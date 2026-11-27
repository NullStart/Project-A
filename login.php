<?php
/**
 * handles user login and password verification.
 * creates a new login session after successful authentication
 * and redirects the user to the character selection page.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$username = filter_input(INPUT_POST, 'username');
$password = filter_input(INPUT_POST, 'password');

//Input
if (empty($username) || empty($password)) {
    echo '<p>Username and password are required.</p>';
    echo '<a href="login.html">Go Back</a>';
    exit();
}

$username = trim($username);
//find user

$sql = "SELECT id, username, password_hash
        FROM users
        WHERE username = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

// use the same error message for unknown users and incorrect passwords
if (!$user || !password_verify($password, $user['password_hash'])) {
    echo '<p>Invalid username or password.</p>';
    echo '<a href="login.html">Go Back</a>';
    exit();
}

//invalidate previous login sessions
loginUser($conn, $user['id'], $user['username']);

mysqli_close($conn);

header('Location: character.php');
exit();