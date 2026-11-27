<?php
/**
 * serves as the entry point of the game.
 * redirects users to the character selection or login page
 * based on their session status.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (validateSession($conn)) {
    header('Location: character.php');
} else {
    header('Location: login.html');
}

exit();