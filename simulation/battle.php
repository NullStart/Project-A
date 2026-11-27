<?php
/**
 * handles the core ATB combat system, including player attacks,
 * monster attacks, battle rewards, and action scheduling.
 * provides shared battle logic for all three simulation algorithms.
 */

require_once __DIR__ . '/../includes/character.php';
require_once __DIR__ . '/../includes/level.php';

require_once __DIR__ . '/damage.php';
require_once __DIR__ . '/encounter.php';
require_once __DIR__ . '/rewards.php';

// higher speed means shorter action intervals
function getActionInterval($speed){
    if ($speed < 1) {
        $speed = 1;
    }

    return 100 / $speed;
}


function createPlayerBattleState($character, $stats){
    $player = array_merge($character, $stats);

    $player['next_action_at'] = null;

    return $player;
}

/**
 * initializes a battle and schedules the first action of each participant.
 *
 * @param array $player player battle data
 * @param array $enemies monster instances
 * @param float $current_time simulation time in seconds
 * @return array initial battle state
 */
function createBattleState($player, $enemies, $current_time){
    // use millisecond precision for action times
    $player['next_action_at'] = round(
        $current_time + getActionInterval($player['speed']), 3
    );

    foreach ($enemies as $index => $enemy) {

        $enemies[$index]['next_action_at'] = round(
            $current_time + getActionInterval($enemy['speed']), 3
        );

        $enemies[$index]['rewarded'] = false;
    }

    return [
        'player' => $player,
        'enemies' => $enemies,
        'status' => 'fighting',
        'current_time' => $current_time
    ];
}

// target the first living enemy
function selectPlayerTarget($enemies){
    foreach ($enemies as $index => $enemy) {

        if ($enemy['current_hp'] > 0) {
            return $index;
        }
    }

    return null;
}

/**
 * processes a player attack, kill rewards, and level progression.
 *
 * @param mysqli $conn database connection
 * @param array $battle current battle state
 * @param array $monster_drops drop tables indexed by monster id
 * @param array &$rewards accumulated rewards passed by reference
 * @return array updated battle state
 */
function processPlayerAction($conn, $battle, $monster_drops, &$rewards){
    $index = selectPlayerTarget($battle['enemies']);

    if ($index === null) {
        return $battle;
    }

    $player = $battle['player'];
    $enemy = $battle['enemies'][$index];

    $result = normalAttack($player, $enemy);

    $battle['enemies'][$index]['current_hp'] =
        applyDamage(
            $enemy['current_hp'],
            $result['damage']
        );

    // prevent duplicate kill rewards
    if ($battle['enemies'][$index]['current_hp'] <= 0 &&
        !$enemy['rewarded']) {

        $reward = getMonsterRewards(
            $enemy, $monster_drops
        );

        $rewards = mergeRewards($rewards, $reward);

        $player['gold'] += $reward['gold'];

        $old_level = $player['level'];

        $player = addExperience(
            $player, $reward['exp']
        );
        //recalculate stats and restore HP and MP after leveling up
        if ($player['level'] > $old_level) {

            $stats = getCharacterStats($conn, $player);

            $player = array_merge($player, $stats);

            $player['current_hp'] = $stats['max_hp'];
            $player['current_mp'] = $stats['max_mp'];
        }

        $battle['enemies'][$index]['rewarded'] = true;

        $battle['enemies'][$index]['next_action_at'] = null;
    }

    $player['next_action_at'] = round(
        $battle['current_time'] +
        getActionInterval($player['speed']), 3
    );

    $battle['player'] = $player;

    return $battle;
}



function processMonsterAction($battle, $index){
    $monster = $battle['enemies'][$index];

    if ($monster['current_hp'] <= 0) {
        return $battle;
    }

    $result = normalAttack(
        $monster,
        $battle['player']
    );

    $battle['player']['current_hp'] =
        applyDamage(
            $battle['player']['current_hp'],
            $result['damage']
        );

    $battle['enemies'][$index]['next_action_at'] =
        round(
            $battle['current_time'] +
            getActionInterval($monster['speed']), 3
        );

    return $battle;
}



function checkBattleStatus($battle){
    if ($battle['player']['current_hp'] <= 0) {

        $battle['status'] = 'dead';

        $battle['player']['next_action_at'] = null;

    } elseif (
        selectPlayerTarget($battle['enemies']) === null
    ) {

        $battle['status'] = 'victory';

        $battle['player']['next_action_at'] = null;

    } else {

        $battle['status'] = 'fighting';
    }

    return $battle;
}

/**
 * finds the next action based on scheduled action times.
 *
 * @param array $battle current battle state
 * @return array|null next actor, enemy index, and action time
 */
function getNextBattleEvent($battle){
    if ($battle['status'] !== 'fighting') {
        return null;
    }

    $event = [
        'actor' => 'player',
        'index' => null,
        'time' => $battle['player']['next_action_at']
    ];

    foreach ($battle['enemies'] as $index => $enemy) {

        if ($enemy['current_hp'] <= 0) {
            continue;
        }
        // When the attack times of the player and the monster are the same, the player will attack first
        if ($enemy['next_action_at'] < $event['time']) {

            $event = [
                'actor' => 'monster',
                'index' => $index,
                'time' => $enemy['next_action_at']
            ];
        }
    }

    return $event;
}

/**
 * executes the next scheduled action and checks the battle outcome.
 *
 * @param mysqli $conn database connection
 * @param array $battle current battle state
 * @param array $monster_drops drop tables indexed by monster id
 * @param array &$rewards accumulated rewards passed by reference
 * @return array updated battle state
 */
function processNextBattleEvent($conn, $battle, $monster_drops, &$rewards){
    $event = getNextBattleEvent($battle);

    if ($event === null) {
        return $battle;
    }
    // advance directly to the scheduled action time
    $battle['current_time'] = $event['time'];

    if ($event['actor'] === 'player') {
        $battle = processPlayerAction(
            $conn,
            $battle,
            $monster_drops,
            $rewards
        );

    } else {
        $battle = processMonsterAction(
            $battle,
            $event['index']
        );
    }
    return checkBattleStatus($battle);
}