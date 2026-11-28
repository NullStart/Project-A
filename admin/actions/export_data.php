<?php
/**
 * processes game data export requests from administrators.
 * validates the request before writing database records to CSV files.
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin.php';
require_once __DIR__ . '/../../includes/data_sync.php';
require_once __DIR__ . '/../../tools/import_data.php';

requireLogin($conn, '../../login.html');
requireAdmin($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    exit('Invalid request method.');
}

// validate csrf token
$token = $_POST['csrf_token'] ?? '';
$session_token = $_SESSION['admin_data_token'] ?? '';

if (!is_string($token) || empty($session_token) || !hash_equals($session_token, $token)){
    http_response_code(403);
    exit('Invalid request token.');
}

try {
    $counts = exportGameData($conn);
    generateSeed();
}
catch (Throwable $e){
    error_log($e->getMessage());

    exit('Unable to synchronize game data. <a href="../data.php">Go Back</a>');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Export Game Data</title>
</head>
<body>

    <h1>Export Game Data</h1>

    <p>Game data exported successfully.</p>
    <p>CSV files updated successfully.</p>
    <p>seed.sql generated successfully.</p>

    <table border="1" cellpadding="5">
        <tr>
            <th>Table</th>
            <th>Exported Records</th>
        </tr>

        <?php foreach ($counts as $table => $count){ ?>
            <tr>
                <td><?php echo htmlspecialchars($table); ?></td>
                <td><?php echo $count; ?></td>
            </tr>
        <?php } ?>

    </table>

    <p><a href="../data.php">Back to Data Management</a></p>

</body>
</html>