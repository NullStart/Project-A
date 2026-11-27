<?php
/**
 * handles skill data, level requirements, MP costs, and cooldowns.
 * provides shared skill functions for the combat system.
 */
require_once __DIR__ . '/db.php';



function getClassSkills($conn, $class_id){
    $sql = "SELECT s.*, cs.required_level
            FROM skills s, class_skills cs
            WHERE s.id = cs.skill_id 
            AND cs.class_id = ?
            ORDER BY cs.required_level, s.id";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $class_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $skills = [];

    while ($skill = mysqli_fetch_assoc($result)) {
        $skills[] = $skill;
    }

    mysqli_stmt_close($stmt);
    return $skills;
}

function getMonsterSkills($conn, $monster_id){
    $sql = "SELECT s.*, ms.use_weight
            FROM skills s, monster_skills ms
            WHERE s.id = ms.skill_id 
            AND ms.monster_id = ?
            ORDER BY s.id";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $monster_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $skills = [];
    while ($skill = mysqli_fetch_assoc($result)) {
        $skills[] = $skill;
    }
    mysqli_stmt_close($stmt);
    return $skills;
}


function getUnlockedSkills($skills, $level){
    $unlocked = [];
    foreach ($skills as $skill) {
        if ($level >= $skill['required_level']) {
            $unlocked[] = $skill;
        }
    }
    return $unlocked;
}

/**
 * checks whether a skill can be used.
 *
 * @param array $skill skill data
 * @param int $current_mp remaining MP
 * @param array $cooldowns skill ids mapped to ready times
 * @param float $current_time current simulation time in seconds
 * @return bool whether the skill is available
 */

function canUseSkill($skill, $current_mp, $cooldowns, $current_time){
    if ($current_mp < $skill['mp_cost']) {
        return false;
    }

    $skill_id = $skill['id'];

    if (isset($cooldowns[$skill_id])) {
        if ($current_time < $cooldowns[$skill_id]) {
            return false;
        }
    }
    return true;
}


function getAvailableSkills($skills, $current_mp, $cooldowns, $current_time){
    $available = [];
    foreach ($skills as $skill) {
        if (canUseSkill(
            $skill,
            $current_mp,
            $cooldowns,
            $current_time
        )) {
            $available[] = $skill;
        }
    }

    return $available;
}

// cooldown starts after casting ends

function getSkillReadyTime($skill, $cast_end_time){
    return $cast_end_time + $skill['cooldown'];
}