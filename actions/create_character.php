<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../create_character.php');
    exit();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin($conn, '../login.html');

$user_id = getUserId();

$name = filter_input(INPUT_POST, 'name');

$class_id = filter_input(
    INPUT_POST, 'class_id', FILTER_VALIDATE_INT
);


//Input

$name = trim($name ?? '');

if (empty($name) || !$class_id) {
    echo '<p>Character name and class are required.</p>';
    echo '<a href="../create_character.php">Go Back</a>';
    exit();
}


//Check class

$sql = "SELECT base_hp, base_mp
        FROM classes
        WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $class_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$class = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$class) {
    echo '<p>Invalid class.</p>';
    echo '<a href="../create_character.php">Go Back</a>';
    exit();
}


// Check character name

$sql = "SELECT id
        FROM characters
        WHERE name = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "s", $name);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    mysqli_stmt_close($stmt);

    echo '<p>Character name already exists.</p>';
    echo '<a href="../create_character.php">Go Back</a>';
    exit();
}

mysqli_stmt_close($stmt);


// Initial attributes

$current_hp = $class['base_hp'];
$current_mp = $class['base_mp'];


//Create character

$sql = "INSERT INTO characters
            (user_id, class_id, name, current_hp, current_mp)
        VALUES (?, ?, ?, ?, ?)";

try {

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt, "iisii",
        $user_id,
        $class_id,
        $name,
        $current_hp,
        $current_mp
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

} catch (mysqli_sql_exception $e) {

    if ($e->getCode() == 1062) {
        echo '<p>Character name already exists.</p>';
    } else {
        error_log($e->getMessage());
        echo '<p>Character creation failed.</p>';
    }

    echo '<a href="../create_character.php">Go Back</a>';
    exit();
}


// 06 Redirect

mysqli_close($conn);

header('Location: ../character.php');
exit();