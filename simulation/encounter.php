<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/skills.php';


//Read zone

function getZone($conn, $zone_id)
{
    $sql = "SELECT *
            FROM zones
            WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $zone_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $zone = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $zone;
}

//read monsters in zone

function getZoneMonsters($conn, $zone_id)
{
    $sql = "SELECT m.*, zm.spawn_weight
            FROM monsters m, zone_monsters zm
            WHERE m.id = zm.monster_id AND
                  zm.zone_id = ?
            ORDER BY m.id";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $zone_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $monsters = [];

    while ($monster = mysqli_fetch_assoc($result)) {
        $monsters[] = $monster;
    }

    mysqli_stmt_close($stmt);

    return $monsters;
}

//Calculate spawn weight

function getTotalSpawnWeight($monsters)
{
    $total_weight = 0;

    foreach ($monsters as $monster) {
        $total_weight += $monster['spawn_weight'];
    }

    return $total_weight;
}

//Select random monster

function selectRandomMonster($monsters)
{
    $total_weight = getTotalSpawnWeight($monsters);

    if ($total_weight <= 0) {
        return null;
    }

    $random = mt_rand(1, $total_weight);

    foreach ($monsters as $monster) {

        $random -= $monster['spawn_weight'];

        if ($random <= 0) {
            return $monster;
        }
    }

    return null;
}

//Create monster instance

function createMonsterInstance(
    $monster, $skills, $instance_id, $current_time
)
{
    $instance = $monster;

    $instance['instance_id'] = $instance_id;

    $instance['current_hp'] = $monster['max_hp'];
    $instance['current_mp'] = $monster['max_mp'];

    $instance['skills'] = $skills;

    $instance['cooldowns'] = [];

    $instance['casting'] = null;

    $instance['next_action_at'] =
        $current_time + 100 / $monster['speed'];

    return $instance;
}

//Load zone monster skills

function loadZoneMonsterSkills($conn, $monsters)
{
    $monster_skills = [];

    foreach ($monsters as $monster) {

        $monster_id = $monster['id'];

        $monster_skills[$monster_id] =
            getMonsterSkills($conn, $monster_id);
    }

    return $monster_skills;
}

//Generate encounter

function generateEncounter(
    $zone, $monsters, $monster_skills, $current_time
)
{
    $enemies = [];

    $count = mt_rand(
        $zone['min_monsters'],
        $zone['max_monsters']
    );

    for ($i = 0; $i < $count; $i++) {

        $monster = selectRandomMonster($monsters);

        if ($monster === null) {
            throw new RuntimeException(
                'No valid monsters in this zone.'
            );
        }

        $monster_id = $monster['id'];

        $skills = $monster_skills[$monster_id] ?? [];

        $enemies[] = createMonsterInstance(
            $monster,
            $skills,
            $i + 1,
            $current_time
        );
    }

    return $enemies;
}

//Calculate spawn probabilities

function getSpawnProbabilities($monsters)
{
    $probabilities = [];

    $total_weight = getTotalSpawnWeight($monsters);

    if ($total_weight <= 0) {
        return $probabilities;
    }

    foreach ($monsters as $monster) {

        $monster_id = $monster['id'];

        $probabilities[$monster_id] =
            $monster['spawn_weight'] / $total_weight;
    }

    return $probabilities;
}

//Calculate expected monster count

function getExpectedMonsterCount($zone)
{
    return (
        $zone['min_monsters'] +
        $zone['max_monsters']
    ) / 2;
}