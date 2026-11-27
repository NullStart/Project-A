<?php
/**
 * implements statistical simulation for long offline periods.
 * estimates battle duration, HP loss, and rewards using expected values.
 * falls back to individual event processing when batch simulation is unsuitable.
 */
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/event_jump.php';

/**
 * estimates the average duration, damage taken, and rewards of an encounter.
 *
 * @param array $player player stats
 * @param array $zone zone settings
 * @param array $monsters available monsters
 * @param array $monster_drops drop tables indexed by monster id
 * @return array expected encounter results
 */
function getAverageEncounter($player, $zone, $monsters, $monster_drops){
    $probabilities = getSpawnProbabilities($monsters);
    $average = [
        'duration' => 0,
        'hp_loss' => 0,
        'exp' => 0,
        'gold' => 0,
        'items' => [],
        'kills' => 0
    ];

    $count = getExpectedMonsterCount($zone);
    $player_interval = getActionInterval(
        $player['speed']
    );

    foreach ($monsters as $monster) {
        $probability = $probabilities[$monster['id']];
        
        $player_damage = calculateExpectedDamage(
            $player['attack'],
            $monster['defense'],
            1.0,
            $player['crit_rate'],
            $player['crit_damage']
        );

        $monster_damage = calculateExpectedDamage(
            $monster['attack'],
            $player['defense'],
            1.0,
            $monster['crit_rate'],
            $monster['crit_damage']
        );

        $hits = ceil($monster['max_hp'] / $player_damage);

        $kill_time = $hits * $player_interval;

        $monster_interval = getActionInterval($monster['speed']);
        // weighted average based on spawn probability
        $average['duration'] += $kill_time * $probability;
        $average['hp_loss'] += ($monster_damage / $monster_interval) * $probability;

        $reward = getExpectedMonsterRewards($monster, $monster_drops);
        
        $average['exp'] += $reward['exp'] * $probability;
        $average['gold'] += $reward['gold'] * $probability;

        foreach ($reward['items'] as $id => $quantity) {
            if (!isset($average['items'][$id])) {
                $average['items'][$id] = 0;
            }
            $average['items'][$id] += $quantity * $probability;
        }
    }
    // account for decreasing enemy count during combat
    // approximate total damage using n * (n + 1) / 2
    $average['hp_loss'] *= $average['duration'] * $count * ($count + 1) / 2;
    $average['duration'] *= $count;
    $average['duration'] += $zone['encounter_interval'];
    $average['exp'] *= $count;
    $average['gold'] *= $count;
    $average['kills'] = $count;

    foreach ($average['items'] as $id => $quantity) {
        $average['items'][$id] = $quantity * $count;
    }
    return $average;
}

/**
 * converts fractional values to integers while preserving
 * their expected value through random rounding.
 *
 * @param float $value expected value
 * @return int rounded value
 */
function randomRound($value){
    $whole = floor($value);
    $fraction = $value - $whole;
    $random = mt_rand(1, 100000000);
    // round up with probability equal to the fractional part
    if ($random <= $fraction * 100000000) {
        $whole++;
    }
    return (int) $whole;
}



function getStatisticalRewards($average, $battles){
    $rewards = createRewards();

    $rewards['exp'] = randomRound($average['exp'] * $battles);
    $rewards['gold'] = randomRound($average['gold'] * $battles);
    $rewards['kills'] = randomRound($average['kills'] * $battles);

    foreach ($average['items'] as $id => $quantity) {
        $amount = randomRound($quantity * $battles);
        if ($amount > 0) {
            $rewards['items'][$id] = $amount;
        }
    }
    return $rewards;
}

/**
 * limits batch size based on available time, remaining HP,
 * and experience required for the next level.
 *
 * @param array $state current adventure state
 * @param array $average expected encounter results
 * @param array $monsters available monsters
 * @param array $zone zone settings
 * @param float $remaining_time remaining simulation time
 * @return int number of battles eligible for batch simulation
 */
function getStatisticalBatchSize($state, $average, $monsters, $zone, $remaining_time){
    if ($average['duration'] <= 0) {
        return 0;
    }

    // limit battles by remaining time
    $battles = floor($remaining_time / $average['duration']);

    // keep at least 1 HP after estimated damage
    $hp_loss = ceil($average['hp_loss']);

    if ($hp_loss > 0) {
        $hp_limit = floor(($state['player']['current_hp'] - 1) / $hp_loss);
        $battles = min($battles, $hp_limit);
    }
    // avoid crossing a level-up during batch simulation
    $required_exp = getRequiredExp($state['player']['level']);

    $max_exp = 0;

    foreach ($monsters as $monster) {
        if ($monster['exp_reward'] > $max_exp) {
            $max_exp = $monster['exp_reward'];
        }
    }
    // maximum possible exp from one encounter
    $max_battle_exp = $max_exp * $zone['max_monsters'];

    if ($required_exp !== null && $max_battle_exp > 0) {
        $remaining_exp =$required_exp - $state['player']['exp'];

        $level_limit = floor(($remaining_exp - 1) / $max_battle_exp);
        $battles = min($battles, $level_limit);
    }
    return (int) max(0, $battles);
}

/**
 * @param mysqli $conn database connection
 * @param array $state current adventure state
 * @param array $zone zone settings
 * @param array $monsters available monsters
 * @param array $monster_drops drop tables indexed by monster id
 * @param float $elapsed_time elapsed time in seconds
 * @return array simulation results and performance counters
 */
function simulateStatistical($conn, $state, $zone, $monsters, $monster_drops, $elapsed_time){
    $rewards = createRewards();
    $end_time = round($state['current_time'] + $elapsed_time, 3);
    $iterations = 0;
    $events = 0;
    $statistical_battles = 0;

    while ($state['status'] === 'active') {
        $event_time = getNextAdventureEvent($state);
        if ($event_time === null ||
            $event_time > $end_time) {
            break;
        }
    
        $iterations++;
        // only batch between encounters
        if ($state['battle'] === null) {
            $average = getAverageEncounter(
                $state['player'],
                $zone,
                $monsters,
                $monster_drops
            );

            $remaining_time = $end_time - $event_time;

            $battles = getStatisticalBatchSize(
                $state,
                $average,
                $monsters,
                $zone,
                $remaining_time
            );
            // require at least two battles for batch simulation
            if ($battles >= 2) {
                $reward = getStatisticalRewards($average, $battles);
                $rewards = mergeRewards($rewards, $reward);
                $state['player']['gold'] += $reward['gold'];
                $state['player'] = addExperience( $state['player'],$reward['exp']);
                $hp_loss = ceil($average['hp_loss']) * $battles;
                $state['player']['current_hp'] -= $hp_loss;
                $state['total_kills'] += $reward['kills'];
                $state['total_battles'] += $battles;
                $state['current_time'] = round($event_time + $average['duration'] * $battles, 3);
                $state['next_encounter_at'] =$state['current_time'];
                $statistical_battles += $battles;
                // skip individual events for simulated battles
                continue;
            }
        }

        // fall back to individual events when batching is unsuitable
        $state = processAdventureEvent(
            $conn,
            $state,
            $zone,
            $monsters,
            $monster_drops,
            $rewards
        );
        $events++;
    }
    // preserve the actual death time
    if ($state['status'] === 'active') {
        $state['current_time'] = $end_time;
    }
    return [
        'state' => $state,
        'rewards' => $rewards,
        'iterations' => $iterations,
        'events' => $events,
        'statistical_battles' => $statistical_battles,
        'algorithm' => 'statistical'
    ];
}