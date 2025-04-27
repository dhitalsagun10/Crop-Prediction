<?php
session_start();
require_once 'connection.php';
require_once 'mailer.php'; // We'll create this next

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getPDO();
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $ip = $_SERVER['REMOTE_ADDR'];

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($user = $stmt->fetch()) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store hashed token in database
        $stmt = $pdo->prepare("UPDATE users SET reset_token_hash=?, reset_expires=?, reset_ip=? WHERE id=?");
        $stmt->execute([$tokenHash, $expires, $_SERVER['REMOTE_ADDR'], $user['id']]);
        
        // Send the original (unhashed) token in the email
        $resetLink = "http://localhost/reset_password.php?token=".urlencode($token);
        
        // Send email
        if (sendResetEmail($email, $resetLink)) {
            $_SESSION['reset_email_sent'] = true;
        } else {
            $_SESSION['reset_error'] = "Failed to send email";
        }
    } else {
        $_SESSION['reset_email_sent'] = true; // Fake success for security
    }
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Display logic
$resetEmailSent = $_SESSION['reset_email_sent'] ?? false;
$resetError = $_SESSION['reset_error'] ?? null;
unset($_SESSION['reset_email_sent'], $_SESSION['reset_error']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Password Reset</title>
    <style>
        .form-container { max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .hidden { display: none; }
        .error { color: red; }
    </style>
</head>
<body>

<section id="forgotPassword" class="form-container <?= $resetEmailSent ? 'hidden' : '' ?>">
    <h1>Reset Password</h1>
    <?php if ($resetError): ?>
        <p class="error"><?= htmlspecialchars($resetError) ?></p>
    <?php endif; ?>
    <form method="POST">
        <input type="email" name="email" placeholder="Your email" required>
        <button type="submit">Send Reset Link</button>
    </form>
</section>

<section id="emailSent" class="form-container <?= $resetEmailSent ? '' : 'hidden' ?>">
    <h1>Check Your Email</h1>
    <p>If this email exists in our system, we've sent a reset link.</p>
</section>

</body>
</html>