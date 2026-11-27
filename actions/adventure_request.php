<?php
/**
 * handles shared request validation for adventure actions.
 * checks login status, request method, character id, and CSRF token.
 * used by start, stop, update, and revive actions.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../simulation/controller.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

requireLogin($conn, '../login.html');

$user_id = getUserId();

$character_id = filter_input(INPUT_POST, 'character_id', FILTER_VALIDATE_INT);

if (!$character_id || $character_id < 1) {
    http_response_code(400);
    exit('Invalid character.');
}

// prevent unauthorized cross-site requests
$csrf_token = filter_input(INPUT_POST, 'csrf_token');

if (!isset($_SESSION['csrf_token']) ||
    !$csrf_token ||
    !hash_equals($_SESSION['csrf_token'], $csrf_token)) {

    http_response_code(403);
    exit('Invalid request token.');
}