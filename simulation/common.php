<?php
/**
 * provides shared adventure logic for all three simulation algorithms
 * manages encounters, battle transitions, and adventure progress
 */
require_once __DIR__ . '/battle.php';



function createAdventureState($player, $current_time){
    return [
        'player' => $player,
        'battle' => null,
        'status' => 'active',
        'current_time' => $current_time,
        'next_encounter_at' => $current_time,
        'total_kills' => 0,
        'total_battles' => 0
    ];
}


function startEncounter($state, $zone, $monsters, $current_time){
    // monster skills disabled in v0.1.0
    $enemies = generateEncounter(
        $zone,
        $monsters,
        [],
        $current_time
    );

    $state['battle'] = createBattleState(
        $state['player'],
        $enemies,
        $current_time
    );

    $state['next_encounter_at'] = null;

    return $state;
}


function finishBattle($state, $zone){
    $battle = $state['battle'];

    $state['player'] = $battle['player'];

    if ($battle['status'] === 'dead') {
        $state['status'] = 'dead';
        $state['next_encounter_at'] = null;
        return $state;
    }

    if ($battle['status'] === 'victory') {
        $state['total_battles']++;
        $state['next_encounter_at'] = round(
            $battle['current_time'] +
            $zone['encounter_interval'], 3
        );
        $state['battle'] = null;
    }

    return $state;
}

/**
 * finds the next encounter or combat event.
 *
 * @param array $state current adventure state
 * @return float|null next event time in seconds
 */
function getNextAdventureEvent($state){
    if ($state['status'] !== 'active') {
        return null;
    }

    if ($state['battle'] === null) {
        return $state['next_encounter_at'];
    }

    $event = getNextBattleEvent(
        $state['battle']
    );

    if ($event === null) {
        return null;
    }
    return $event['time'];
}

/**
 * processes the next adventure event and updates adventure progress.
 *
 * @param mysqli $conn database connection
 * @param array $state current adventure state
 * @param array $zone zone settings
 * @param array $monsters available monsters
 * @param array $monster_drops drop tables indexed by monster id
 * @param array &$rewards accumulated rewards
 * @return array updated adventure state
 */
function processAdventureEvent($conn, $state, $zone, $monsters, $monster_drops, &$rewards){
    $event_time = getNextAdventureEvent($state);

    if ($event_time === null) {
        return $state;
    }

    $state['current_time'] = $event_time;

    if ($state['battle'] === null) {
        return startEncounter(
            $state,
            $zone,
            $monsters,
            $event_time
        );
    }

    $old_kills = $rewards['kills'];

    $state['battle'] = processNextBattleEvent(
        $conn,
        $state['battle'],
        $monster_drops,
        $rewards
    );
    // count only newly defeated monsters
    $state['total_kills'] +=$rewards['kills'] - $old_kills;
    // sync player data with battle state
    $state['player'] = $state['battle']['player'];

    if ($state['battle']['status'] !== 'fighting') {
        $state = finishBattle($state, $zone);
    }

    
    return $state;
}