<?php
$base_url = 'http://localhost/Crop%20Prediction%20System/frontend/';
ob_start(); // Start output buffering
session_start();
require 'connection.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];


    $connection = mysqli_connect("localhost", "root", "", "cps_database");
    

    // Check if user exists
    $query = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($connection, $query);


    
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        // Verify password (plain text comparison - INSECURE, only for example)
        if (password_verify($password, $user['password'])) { {
                session_start();
                $_SESSION['user_id'] = $user['id'];
                header("Location: http://localhost/Crop%20Prediction%20System/frontend/user_dashboard.php");
                $_SESSION['username'] = $user['username'];

                // Successful login
            }

            echo "Invalid password!";
        }
    } else {
        echo "User not found!";
    }

    mysqli_close($connection);
}
