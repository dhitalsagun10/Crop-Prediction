<?php
session_start();
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getPDO();
    $token = $_POST['token'];
    $password = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    // Validate
    if ($password !== $confirm) {
        die("Passwords don't match");
    }

// After successful password reset, clear the token
$stmt = $pdo->prepare("UPDATE users SET password=?, reset_token_hash=NULL, reset_expires=NULL WHERE id=?");
$stmt->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    
    if ($user = $stmt->fetch()) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
        $stmt->execute([$hash, $user['id']]);
        
        echo "Password updated successfully! <a href='login.php'>Login</a>";
    } else {
        die("Invalid or expired token");
    }
}