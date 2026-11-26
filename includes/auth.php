<?php

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn()
{
    return isset($_SESSION['user_id'],
                 $_SESSION['login_token']);
}

function getUserId()
{
    return $_SESSION['user_id'] ?? null;
}

function loginUser($conn, $user_id, $username)
{
    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);

    $sql = "UPDATE users
            SET login_token = ?
            WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt, "si", $token_hash, $user_id
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['login_token'] = $token;
}

function validateSession($conn)
{
    if (!isLoggedIn()) {
        return false;
    }

    $user_id = getUserId();

    $sql = "SELECT login_token
            FROM users
            WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$user || $user['login_token'] === null) {
        return false;
    }

    $token_hash = hash(
        'sha256', $_SESSION['login_token']
    );

    return hash_equals(
        $user['login_token'], $token_hash
    );
}

function requireLogin($conn, $login_page = 'login.html')
{
    if (!isLoggedIn()) {
        header('Location: ' . $login_page);
        exit();
    }

    if (!validateSession($conn)) {
        clearSession();

        header('Location: ' . $login_page . '?error=session_expired');
        exit();
    }
}

function clearSession()
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function logoutUser($conn)
{
    if (isLoggedIn()) {

        $user_id = getUserId();

        $token_hash = hash(
            'sha256', $_SESSION['login_token']
        );

        $sql = "UPDATE users
                SET login_token = NULL
                WHERE id = ? AND login_token = ?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt, "is", $user_id, $token_hash
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    clearSession();
}