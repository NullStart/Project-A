<?php
/**
 * handles damage, critical hits, defense reduction, and recovery.
 * provides shared combat calculations for all simulation algorithms.
 * expected damage is used by the statistical simulation.
 */


// diminishing returns from defense
function getDefenseMultiplier($defense){
    return 100 / (100 + $defense);
}


function rollCritical($crit_rate){
    // use a scale of 100000 for critical hit probability
    $random = mt_rand(1, 100000);
    return $random <= $crit_rate * 100000;
}

/**
 * Calculate the damage after applying defense reduction and critical hit correction.
 *
 * @param int $attack attacker attack stat
 * @param int $defense target defense stat
 * @param float $power attack or skill multiplier
 * @param bool $critical whether the attack is critical
 * @param float $crit_damage critical damage multiplier
 * @return int final damage, minimum 1
 */

function calculateDamage($attack, $defense, $power, $critical, $crit_damage){
    $damage = $attack * $power;
    $damage *= getDefenseMultiplier($defense);
    if ($critical) {
        $damage *= $crit_damage;
    }
    $damage = floor($damage);
    if ($damage < 1) {
        $damage = 1;
    }
    return (int) $damage;
}

function normalAttack($attacker, $target){
    $critical = rollCritical(
        $attacker['crit_rate']
    );
    $damage = calculateDamage(
        $attacker['attack'],
        $target['defense'],
        1.0,
        $critical,
        $attacker['crit_damage']
    );
    return [
        'damage' => $damage,
        'critical' => $critical
    ];
}

function skillAttack($attacker, $target, $skill){
    $critical = rollCritical(
        $attacker['crit_rate']
    );

    $damage = calculateDamage(
        $attacker['attack'],
        $target['defense'],
        $skill['power'],
        $critical,
        $attacker['crit_damage']
    );
    return [
        'damage' => $damage,
        'critical' => $critical
    ];
}


function applyDamage($current_hp, $damage){
    $current_hp -= $damage;
    if ($current_hp < 0) {
        $current_hp = 0;
    }

    return $current_hp;
}

function applyRecovery($current, $maximum, $recovery){
    $current += $recovery;
    if ($current > $maximum) {
        $current = $maximum;
    }
    return $current;
}

function calculateRecovery($maximum, $power){
    return (int) floor($maximum * $power);
}



/**
 * calculates average damage based on normal and critical hit probabilities.
 * used by statistical simulation instead of individual attack rolls.
 *
 * @param int $attack attacker attack stat
 * @param int $defense target defense stat
 * @param float $power attack or skill multiplier
 * @param float $crit_rate critical hit probability from 0 to 1
 * @param float $crit_damage critical damage multiplier
 * @return float expected damage per attack
 */

function calculateExpectedDamage($attack, $defense, $power, $crit_rate, $crit_damage){
    $normal = calculateDamage($attack, $defense,$power, false,$crit_damage);

    $critical = calculateDamage($attack, $defense, $power, true,$crit_damage);
    // Expectation of harm (Probability-weighted average)
    $expected = $normal * (1 - $crit_rate) + $critical * $crit_rate;
    return $expected;
}