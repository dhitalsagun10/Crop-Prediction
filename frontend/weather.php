<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Sample weather data (replace with actual API calls)
$weather_data = [
    ['Today', 'Sunny', '28°C', '10%'],
    ['Tomorrow', 'Partly Cloudy', '26°C', '30%'],
    ['Wed', 'Rain', '22°C', '80%']
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Weather Forecast</title>
    <!-- Same CSS as crops.php -->
</head>
<body>
    <?php include('sidebar.php'); ?>
    
    <div class="main-content">
        <div class="container">
            <h1>Weather Forecast</h1>
            
            <div class="card">
                <h2>7-Day Forecast</h2>
                <div class="weather-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                    <?php foreach($weather_data as $day): ?>
                    <div class="weather-card" style="text-align: center; padding: 1rem; background: #f5f7fa; border-radius: 8px;">
                        <h3><?= $day[0] ?></h3>
                        <p><?= $day[1] ?></p>
                        <p style="font-size: 1.5rem; font-weight: bold;"><?= $day[2] ?></p>
                        <p>Rain: <?= $day[3] ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="card">
                <h2>Weather Alerts</h2>
                <div class="alert" style="padding: 1rem; background: #FFF3E0; border-left: 4px solid #FF9800;">
                    <h3 style="color: #E65100;">Monsoon Advisory</h3>
                    <p>Expect heavy rainfall in your region from July 15-20. Plan irrigation accordingly.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>