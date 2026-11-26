<?php

require_once __DIR__ . '/config.php';

try {
    $conn = mysqli_connect(
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        DB_PORT
    );

    mysqli_set_charset($conn, 'utf8mb4');

    mysqli_query($conn, "SET time_zone = '+00:00'");

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    die('Database connection failed.');
}