<?php
/**
 * implements event jump simulation by processing scheduled events directly instead of advancing through fixed time steps.
 * uses the same combat rules as exact simulation.
 */
require_once __DIR__ . '/common.php';


/**
 * @param mysqli $conn database connection
 * @param array $state current adventure state
 * @param array $zone zone settings
 * @param array $monsters available monsters
 * @param array $monster_drops drop tables indexed by monster id
 * @param float $elapsed_time elapsed time in seconds
 * @return array simulation results and performance counters
 */
function simulateEventJump($conn, $state, $zone, $monsters, $monster_drops, $elapsed_time){
    $rewards = createRewards();
    $end_time = round($state['current_time'] +$elapsed_time, 3);
    $iterations = 0;
    $events = 0;
    // skip time periods without events
    while ($state['status'] === 'active') {
        $event_time = getNextAdventureEvent($state);
        // stop if no event remains within the simulation period
        if ($event_time === null ||
            $event_time > $end_time) {
            break;
        }

        $state = processAdventureEvent(
            $conn,
            $state,
            $zone,
            $monsters,
            $monster_drops,
            $rewards
        );

        $iterations++;
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
        'algorithm' => 'event_jump'
    ];
}