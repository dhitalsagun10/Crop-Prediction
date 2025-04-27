<?php
session_start();

// Authentication and admin check
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Database connection
require 'connection.php';
$connection = mysqli_connect("localhost", "root", "", "cps_database");

// Get admin data
$query = "SELECT * FROM users WHERE id=?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

// Get stats for dashboard
$users_count = mysqli_query($connection, "SELECT COUNT(*) FROM users")->fetch_row()[0];
$active_count = mysqli_query($connection, "SELECT COUNT(*) FROM users WHERE status='active'")->fetch_row()[0];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        
        .container {
            width: 90%;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 5px;
        }
        
        header {
            background: #2c3e50;
            color: #fff;
            padding: 20px;
            border-radius: 5px 5px 0 0;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .admin-info {
            background: #ecf0f1;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
        }
        
        .dashboard-content {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            flex: 1;
            background: #3498db;
            color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
        }
        
        .stat-card h3 {
            margin-top: 0;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
        }
        
        .admin-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .admin-nav a {
            display: inline-block;
            background: #2c3e50;
            color: #fff;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .admin-nav a:hover {
            background: #e74c3c;
        }
        
        .logout-btn {
            display: inline-block;
            background: #e74c3c;
            color: #fff;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: #c0392b;
        }
        
        footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .action-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        
        .action-card h3 {
            margin-top: 0;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Admin Dashboard</h1>
            <div>Welcome, <?php echo htmlspecialchars($admin['username']); ?> (Admin)</div>
        </header>
        
        <div class="admin-info">
            <div>
                <p><strong>Admin ID:</strong> <?php echo $admin['id']; ?></p>
                <p><strong>Last Login:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
            <div>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($admin['email'] ?? 'Not provided'); ?></p>
                <p><strong>Account Created:</strong> <?php echo date('Y-m-d', strtotime($admin['created_at'] ?? 'now')); ?></p>
            </div>
        </div>
        
        <div class="admin-nav">
            <a href="users.php">Manage Users</a>
            <a href="settings.php">System Settings</a>
            <a href="reports.php">View Reports</a>
            <a href="logs.php">System Logs</a>
        </div>
        
        <div class="dashboard-content">
            <h2>System Overview</h2>
            
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="number"><?php echo $users_count; ?></div>
                </div>
                <div class="stat-card" style="background: #2ecc71;">
                    <h3>Active Users</h3>
                    <div class="number"><?php echo $active_count; ?></div>
                </div>
                <div class="stat-card" style="background: #e74c3c;">
                    <h3>Pending Requests</h3>
                    <div class="number">12</div>
                </div>
                <div class="stat-card" style="background: #f39c12;">
                    <h3>System Health</h3>
                    <div class="number">98%</div>
                </div>
            </div>
            
            <h3>Quick Actions</h3>
            <div class="quick-actions">
                <div class="action-card">
                    <h3>Add New User</h3>
                    <p>Create a new user account</p>
                    <a href="add_user.php">Go</a>
                </div>
                <div class="action-card">
                    <h3>View Reports</h3>
                    <p>Analyze system usage</p>
                    <a href="reports.php">Go</a>
                </div>
                <div class="action-card">
                    <h3>Backup Database</h3>
                    <p>Create system backup</p>
                    <a href="backup.php">Go</a>
                </div>
                <div class="action-card">
                    <h3>System Settings</h3>
                    <p>Configure application</p>
                    <a href="settings.php">Go</a>
                </div>
            </div>
        </div>
        
        <a href="logout.php" class="logout-btn">Logout</a>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Your Company Name. All rights reserved.</p>
            <p>Admin Dashboard v1.0</p>
        </footer>
    </div>
</body>
</html>
<?php
mysqli_close($connection);
?>