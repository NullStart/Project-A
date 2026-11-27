<?php
/**
 * implements exact simulation using a fixed time step.
 * checks for scheduled events every 0.1 seconds while preserving their original execution times.
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
function simulateExact($conn, $state, $zone, $monsters,$monster_drops, $elapsed_time){
    $rewards = createRewards();
    // time step in seconds
    $step = 0.1;
    $current_time = $state['current_time'];
    $end_time = round($current_time + $elapsed_time, 3);
    $iterations = 0;
    $events = 0;
    // advance simulation in fixed time steps
    while ($current_time < $end_time &&
           $state['status'] === 'active') {
        $current_time = round(
            $current_time + $step, 3
        );
        // prevent advancing beyond the end time
        if ($current_time > $end_time) {
            $current_time = $end_time;
        }

        $iterations++;

        $event_time = getNextAdventureEvent($state);
        // process all scheduled events within the current step
        while ($event_time !== null &&
               $event_time <= $current_time &&
               $state['status'] === 'active') {
            $state = processAdventureEvent(
                $conn,
                $state,
                $zone,
                $monsters,
                $monster_drops,
                $rewards
            );
            $events++;
            $event_time = getNextAdventureEvent($state);
        }
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
        'algorithm' => 'exact'
    ];
}