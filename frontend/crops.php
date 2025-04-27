<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'connection.php';

// Add new crop
if(isset($_POST['add_crop'])) {
    $stmt = mysqli_prepare($connection, 
        "INSERT INTO crops (user_id, crop_name, crop_type, planting_date, harvest_date) 
         VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "issss", 
        $_SESSION['user_id'],
        $_POST['crop_name'],
        $_POST['crop_type'],
        $_POST['planting_date'],
        $_POST['harvest_date']
    );
    mysqli_stmt_execute($stmt);
}

// Get user's crops
$crops = mysqli_query($connection, 
    "SELECT * FROM crops WHERE user_id={$_SESSION['user_id']} ORDER BY planting_date DESC"
);
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Crops</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reuse your dashboard CSS styles */
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 2rem; }
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); padding: 2rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        .btn { background: #2e7d32; color: white; padding: 10px 15px; border-radius: 6px; text-decoration: none; }
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>
    
    <div class="main-content">
        <div class="container">
            <h1>My Crops</h1>
            
            <div class="card">
                <h2>Add New Crop</h2>
                <form method="POST">
                    <input type="text" name="crop_name" placeholder="Crop Name" required>
                    <select name="crop_type" required>
                        <option value="Cereal">Cereal</option>
                        <option value="Vegetable">Vegetable</option>
                        <option value="Fruit">Fruit</option>
                    </select>
                    <input type="date" name="planting_date" required>
                    <input type="date" name="harvest_date">
                    <button type="submit" name="add_crop" class="btn">Add Crop</button>
                </form>
            </div>
            
            <div class="card">
                <h2>My Crop List</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Crop Name</th>
                            <th>Type</th>
                            <th>Planting Date</th>
                            <th>Harvest Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($crop = mysqli_fetch_assoc($crops)): ?>
                        <tr>
                            <td><?= htmlspecialchars($crop['crop_name']) ?></td>
                            <td><?= htmlspecialchars($crop['crop_type']) ?></td>
                            <td><?= $crop['planting_date'] ?></td>
                            <td><?= $crop['harvest_date'] ?: 'Not set' ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>