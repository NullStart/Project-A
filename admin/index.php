<?php
/**
 * serves as the entry point of the administration panel.
 * provides access to game content management pages.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin.php';

requireLogin($conn, '../login.html');
requireAdmin($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
</head>
<body>

    <h1>Admin Panel</h1>

    <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>.</p>

    <h2>Game Content</h2>

    <ul>
        <li><a href="items.php">Item Management</a></li>
        <li>Monster Management (TODO)</li>
        <li>Spawn Management (TODO)</li>
        <li>Drop Management (TODO)</li>
    </ul>

    <h2>Player Management</h2>
    
    <ul>
        <li><a href="data.php">Data Management</a></li>
    </ul>

    <ul>
        <li>Inventory Management (TODO)</li>
    </ul>

    <a href="../character.php">Back to Game</a>

</body>
</html>