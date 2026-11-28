<?php
/**
 * handles administrator authorization for the management pages.
 * restricts access to users with valid administrator privileges.
 */

require_once __DIR__ . '/auth.php';

function isAdmin($conn){
    if (!validateSession($conn)){
        return false;
    }

    $user_id = getUserId();

    $sql = "SELECT is_admin
            FROM users
            WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $user && (int)$user['is_admin'] === 1;
}

/**
 * prevents unauthorized access to administrator pages.
 */
function requireAdmin($conn){
    if (!isAdmin($conn)){
        http_response_code(403);
        exit('Access denied.');
    }
}