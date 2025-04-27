<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'connection.php';

// Add new field
if(isset($_POST['add_field'])) {
    $stmt = mysqli_prepare($connection, 
        "INSERT INTO regions (user_id, region_name, climate, soil_type) 
         VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isss", 
        $_SESSION['user_id'],
        $_POST['region_name'],
        $_POST['climate'],
        $_POST['soil_type']
    );
    mysqli_stmt_execute($stmt);
}

// Get user's fields
$fields = mysqli_query($connection, 
    "SELECT * FROM regions WHERE user_id={$_SESSION['user_id']}"
);
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Fields</title>
    <!-- Same CSS as crops.php -->
</head>
<body>
    <?php include('sidebar.php'); ?>
    
    <div class="main-content">
        <div class="container">
            <h1>My Fields</h1>
            
            <div class="card">
                <h2>Add New Field</h2>
                <form method="POST">
                    <input type="text" name="region_name" placeholder="Field Name" required>
                    <select name="climate" required>
                        <option value="Tropical">Tropical</option>
                        <option value="Temperate">Temperate</option>
                        <option value="Arid">Arid</option>
                    </select>
                    <select name="soil_type" required>
                        <option value="Loamy">Loamy</option>
                        <option value="Clay">Clay</option>
                        <option value="Sandy">Sandy</option>
                    </select>
                    <button type="submit" name="add_field" class="btn">Add Field</button>
                </form>
            </div>
            
            <div class="card">
                <h2>My Field List</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Field Name</th>
                            <th>Climate</th>
                            <th>Soil Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($field = mysqli_fetch_assoc($fields)): ?>
                        <tr>
                            <td><?= htmlspecialchars($field['region_name']) ?></td>
                            <td><?= htmlspecialchars($field['climate']) ?></td>
                            <td><?= htmlspecialchars($field['soil_type']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>