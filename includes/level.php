<?php
/**
 * handles character experience and level progression using
 * the experience requirements defined in levels.json
 * manages HP and MP recovery after leveling up
 */

require_once __DIR__ . '/character.php';


/**
 * @param int $level current character level
 * @return int|null experience required for the next level, null if unavailable
 */
function getRequiredExp($level){
    //avoid reading levels.json multiple times
    static $levels = null;

    if ($levels === null) {

        $json = file_get_contents(
            __DIR__ . '/../data/levels.json'
        );

        $levels = json_decode($json, true);

        if (!is_array($levels)) {
            throw new RuntimeException(
                'Invalid level configuration.'
            );
        }
    }

    return $levels[$level] ?? null;
}

/**
 * applies experience and handles multiple level-ups.
 *
 * @param array $character current character data
 * @param int $gained_exp experience gained
 * @return array updated character data
 */

function addExperience($character, $gained_exp){
    if ($gained_exp <= 0) {
        return $character;
    }

    $character['exp'] += $gained_exp;

    $required_exp = getRequiredExp(
        $character['level']
    );
   
    while ($required_exp !== null &&
           $character['exp'] >= $required_exp) {

        $character['exp'] -= $required_exp;

        $character['level']++;

        $required_exp = getRequiredExp(
            $character['level']
        );
    }
    //If the limit is reached, clear out the excess experience.
    if ($required_exp === null) {
        $character['exp'] = 0;
    }

    return $character;
}

/**
 * applies experience and restores HP and MP after leveling up.
 *
 * @param mysqli $conn database connection
 * @param array $character current character data
 * @param int $gained_exp experience gained
 * @return array updated character data
 */
function processExperience($conn, $character, $gained_exp){
    $old_level = $character['level'];

    $character = addExperience(
        $character, $gained_exp
    );
    if ($character['level'] > $old_level) {
        $stats = getCharacterStats(
            $conn, $character
        );
        $character['current_hp'] = $stats['max_hp'];
        $character['current_mp'] = $stats['max_mp'];
    }

    return $character;
}