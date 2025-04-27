<?php
function getPDO() {
    $host = 'localhost';
    $db   = 'cps_database';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';
    $port = 3306; // Default MySQL port (3307 for XAMPP)

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        error_log("PDO Connection Error: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}
?>