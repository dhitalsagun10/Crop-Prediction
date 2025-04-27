<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        echo "Email cannot be empty.";
        exit; // Stop further execution
    }

    // Database connection
    $connection = mysqli_connect("localhost", "root", "", "cps_database");

    if (!$connection) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Insert query for subscriptions table
    $query = "INSERT INTO subscriptions (email) VALUES ('$email')";
    
    if (mysqli_query($connection, $query)) {
        echo "Subscription successful!";
    } else {
        echo "Error: " . mysqli_error($connection);
    }

    // Close connection
    mysqli_close($connection);
}
?>
