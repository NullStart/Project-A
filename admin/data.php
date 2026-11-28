<?php
/**
 * provides access to game data synchronization tools.
 * allows administrators to export and refresh game definitions.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin.php';

requireLogin($conn, '../login.html');
requireAdmin($conn);

if (empty($_SESSION['admin_data_token'])){
    $_SESSION['admin_data_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Data Management</title>
</head>
<body>

    <h1>Data Management</h1>

    <h2>Export Game Data</h2>

    <p>Export current game definitions from the database to CSV files.</p>

    <p>Existing CSV files will be replaced and seed.sql will be regenerated.</p>

    <form action="actions/export_data.php" method="POST">
        <input type="hidden" name="csrf_token"
               value="<?php echo htmlspecialchars($_SESSION['admin_data_token']); ?>">

        <button type="submit">Export Data</button>
    </form>

    <hr>

    <h2>Refresh Database</h2>

    <p>Load game definitions from CSV files into the database.</p>

    <button>Refresh Database</button>

    <hr>

    <a href="index.php">Back to Admin Panel</a>

</body>
</html>