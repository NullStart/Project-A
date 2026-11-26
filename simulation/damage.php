<?php


//Calculate defense reduction damage

function getDefenseMultiplier($defense)
{
    return 100 / (100 + $defense);
}

//Critical hit determination

function rollCritical($crit_rate)
{
    $random = mt_rand(1, 100000);

    return $random <= $crit_rate * 100000;
}

//Calculate damage

function calculateDamage(
    $attack,
    $defense,
    $power,
    $critical,
    $crit_damage
)
{
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

//Normal attack

function normalAttack($attacker, $target)
{
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

//Skill attack

function skillAttack($attacker, $target, $skill)
{
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

//Apply damage

function applyDamage($current_hp, $damage)
{
    $current_hp -= $damage;

    if ($current_hp < 0) {
        $current_hp = 0;
    }

    return $current_hp;
}

//Apply recovery

function applyRecovery(
    $current, $maximum, $recovery
)
{
    $current += $recovery;

    if ($current > $maximum) {
        $current = $maximum;
    }

    return $current;
}

//Calculate recovery

function calculateRecovery($maximum, $power)
{
    return (int) floor($maximum * $power);
}



//Expected damage

function calculateExpectedDamage(
    $attack,
    $defense,
    $power,
    $crit_rate,
    $crit_damage
)
{
    $normal = calculateDamage(
        $attack,
        $defense,
        $power,
        false,
        $crit_damage
    );

    $critical = calculateDamage(
        $attack,
        $defense,
        $power,
        true,
        $crit_damage
    );

    $expected = $normal * (1 - $crit_rate) +
                $critical * $crit_rate;

    return $expected;
}