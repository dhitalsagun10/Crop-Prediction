<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    // $encryptedPassword = password_hash($Password, PASSWORD_DEFAULT);
    $connection = mysqli_connect("localhost", "root", "", "cps_database");
    $query = "INSERT INTO users(username,email,password) VALUES ('$username','$email','$hashedPassword')";
    $result = mysqli_query($connection, $query);
    if ($result) {
        echo "sign in successfully !!";
        // header("Location:index.php");
    } else {
        echo "error!!";
    }
}
