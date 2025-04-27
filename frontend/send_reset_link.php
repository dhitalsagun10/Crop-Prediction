<?php
session_start();

// Database connection
try {
    $db = new PDO('mysql:host=localhost;dbname=cps_database', 'username', 'password');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database
        $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $stmt->execute([$token, $expires, $user['id']]);
        
        // Create reset link
        $resetLink = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/reset_password.php?token=' . urlencode($token);
        
        // Send email (simplified - consider using PHPMailer in production)
        $subject = "Password Reset Request";
        $message = "Click this link to reset your password:\n\n$resetLink\n\nThis link expires in 1 hour.";
        $headers = "From: no-reply@" . $_SERVER['HTTP_HOST'];
        
        mail($email, $subject, $message, $headers);
    }
    
    // Store in session to show the message
    $_SESSION['reset_email_sent'] = true;
    header("Location: index.php#emailSent");
    exit();
}
?>