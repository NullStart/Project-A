<?php
/**
 * handles account registration and input validation.
 * checks for duplicate accounts and stores passwords securely
 * using password hashing.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.html');
    exit();
}

require_once __DIR__ . '/includes/db.php';

$username = trim($_POST['username'] ?? '');
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$errors = [];

//input validation

if (strlen($username) < 3 || strlen($username) > 50) {
    $errors[] = 'Username must be between 3 and 50 characters.';
}
// allow only letters, numbers, and underscores use regular expression
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = 'Username can only contain letters, numbers and underscores.';
}

if (!$email || strlen($email) > 255) {
    $errors[] = 'Invalid email address.';
}
// bcrypt password limit of 72 bytes
if (strlen($password) < 8 || strlen($password) > 72) {
    $errors[] = 'Password must be between 8 and 72 character.';
}

if ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match.';
}

if (count($errors) > 0) {

    echo '<h2>Registration Failed</h2>';
    echo '<ul>';

    foreach ($errors as $error) {
        echo '<li>' . htmlspecialchars($error) . '</li>';
    }

    echo '</ul>';
    echo '<a href="register.html">Go Back</a>';

    exit();
}

$sql = "SELECT id
        FROM users
        WHERE username = ? OR email = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ss", $username, $email);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo '<h2>Registration Failed</h2>';
    echo '<p>Username or email already exists.</p>';
    echo '<a href="register.html">Go Back</a>';

    exit();
}

mysqli_stmt_close($stmt);

//Hash password

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, email, password_hash)
        VALUES (?, ?, ?)";

try {

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt, "sss", $username, $email, $password_hash
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

} catch (mysqli_sql_exception $e) {

    if ($e->getCode() == 1062) {
        echo '<p>Username or email already exists.</p>';
    } else {
        error_log($e->getMessage());
        echo '<p>Registration failed. Please try again.</p>';
    }

    echo '<a href="register.html">Go Back</a>';

    mysqli_close($conn);
    exit();
}

mysqli_close($conn);

header('Location: login.html?registered=1');
exit();