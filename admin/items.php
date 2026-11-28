<?php
/**
 * displays all item definitions stored in the database.
 * provides access to item creation and editing pages.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/assets.php';

requireLogin($conn, '../login.html');
requireAdmin($conn);

$sql = "SELECT *
        FROM items
        ORDER BY id";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Item Management</title>
</head>
<body>

    <h1>Item Management</h1>

    <p>
        <a href="edit_item.php">Create Item</a> |
        <a href="index.php">Back to Admin Panel</a>
    </p>

    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th>
            <th>Icon</th>
            <th>Name</th>
            <th>Type</th>
            <th>Required Level</th>
            <th>Buy Price</th>
            <th>Sell Price</th>
            <th>Action</th>
        </tr>

        <?php while ($item = mysqli_fetch_assoc($result)){ ?>
            <tr>
                <td><?php echo $item['id']; ?></td>

                <td>
                    <img src="../<?php echo htmlspecialchars(getAssetImage('items', $item['icon'])); ?>"
                         width="32"
                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                </td>

                <td><?php echo htmlspecialchars($item['name']); ?></td>

                <td>
                    <?php
                    switch ($item['type_id']){
                        case 1:
                            echo 'Equipment';
                            break;
                        case 2:
                            echo 'Consumable';
                            break;
                        case 3:
                            echo 'Material';
                            break;
                        default:
                            echo 'Unknown';
                    }
                    ?>
                </td>

                <td><?php echo $item['required_level']; ?></td>
                <td><?php echo $item['buy_price']; ?></td>
                <td><?php echo $item['sell_price']; ?></td>

                <td>
                    <a href="edit_item.php?id=<?php echo $item['id']; ?>">Edit</a>
                </td>
            </tr>
        <?php } ?>

    </table>

</body>
</html>