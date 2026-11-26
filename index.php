<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (validateSession($conn)) {
    header('Location: character.php');
} else {
    header('Location: login.html');
}

exit();