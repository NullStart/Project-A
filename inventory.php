<?php

/**
 * displays the character's inventory, equipment, and gold.
 * stackable items are displayed by quantity, while equipment
 * is displayed as individual instances.
 */

 // TODO: support equipping and unequipping equipment
// TODO: support consumable item usage
// TODO: add item selling and equipment enhancement (may sell in shop)
// TODO: display item descriptions and stat bonuses
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/character.php';
require_once __DIR__ . '/includes/assets.php';

requireLogin($conn);

$user_id = getUserId();

$character_id = filter_input(INPUT_GET, 'character_id', FILTER_VALIDATE_INT);

$character = getCharacter($conn, $character_id, $user_id);

if (!$character) {
    header('Location: character.php');
    exit();
}



$sql = "SELECT i.name, i.icon,s.quantity
        FROM items i, inventory_stacks s
        WHERE i.id = s.item_id 
        AND s.character_id = ?
        ORDER BY i.id";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $character_id);
mysqli_stmt_execute($stmt);

$items = mysqli_stmt_get_result($stmt);

mysqli_stmt_close($stmt);



$sql = "SELECT i.name, i.icon, e.enhancement_level
        FROM items i, equipment_instances e
        WHERE i.id = e.item_id 
        AND e.character_id = ?
        ORDER BY i.id";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $character_id);
mysqli_stmt_execute($stmt);

$equipment = mysqli_stmt_get_result($stmt);

mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Inventory</title>
</head>

<body>

    <h1>Inventory</h1>

    <p>Gold: <?php echo $character['gold']; ?></p>

    <h2>Items</h2>

    <?php while ($item = mysqli_fetch_assoc($items)) { ?>
        <p>
            <img src="<?php echo htmlspecialchars(getAssetImage('items', $item['icon'])); ?>" width="32">

            <?php echo htmlspecialchars($item['name']); ?>
            x<?php echo $item['quantity']; ?>
        </p>
    <?php } ?>

    <h2>Equipment</h2>

    <?php while ($item = mysqli_fetch_assoc($equipment)) { ?>
        <p>
            <img src="<?php echo htmlspecialchars(getAssetImage('items', $item['icon'])); ?>" width="32">

            <?php echo htmlspecialchars($item['name']); ?>
            +<?php echo $item['enhancement_level']; ?>
        </p>
    <?php } ?>

    <a href="adventure.php?character_id=<?php echo $character_id; ?>">
        Go Back
    </a>

</body>

</html>

<?php mysqli_close($conn); ?>