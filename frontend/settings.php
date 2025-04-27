<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'connection.php';

// Update settings
if(isset($_POST['update_settings'])) {
    $stmt = mysqli_prepare($connection, 
        "INSERT INTO user_settings (user_id, notification_pref, language) 
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE 
            notification_pref = VALUES(notification_pref),
            language = VALUES(language)");
    mysqli_stmt_bind_param($stmt, "iss", 
        $_SESSION['user_id'],
        $_POST['notification_pref'],
        $_POST['language']
    );
    mysqli_stmt_execute($stmt);
}

// Get user settings
$settings = mysqli_fetch_assoc(mysqli_query($connection, 
    "SELECT * FROM user_settings WHERE user_id={$_SESSION['user_id']}"
));
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Settings</title>
    <!-- Same CSS as crops.php -->
</head>
<body>
    <?php include('sidebar.php'); ?>
    
    <div class="main-content">
        <div class="container">
            <h1>System Settings</h1>
            
            <div class="card">
                <h2>Notification Preferences</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Notification Frequency</label>
                        <select name="notification_pref">
                            <option value="daily" <?= ($settings['notification_pref'] ?? '') == 'daily' ? 'selected' : '' ?>>Daily</option>
                            <option value="weekly" <?= ($settings['notification_pref'] ?? '') == 'weekly' ? 'selected' : '' ?>>Weekly</option>
                            <option value="none" <?= ($settings['notification_pref'] ?? '') == 'none' ? 'selected' : '' ?>>None</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Language</label>
                        <select name="language">
                            <option value="en" <?= ($settings['language'] ?? '') == 'en' ? 'selected' : '' ?>>English</option>
                            <option value="ne" <?= ($settings['language'] ?? '') == 'ne' ? 'selected' : '' ?>>Nepali</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="update_settings" class="btn">Save Settings</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>