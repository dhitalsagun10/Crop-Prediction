<?php
// Database connection
$host = 'localhost';
$dbname = 'cps_database';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get search query from the request
$searchQuery = $_GET['query'] ?? '';

// Search crops and regions
$stmt = $pdo->prepare("
    (SELECT name FROM crops WHERE name LIKE :query)
    UNION
    (SELECT name FROM regions WHERE name LIKE :query)
");
$stmt->execute(['query' => "%$searchQuery%"]);
$results = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Return results as JSON
header('Content-Type: application/json');
echo json_encode($results);
?>