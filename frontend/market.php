<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Sample market data (replace with actual API/database calls)
$market_data = [
    ['Rice', 'NRs. 80/kg', '+2%'],
    ['Maize', 'NRs. 60/kg', '-1%'],
    ['Wheat', 'NRs. 75/kg', '0%']
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Market Prices</title>
    <!-- Same CSS as crops.php -->
</head>
<body>
    <?php include('sidebar.php'); ?>
    
    <div class="main-content">
        <div class="container">
            <h1>Market Prices</h1>
            
            <div class="card">
                <h2>Current Prices (Kathmandu)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Crop</th>
                            <th>Price</th>
                            <th>Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($market_data as $item): ?>
                        <tr>
                            <td><?= $item[0] ?></td>
                            <td><?= $item[1] ?></td>
                            <td><?= $item[2] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>Price Trends</h2>
                <div id="price-chart" style="height: 300px;"></div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('price-chart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [
                    {
                        label: 'Rice',
                        data: [75, 78, 82, 80, 85, 83],
                        borderColor: '#4CAF50'
                    },
                    {
                        label: 'Maize',
                        data: [55, 58, 60, 62, 59, 60],
                        borderColor: '#FF9800'
                    }
                ]
            }
        });
    </script>
</body>
</html>