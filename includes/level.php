<?php

require_once __DIR__ . '/character.php';


// read EXP

function getRequiredExp($level)
{
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

// add EXP and calculate level

function addExperience($character, $gained_exp)
{
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

    if ($required_exp === null) {
        $character['exp'] = 0;
    }

    return $character;
}

// Handling upgraded HP and MP

function processExperience($conn, $character, $gained_exp)
{
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