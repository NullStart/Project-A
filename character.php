<?php
/**
 * displays characters owned by the current user.
 * select a character, create a new character or log out.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin($conn);

$user_id = getUserId();

$sql = "SELECT c.id, c.name, c.level, c.exp,
               c.gold, c.current_hp, c.current_mp,
               cl.name AS class_name
        FROM characters c, classes cl
        WHERE c.class_id = cl.id 
        AND c.user_id = ?
        ORDER BY c.id";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Character Selection - Idle Adventure</title>
</head>

<body>

    <h1>Idle Adventure</h1>

    <p>
        Welcome,
        <?php echo htmlspecialchars($_SESSION['username']); ?>
    </p>

    <form action="logout.php" method="POST">
        <input type="submit" value="Logout">
    </form>

    <h2>Select Character</h2>

    <?php if (mysqli_num_rows($result) == 0) { ?>

        <p>You don't have any characters yet.</p>

    <?php } else { ?>

        <?php while ($character = mysqli_fetch_assoc($result)) { ?>

            <fieldset>

                <legend>
                    <?php echo htmlspecialchars($character['name']); ?>
                </legend>

                <p>
                    Class:
                    <?php echo htmlspecialchars($character['class_name']); ?>
                </p>

                <p>
                    Level: <?php echo $character['level']; ?>
                </p>

                <p>
                    EXP: <?php echo $character['exp']; ?>
                </p>

                <p>
                    Gold: <?php echo $character['gold']; ?>
                </p>

                <p>
                    HP: <?php echo $character['current_hp']; ?>
                </p>

                <p>
                    MP: <?php echo $character['current_mp']; ?>
                </p>

                <form action="adventure.php" method="GET">

                    <input type="hidden" name="character_id"
                           value="<?php echo $character['id']; ?>">

                    <input type="submit" value="Play">

                </form>

            </fieldset>

        <?php } ?>

    <?php } ?>

    <p>
        <a href="create_character.php">Create New Character</a>
    </p>

</body>

</html>

<?php

mysqli_stmt_close($stmt);
mysqli_close($conn);

?>