<?php

/**
 * establishes the database connection used by the game
 * configures character encoding, timezone, and error handling
 */

require_once __DIR__ . '/config.php';

mysqli_report(
    MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT
);

try {
    $conn = mysqli_connect(
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        DB_PORT
    );
    mysqli_set_charset($conn, 'utf8mb4');

    // consistent timezone for offline simulation
    mysqli_query($conn, "SET time_zone = '+00:00'");
} catch (mysqli_sql_exception $e){
    die("Database connection failed: " . $e->getMessage());
}