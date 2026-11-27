<?php
/**
 * handles character data and stat calculations.
 * combines class stats, level growth, and equipment bonuses
 * to calculate the final character stats.
 */
require_once __DIR__ . '/db.php';


/**
 * retrieves character and class data for the current user.
 *
 * @param mysqli $conn database connection
 * @param int $character_id character id
 * @param int $user_id owner id
 * @return array|null character data
 */
function getCharacter($conn, $character_id, $user_id){
    $sql = "SELECT c.*,
                   cl.name AS class_name,
                   cl.base_hp,
                   cl.base_mp,
                   cl.base_attack,
                   cl.base_defense,
                   cl.base_speed,
                   cl.base_crit_rate,
                   cl.base_crit_damage,
                   cl.hp_growth,
                   cl.mp_growth,
                   cl.attack_growth,
                   cl.defense_growth,
                   cl.speed_growth
            FROM characters c, classes cl
            WHERE c.class_id = cl.id 
            AND c.id = ? 
            AND c.user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt, "ii", $character_id, $user_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $character = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $character;
}

//base stats

function getBaseStats($character){
    // level 1 uses base stats without growth
    $level = $character['level'] - 1;

    $stats = [];

    $stats['max_hp'] = $character['base_hp'] + $level * $character['hp_growth'];

    $stats['max_mp'] = $character['base_mp'] +$level * $character['mp_growth'];

    $stats['attack'] = $character['base_attack'] +$level * $character['attack_growth'];

    $stats['defense'] = $character['base_defense'] +$level * $character['defense_growth'];

    $stats['speed'] = $character['base_speed'] + $level * $character['speed_growth'];

    $stats['crit_rate'] = $character['base_crit_rate'];

    $stats['crit_damage'] = $character['base_crit_damage'];

    return $stats;
}

//equipment stats

function getEquipmentStats($conn, $character_id){
    $stats = [
        'max_hp' => 0,
        'max_mp' => 0,
        'attack' => 0,
        'defense' => 0,
        'speed' => 0,
        'crit_rate' => 0,
        'crit_damage' => 0
    ];

    $sql = "SELECT e.enhancement_level,
                   i.bonus_hp,
                   i.bonus_mp,
                   i.bonus_attack,
                   i.bonus_defense,
                   i.bonus_speed,
                   i.bonus_crit_rate,
                   i.bonus_crit_damage
            FROM equipment_instances e, items i
            WHERE e.item_id = i.id 
            AND e.character_id = ? 
            AND e.equipped_slot IS NOT NULL";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $character_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($equipment = mysqli_fetch_assoc($result)) {
        // 10% bonus per enhancement level
        $multiplier = 1 + $equipment['enhancement_level'] * 0.1;

        $stats['max_hp'] += floor($equipment['bonus_hp'] * $multiplier);

        $stats['max_mp'] += floor($equipment['bonus_mp'] * $multiplier);

        $stats['attack'] += floor($equipment['bonus_attack'] * $multiplier);

        $stats['defense'] += floor($equipment['bonus_defense'] * $multiplier);

        $stats['speed'] += floor($equipment['bonus_speed'] * $multiplier);

        $stats['crit_rate'] += $equipment['bonus_crit_rate'] * $multiplier;

        $stats['crit_damage'] +=$equipment['bonus_crit_damage'] * $multiplier;
    }

    mysqli_stmt_close($stmt);

    return $stats;
}

/**
 * calculates the final stats used by the combat system.
 *
 * @param mysqli $conn database connection
 * @param array $character character and class data
 * @return array combined character stats
 */
function getCharacterStats($conn, $character){
    $base = getBaseStats($character);
    $equipment = getEquipmentStats(
        $conn, $character['id']
    );

    $stats = [];
    $stats['max_hp'] = $base['max_hp'] + $equipment['max_hp'];
    $stats['max_mp'] = $base['max_mp'] + $equipment['max_mp'];
    $stats['attack'] = $base['attack'] + $equipment['attack'];
    $stats['defense'] = $base['defense'] + $equipment['defense'];
    $stats['speed'] = $base['speed'] + $equipment['speed'];
    $stats['crit_rate'] = $base['crit_rate'] + $equipment['crit_rate'];
    $stats['crit_damage'] = $base['crit_damage'] + $equipment['crit_damage'];

// cap critical rate at 100% and prevent division by zero
    if ($stats['crit_rate'] > 1) {
        $stats['crit_rate'] = 1;
    }

    if ($stats['speed'] < 1) {
        $stats['speed'] = 1;
    }
    return $stats;
}