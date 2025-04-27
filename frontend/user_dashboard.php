<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'connection.php';
$connection = mysqli_connect("localhost", "root", "", "cps_database");

// Get user data
$user_query = mysqli_prepare($connection, "SELECT * FROM users WHERE id=?");
mysqli_stmt_bind_param($user_query, "i", $_SESSION['user_id']);
mysqli_stmt_execute($user_query);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($user_query));

// Get user's recent crops (using your confirmed crops table structure)
$recent_crops_query = mysqli_query($connection, 
    "SELECT id, crop_name, crop_type, planting_date, harvest_date 
     FROM crops 
     WHERE user_id={$user['id']} 
     ORDER BY planting_date DESC 
     LIMIT 5"
);

// Get regions data (using your confirmed regions table structure)
$regions_query = mysqli_query($connection,
    "SELECT id, region_name, climate, soil_type
     FROM regions
     WHERE user_id={$user['id']}
     LIMIT 3"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-light: #4caf50;
            --secondary: #ff9800;
            --dark: #263238;
            --light: #f5f7fa;
            --gray: #90a4ae;
            --white: #ffffff;
            --danger: #f44336;
            --warning: #ffc107;
            --success: #8bc34a;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: var(--dark);
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--dark);
            color: var(--white);
            padding: 2rem 1.5rem;
            position: fixed;
            height: 100vh;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header img {
            width: 40px;
            margin-right: 10px;
        }
        
        .sidebar-menu {
            margin-top: 2rem;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--gray);
            text-decoration: none;
        }
        
        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
            color: var(--white);
        }
        
        .menu-item i {
            margin-right: 12px;
            font-size: 1.1rem;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            transition: all 0.3s;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
        }
        
        .user-profile img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 1rem;
            object-fit: cover;
        }
        
        .user-info h3 {
            font-weight: 600;
            margin-bottom: 0.2rem;
        }
        
        .user-info p {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .notification-bell {
            position: relative;
            font-size: 1.3rem;
            color: var(--gray);
            cursor: pointer;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 1.5rem;
        }
        
        .card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .card-title {
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .card-actions {
            color: var(--gray);
            cursor: pointer;
        }
        
        /* Stats Cards */
        .stats-card {
            grid-column: span 3;
            display: flex;
            align-items: center;
        }
        
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.5rem;
        }
        
        .stats-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }
        
        .stats-info p {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        /* Main Cards */
        .main-card {
            grid-column: span 6;
        }
        
        /* Activity List */
        .activity-item {
            display: flex;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--primary);
        }
        
        .activity-details h4 {
            font-weight: 500;
            margin-bottom: 0.3rem;
        }
        
        .activity-details p {
            color: var(--gray);
            font-size: 0.8rem;
        }
        
        /* Prediction Cards */
        .prediction-card {
            display: flex;
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 8px;
            background: var(--light);
        }
        
        .prediction-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .prediction-details h4 {
            font-weight: 500;
            margin-bottom: 0.3rem;
        }
        
        .prediction-details p {
            color: var(--gray);
            font-size: 0.8rem;
        }
        
        .prediction-value {
            font-weight: 700;
            color: var(--primary);
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .stats-card {
                grid-column: span 6;
            }
            
            .main-card {
                grid-column: span 12;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
                padding: 1rem 0.5rem;
                overflow: hidden;
            }
            
            .sidebar-header span, .menu-item span {
                display: none;
            }
            
            .menu-item {
                justify-content: center;
                padding: 0.8rem 0;
            }
            
            .menu-item i {
                margin-right: 0;
                font-size: 1.3rem;
            }
            
            .main-content {
                margin-left: 80px;
            }
            
            .stats-card {
                grid-column: span 12;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="assets/logo-icon.png" alt="">
            <span>CPS</span>
        </div>
        
        <div class="sidebar-menu">
            <a href="#" class="menu-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
         
            </a>
            <a href="predictions.php" class="menu-item">
                <i class="fas fa-seedling"></i>
                <span>Crop Predictions</span>
            </a>
            <a href="fields.php" class="menu-item">
                <i class="fas fa-map-marked-alt"></i>
                <span>My Fields</span>
            </a>
            <a href="calendar.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Farming Calendar</span>
            </a>
            <a href="market.php" class="menu-item">
                <i class="fas fa-chart-line"></i>
                <span>Market Prices</span>
            </a>
            <a href="weather.php" class="menu-item">
                <i class="fas fa-cloud-sun"></i>
                <span>Weather Forecast</span>
            </a>
            <a href="settings.php" class="menu-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </div>
        
        <div style="position: absolute; bottom: 2rem; width: calc(100% - 3rem);">
            <a href="logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
        <div class="user-profile">
    <div class="user-icon" style="margin-right: 1rem;">
        <i class="fas fa-user-circle" style="font-size: 2rem; color: var(--primary);"></i>
    </div>
    <div class="user-info">
        <h3><?php echo htmlspecialchars($user['username']); ?></h3>
        
    </div>
</div>
            
            <div class="notification-bell">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <!-- Stats Cards -->
            <div class="card stats-card">
                <div class="stats-icon" style="background: rgba(46, 125, 50, 0.1); color: var(--primary);">
                    <i class="fas fa-seedling"></i>
                </div>
                <div class="stats-info">
                    <h3>12</h3>
                    <p>Active Crops</p>
                </div>
            </div>
            
            <div class="card stats-card">
                <div class="stats-icon" style="background: rgba(255, 152, 0, 0.1); color: var(--secondary);">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <div class="stats-info">
                    <h3>5</h3>
                    <p>Fields</p>
                </div>
            </div>
            
            <div class="card stats-card">
                <div class="stats-icon" style="background: rgba(139, 195, 74, 0.1); color: var(--success);">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stats-info">
                    <h3>3</h3>
                    <p>Pending Tasks</p>
                </div>
            </div>
            
            <div class="card stats-card">
                <div class="stats-icon" style="background: rgba(244, 67, 54, 0.1); color: var(--danger);">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stats-info">
                    <h3>2</h3>
                    <p>Alerts</p>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="card main-card">
                <div class="card-header">
                    <div class="card-title">Recent Activities</div>
                    <div class="card-actions">
                        <i class="fas fa-ellipsis-h"></i>
                    </div>
                </div>
                
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <div class="activity-details">
                            <h4>New crop prediction</h4>
                            <p>Rice suitability analysis for Chitwan</p>
                            <p>2 hours ago</p>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-tint"></i>
                        </div>
                        <div class="activity-details">
                            <h4>Irrigation scheduled</h4>
                            <p>Field A irrigation for tomorrow</p>
                            <p>5 hours ago</p>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="activity-details">
                            <h4>Purchase recorded</h4>
                            <p>Bought 5kg of maize seeds</p>
                            <p>Yesterday</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Crop Predictions -->
            <div class="card main-card">
                <div class="card-header">
                    <div class="card-title">Recent Predictions</div>
                    <div class="card-actions">
                        <i class="fas fa-ellipsis-h"></i>
                    </div>
                </div>
                
                <div class="prediction-list">
                    <div class="prediction-card">
                        <div class="prediction-icon">
                            <i class="fas fa-wheat"></i>
                        </div>
                        <div class="prediction-details">
                            <h4>Rice Prediction</h4>
                            <p>Chitwan District • 250m elevation</p>
                            <p>Success probability: <span class="prediction-value">87%</span></p>
                        </div>
                    </div>
                    
                    <div class="prediction-card">
                        <div class="prediction-icon">
                            <i class="fas fa-apple-alt"></i>
                        </div>
                        <div class="prediction-details">
                            <h4>Maize Prediction</h4>
                            <p>Kathmandu Valley • 1400m elevation</p>
                            <p>Success probability: <span class="prediction-value">72%</span></p>
                        </div>
                    </div>
                    
                    <div class="prediction-card">
                        <div class="prediction-icon">
                            <i class="fas fa-pepper-hot"></i>
                        </div>
                        <div class="prediction-details">
                            <h4>Chili Prediction</h4>
                            <p>Pokhara • 900m elevation</p>
                            <p>Success probability: <span class="prediction-value">65%</span></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Weather Widget -->
            <div class="card main-card">
                <div class="card-header">
                    <div class="card-title">Weather Forecast</div>
                    <div class="card-actions">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 0;">
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; color: var(--secondary);">
                            <i class="fas fa-sun"></i>
                        </div>
                        <h3>28°C</h3>
                        <p>Sunny</p>
                    </div>
                    
                    <div style="flex: 1; margin-left: 2rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Humidity</span>
                            <span>65%</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Rainfall</span>
                            <span>0mm</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Wind</span>
                            <span>10 km/h</span>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-top: 1rem;">
                    <div style="text-align: center;">
                        <p>Tomorrow</p>
                        <i class="fas fa-cloud-rain" style="font-size: 1.5rem; color: var(--gray); margin: 0.5rem 0;"></i>
                        <p>26°C</p>
                    </div>
                    <div style="text-align: center;">
                        <p>Wed</p>
                        <i class="fas fa-cloud" style="font-size: 1.5rem; color: var(--gray); margin: 0.5rem 0;"></i>
                        <p>25°C</p>
                    </div>
                    <div style="text-align: center;">
                        <p>Thu</p>
                        <i class="fas fa-sun" style="font-size: 1.5rem; color: var(--secondary); margin: 0.5rem 0;"></i>
                        <p>27°C</p>
                    </div>
                    <div style="text-align: center;">
                        <p>Fri</p>
                        <i class="fas fa-sun" style="font-size: 1.5rem; color: var(--secondary); margin: 0.5rem 0;"></i>
                        <p>29°C</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card main-card">
                <div class="card-header">
                    <div class="card-title">Quick Actions</div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; padding: 1rem 0;">
                    <button style="background: var(--light); border: none; border-radius: 8px; padding: 1rem; cursor: pointer; transition: all 0.3s;">
                        <i class="fas fa-plus" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.5rem;"></i>
                        <p>New Crop</p>
                    </button>
                    
                    <button style="background: var(--light); border: none; border-radius: 8px; padding: 1rem; cursor: pointer; transition: all 0.3s;">
                        <i class="fas fa-map-marked-alt" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.5rem;"></i>
                        <p>Add Field</p>
                    </button>
                    
                    <button style="background: var(--light); border: none; border-radius: 8px; padding: 1rem; cursor: pointer; transition: all 0.3s;">
                        <i class="fas fa-chart-line" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.5rem;"></i>
                        <p>Market Data</p>
                    </button>
                    
                    <button style="background: var(--light); border: none; border-radius: 8px; padding: 1rem; cursor: pointer; transition: all 0.3s;">
                        <i class="fas fa-book" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.5rem;"></i>
                        <p>Knowledge</p>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Animation for cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.5s ease ' + (index * 0.1) + 's';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 500);
            });
            
            // Notification bell animation
            const bell = document.querySelector('.notification-bell');
            bell.addEventListener('click', function() {
                this.style.transform = 'rotate(15deg)';
                setTimeout(() => {
                    this.style.transform = 'rotate(-15deg)';
                    setTimeout(() => {
                        this.style.transform = 'rotate(0)';
                    }, 200);
                }, 200);
            });
        });
        
        // Interactive quick action buttons
        const quickActions = document.querySelectorAll('.quick-actions button');
        quickActions.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
    </script>
</body>
</html>
<?php
mysqli_close($connection);
?>