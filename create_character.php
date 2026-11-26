<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin($conn);


// Get classes

$sql = "SELECT id, name, description,
               base_hp, base_mp, base_attack,
               base_defense, base_speed,
               base_crit_rate, base_crit_damage
        FROM classes
        ORDER BY id";

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create Character - Idle Adventure</title>
</head>

<body>

    <h1>Idle Adventure</h1>
    <h2>Create Character</h2>

    <form action="actions/create_character.php" method="POST">

        <div>
            <label for="name">Character Name:</label>

            <input type="text" id="name" name="name"
                   required>
        </div>

        <h3>Select Class</h3>

        <?php while ($class = mysqli_fetch_assoc($result)) { ?>

            <fieldset>

                <legend>
                    <?php echo htmlspecialchars($class['name']); ?>
                </legend>

                <p>
                    <?php echo htmlspecialchars($class['description'] ?? ''); ?>
                </p>

                <p>HP: <?php echo $class['base_hp']; ?></p>

                <p>MP: <?php echo $class['base_mp']; ?></p>

                <p>Attack: <?php echo $class['base_attack']; ?></p>

                <p>Defense: <?php echo $class['base_defense']; ?></p>

                <p>Speed: <?php echo $class['base_speed']; ?></p>

                <p>
                    Critical Rate:
                    <?php echo $class['base_crit_rate'] * 100; ?>%
                </p>

                <p>
                    Critical Damage:
                    <?php echo $class['base_crit_damage'] * 100; ?>%
                </p>

                <label>
                    <input type="radio" name="class_id"
                           value="<?php echo $class['id']; ?>"
                           required>

                    Select <?php echo htmlspecialchars($class['name']); ?>
                </label>

            </fieldset>

        <?php } ?>

        <input type="submit" value="Create Character">

    </form>

    <p>
        <a href="character.php">Go Back</a>
    </p>

</body>

</html>

<?php

mysqli_free_result($result);
mysqli_close($conn);

?>