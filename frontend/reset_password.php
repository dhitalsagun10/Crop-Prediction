<?php
session_start();
require_once 'connection.php';

$token = $_GET['token'] ?? '';
$pdo = getPDO();

// Verify token against hashed version in database
$stmt = $pdo->prepare("SELECT id, reset_token_hash FROM users WHERE reset_token_hash IS NOT NULL AND reset_expires > NOW()");
$stmt->execute();
$users = $stmt->fetchAll();

$validUser = null;
foreach ($users as $user) {
    if (password_verify($token, $user['reset_token_hash'])) {
        $validUser = $user;
        break;
    }
}

if (!$validUser) {
    die("Invalid or expired token. <a href='forgot_password.php'>Request new link</a>");
}

// Store token in session for the form
$_SESSION['reset_token'] = $token;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <h1>Create New Password</h1>
    <form action="process_reset.php" method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Reset Password</button>
    </form>
</body>
</html>