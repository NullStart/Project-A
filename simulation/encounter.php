<?php
/**
 * handles monster encounters based on zone settings and spawn weights.
 * creates individual monster instances for combat and provides
 * spawn probabilities for statistical simulation.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/skills.php';

function getZone($conn, $zone_id){
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

function getZoneMonsters($conn, $zone_id){
    $sql = "SELECT m.*, zm.spawn_weight
            FROM monsters m, zone_monsters zm
            WHERE m.id = zm.monster_id 
            AND zm.zone_id = ?
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


function getTotalSpawnWeight($monsters){
    $total_weight = 0;
    foreach ($monsters as $monster) {
        $total_weight += $monster['spawn_weight'];
    }
    return $total_weight;
}

/**
 * selects a random monster based on spawn weights.
 *
 * @param array $monsters available monsters with spawn weights
 * @return array|null selected monster
 */

function selectRandomMonster($monsters){
    $total_weight = getTotalSpawnWeight($monsters);

    if ($total_weight <= 0) {
        return null;
    }

    $random = mt_rand(1, $total_weight);
    // weighted random selection
    foreach ($monsters as $monster) {
        $random -= $monster['spawn_weight'];
        if ($random <= 0) {
            return $monster;
        }
    }
    return null;
}

/**
 * creates an independent monster instance for combat.
 *
 * @param array $monster base monster data
 * @param array $skills monster skills
 * @param int $instance_id unique id within the encounter
 * @param float $current_time current simulation time in seconds
 * @return array monster battle state
 */

function createMonsterInstance($monster, $skills, $instance_id, $current_time){
    $instance = $monster;
    $instance['instance_id'] = $instance_id;
    $instance['current_hp'] = $monster['max_hp'];
    $instance['current_mp'] = $monster['max_mp'];
    $instance['skills'] = $skills;
    $instance['cooldowns'] = [];
    $instance['casting'] = null;
    // initial action time based on speed
    $instance['next_action_at'] =$current_time + 100 / $monster['speed'];

    return $instance;
}


function loadZoneMonsterSkills($conn, $monsters){
    $monster_skills = [];
    foreach ($monsters as $monster) {
        $monster_id = $monster['id'];
        $monster_skills[$monster_id] = getMonsterSkills($conn, $monster_id);
    }
    return $monster_skills;
}

/**
 * generates a group of monsters for a new encounter.
 *
 * @param array $zone zone settings
 * @param array $monsters available monsters
 * @param array $monster_skills skills grouped by monster id
 * @param float $current_time current simulation time in seconds
 * @return array monster instances
 */
function generateEncounter($zone, $monsters, $monster_skills, $current_time){
    $enemies = [];
    $count = mt_rand($zone['min_monsters'],$zone['max_monsters']
    );
    // allow multiple monsters of the same type
    for ($i = 0; $i < $count; $i++) {
        $monster = selectRandomMonster($monsters);
        if ($monster === null) {
            throw new RuntimeException(
                'No valid monsters in this zone'
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

/**
 * converts spawn weights into probabilities for statistical simulation.
 *
 * @param array $monsters monsters with spawn weights
 * @return array probabilities indexed by monster id
 */

function getSpawnProbabilities($monsters){
    $probabilities = [];

    $total_weight = getTotalSpawnWeight($monsters);
    if ($total_weight <= 0) {
        return $probabilities;
    }

    foreach ($monsters as $monster) {
        $monster_id = $monster['id'];
        $probabilities[$monster_id] = $monster['spawn_weight'] / $total_weight;
    }
    return $probabilities;
}

// expected count under a uniform distribution
function getExpectedMonsterCount($zone){
    return ($zone['min_monsters'] + $zone['max_monsters']) / 2;
}