<?php
/**
 * handles user logout and redirects the user to the login page.
 * clears the current login session through the authentication module.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser($conn);

mysqli_close($conn);

header('Location: login.html');
exit();