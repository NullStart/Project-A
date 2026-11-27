<?php
/**
 * displays character stats, available zones, and current battle progress.
 * provides controls for starting, stopping, and updating adventures
 * as well as reviving dead characters.
 */
// TODO: Add automatic battle update
// TODO: Add progress bars for health, mana, and experience points
// TODO: Add battle log
// TODO: Add death notification
// TODO：AAD maybe shop to sell some item
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/simulation/controller.php';
require_once __DIR__ . '/includes/assets.php';

requireLogin($conn);

$user_id = getUserId();

$character_id = filter_input(INPUT_GET, 'character_id', FILTER_VALIDATE_INT);

$character = getCharacter($conn, $character_id, $user_id);

if (!$character) {
    header('Location: character.php');
    exit();
}
//Get character and adventure
$stats = getCharacterStats($conn, $character);

$sql = "SELECT *
        FROM adventure_state
        WHERE character_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $character_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$adventure = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);
// default to idle before the first adventure
$status = $adventure['status'] ?? 'idle';

$zones = mysqli_query($conn, "SELECT * FROM zones");

// reuse the same token throughout the session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Idle Adventure</title>
</head>

<body>

    <h1>Idle Adventure v0.1.0</h1>

    <a href="character.php">Character Selection</a>
    <a href="inventory.php?character_id=<?php echo $character_id; ?>">Inventory</a>
    <h2><?php echo htmlspecialchars($character['name']); ?></h2>

    <p>Class: <?php echo htmlspecialchars($character['class_name']); ?></p>

    <p>Level: <?php echo $character['level']; ?></p>

    <p>EXP: <?php echo $character['exp']; ?></p>

    <p>Gold: <?php echo $character['gold']; ?></p>

    <p>HP: <?php echo $character['current_hp']; ?> / <?php echo $stats['max_hp']; ?></p>

    <p>MP: <?php echo $character['current_mp']; ?> / <?php echo $stats['max_mp']; ?></p>
    <p>Attack: <?php echo $stats['attack']; ?></p>

    <p>Defense: <?php echo $stats['defense']; ?></p>

    <p>Speed: <?php echo $stats['speed']; ?></p>

    <hr>
    
    <h2>Adventure</h2>

    <p>Status: <?php echo htmlspecialchars($status); ?></p>

    <?php if ($status === 'active') { ?>

        <form action="actions/stop_adventure.php" method="POST">

            <input type="hidden" name="character_id"
                   value="<?php echo $character_id; ?>">

            <input type="hidden" name="csrf_token"
                   value="<?php echo $csrf_token; ?>">

            <input type="submit" value="Stop Adventure">

        </form>

    <?php } elseif ($character['current_hp'] > 0) { ?>

        <form action="actions/start_adventure.php" method="POST">

            <input type="hidden" name="character_id"
                   value="<?php echo $character_id; ?>">

            <input type="hidden" name="csrf_token"
                   value="<?php echo $csrf_token; ?>">

            <select name="zone_id">

                <?php while ($zone = mysqli_fetch_assoc($zones)) { ?>

                    <?php if ($character['level'] >= $zone['required_level']) { ?>

                        <option value="<?php echo $zone['id']; ?>">
                            <?php echo htmlspecialchars($zone['name']); ?>
                        </option>

                    <?php } ?>

                <?php } ?>

            </select>

            <input type="submit" value="Start Adventure">

        </form>

    <?php } else { ?>

        <p>Your character is dead.</p>

        <form action="actions/revive.php" method="POST">

            <input type="hidden" name="character_id"
                value="<?php echo $character_id; ?>">

            <input type="hidden" name="csrf_token"
                value="<?php echo $csrf_token; ?>">

            <input type="submit" value="Revive">

        </form>

    <?php } ?>

    <hr>

    <h2>Battle</h2>

    <?php if ($adventure && $status === 'active') { ?>

        <?php $state = json_decode($adventure['state_data'], true); ?>

        <?php if ($state['battle'] !== null) { ?>

            <?php foreach ($state['battle']['enemies'] as $enemy) { ?>
                <div>
                    <img src="<?php echo htmlspecialchars(getAssetImage('monsters', $enemy['sprite'] ?? null)); ?>"
                        alt="<?php echo htmlspecialchars($enemy['name']); ?>"
                        width="80">
                    <p>
                        <?php echo htmlspecialchars($enemy['name']); ?>
                        #<?php echo $enemy['instance_id']; ?>

                        HP: <?php echo $enemy['current_hp']; ?>
                        / <?php echo $enemy['max_hp']; ?>
                    </p>
                </div>
                <?php } ?>
        <?php } else { ?>

            <p>Waiting for next encounter.</p>

        <?php } ?>

        <p>Total Kills: <?php echo $state['total_kills']; ?></p>

        <p>Battles Won: <?php echo $state['total_battles']; ?></p>

        <form action="actions/update_adventure.php" method="POST">

            <input type="hidden" name="character_id"
                   value="<?php echo $character_id; ?>">

            <input type="hidden" name="csrf_token"
                   value="<?php echo $csrf_token; ?>">

            <input type="submit" value="Refresh Battle">

        </form>

    <?php } ?>

</body>

</html>
<?php
mysqli_close($conn);
?>