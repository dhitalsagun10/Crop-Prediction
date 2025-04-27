<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cps_database');

// Establish database connection
try {
    $connection = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get user data
$user_query = $connection->prepare("SELECT id, username, email FROM users WHERE id = :user_id");
$user_query->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);

try {
    $user_query->execute();
    $user = $user_query->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("User not found");
    }
} catch (PDOException $e) {
    die("Error fetching user data: " . $e->getMessage());
}

// Get prediction data from the new predictions table
$prediction_query = $connection->prepare("
    SELECT 
        p.id,
        p.prediction_date,
        p.temperature,
        p.rainfall,
        p.soil_ph,
        p.probability,
        p.is_suitable,
        p.actual_yield,
        p.harvest_date,
        c.crop_name,
        c.crop_type,
        r.region_name,
        r.climate,
        r.soil_type,
        r.altitude
    FROM predictions p
    JOIN crops c ON p.crop_id = c.id
    JOIN regions r ON p.region_id = r.id
    WHERE p.user_id = :user_id
    ORDER BY p.prediction_date DESC
    LIMIT 6
");
$prediction_query->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);

try {
    $prediction_query->execute();
    $predictions = $prediction_query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching predictions: " . $e->getMessage());
}

// Get crops for dropdown
$crops_query = $connection->prepare("
    SELECT id, crop_name, crop_type FROM crops WHERE user_id = :user_id ORDER BY crop_name
");
$crops_query->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);

try {
    $crops_query->execute();
    $crops = $crops_query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching crops: " . $e->getMessage());
}

// Get regions for dropdown with Nepal-specific data
$regions_query = $connection->prepare("
    SELECT id, region_name, climate, soil_type, altitude FROM regions WHERE user_id = :user_id ORDER BY region_name
");
$regions_query->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);

try {
    $regions_query->execute();
    $regions = $regions_query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching regions: " . $e->getMessage());
}

// Enhanced Nepal-specific prediction calculation
function calculatePrediction($crop_type, $climate, $soil_type, $altitude, $temperature, $rainfall, $soil_ph) {
    $base_score = 50;
    
    // Nepal altitude adjustment
    if ($altitude < 1000) { // Terai region
        $base_score += 10;
        if (in_array($crop_type, ['Rice', 'Sugarcane', 'Jute'])) {
            $base_score += 15;
        }
    } elseif ($altitude >= 1000 && $altitude < 2000) { // Hill region
        $base_score += 5;
        if (in_array($crop_type, ['Maize', 'Wheat', 'Potato'])) {
            $base_score += 15;
        }
    } else { // Mountain region
        $base_score -= 5;
        if (in_array($crop_type, ['Barley', 'Buckwheat', 'Apple'])) {
            $base_score += 20;
        }
    }
    
    // Nepal monsoon season adjustment
    $current_month = date('n');
    $is_monsoon_season = ($current_month >= 6 && $current_month <= 9);
    if ($is_monsoon_season && in_array($crop_type, ['Rice', 'Maize'])) {
        $base_score += 15;
    }
    
    // Temperature adjustment for Nepal
    if ($temperature >= 20 && $temperature <= 30) {
        $base_score += 15;
    } elseif ($temperature >= 15 && $temperature <= 35) {
        $base_score += 10;
    } else {
        $base_score -= 10;
    }
    
    // Rainfall adjustment for Nepal
    if ($rainfall >= 1000 && $rainfall <= 2500) { // Typical monsoon rainfall
        $base_score += 15;
    } elseif ($rainfall >= 500 && $rainfall <= 3000) {
        $base_score += 10;
    } else {
        $base_score -= 10;
    }
    
    // Soil pH adjustment for Nepal
    if ($soil_ph >= 5.5 && $soil_ph <= 7.0) {
        $base_score += 15;
    } elseif ($soil_ph >= 5.0 && $soil_ph <= 7.5) {
        $base_score += 10;
    } else {
        $base_score -= 10;
    }
    
    // Convert to probability (0-1 range)
    $probability = min(max($base_score / 100, 0), 1);
    
    return [
        'probability' => round($probability, 2),
        'suitable' => $probability >= 0.5,
        'nepal_factors' => [
            'altitude_effect' => $altitude < 1000 ? 'Terai Advantage' : ($altitude < 2000 ? 'Hill Moderate' : 'Mountain Challenge'),
            'season_effect' => $is_monsoon_season ? 'Monsoon Boost' : 'Normal Season'
        ]
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $temperature = $_POST['temperature'] ?? null;
    $rainfall = $_POST['rainfall'] ?? null;
    $soil_ph = $_POST['soil_ph'] ?? null;
    $crop_id = $_POST['crop_id'] ?? null;
    $region_id = $_POST['region_id'] ?? null;
    
    // Get crop and region details
    $crop_details = $connection->prepare("SELECT crop_type FROM crops WHERE id = :crop_id");
    $crop_details->bindParam(':crop_id', $crop_id, PDO::PARAM_INT);
    $crop_details->execute();
    $crop = $crop_details->fetch(PDO::FETCH_ASSOC);
    
    $region_details = $connection->prepare("SELECT climate, soil_type, altitude FROM regions WHERE id = :region_id");
    $region_details->bindParam(':region_id', $region_id, PDO::PARAM_INT);
    $region_details->execute();
    $region = $region_details->fetch(PDO::FETCH_ASSOC);
    
    // Calculate prediction with Nepal-specific factors
    $prediction_result = calculatePrediction(
        $crop['crop_type'],
        $region['climate'],
        $region['soil_type'],
        $region['altitude'],
        $temperature,
        $rainfall,
        $soil_ph
    );
    
    // Store prediction in database
    $insert_query = $connection->prepare("
        INSERT INTO predictions (
            user_id,
            crop_id,
            region_id,
            temperature,
            rainfall,
            soil_ph,
            probability,
            is_suitable,
            prediction_date
        ) VALUES (
            :user_id,
            :crop_id,
            :region_id,
            :temperature,
            :rainfall,
            :soil_ph,
            :probability,
            :is_suitable,
            NOW()
        )
    ");
    
    $insert_query->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $insert_query->bindParam(':crop_id', $crop_id, PDO::PARAM_INT);
    $insert_query->bindParam(':region_id', $region_id, PDO::PARAM_INT);
    $insert_query->bindParam(':temperature', $temperature);
    $insert_query->bindParam(':rainfall', $rainfall);
    $insert_query->bindParam(':soil_ph', $soil_ph);
    $insert_query->bindParam(':probability', $prediction_result['probability']);
    $insert_query->bindParam(':is_suitable', $prediction_result['suitable'], PDO::PARAM_BOOL);
    
    try {
        $insert_query->execute();
        $prediction_id = $connection->lastInsertId();
        
        // Store in session for display
        $_SESSION['prediction_result'] = $prediction_result;
        $_SESSION['prediction_inputs'] = [
            'temperature' => $temperature,
            'rainfall' => $rainfall,
            'soil_ph' => $soil_ph,
            'crop_type' => $crop['crop_type'],
            'climate' => $region['climate'],
            'soil_type' => $region['soil_type'],
            'altitude' => $region['altitude'],
            'nepal_factors' => $prediction_result['nepal_factors']
        ];
        
        header("Location: predictions.php");
        exit();
    } catch (PDOException $e) {
        die("Error saving prediction: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crop Prediction Analytics | CPS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c7be5;
            --primary-light: #4e9af1;
            --primary-dark: #1a68c7;
            --secondary: #00b894;
            --danger: #ff7675;
            --warning: #fdcb6e;
            --info: #6c5ce7;
            --dark: #2d3436;
            --light: #f5f6fa;
            --gray: #636e72;
            --light-gray: #dfe6e9;
            --white: #ffffff;
            --success-bg: #55efc4;
            --success-text: #00b894;
            --warning-bg: #ffeaa7;
            --warning-text: #fdcb6e;
            --danger-bg: #ff7675;
            --danger-text: #d63031;
            --nepal-green: #00b894;
            --nepal-orange: #e17055;
            --nepal-blue: #0984e3;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: var(--nepal-blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .user-info h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--dark);
        }

        .user-info p {
            font-size: 0.875rem;
            color: var(--gray);
        }

        .nav-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary);
            color: white;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        @media (min-width: 992px) {
            .content-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .card {
            background-color: var(--white);
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }

        .prediction-form-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 123, 229, 0.1);
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .prediction-result {
            margin-top: 2rem;
            padding: 1.5rem;
            background-color: var(--light);
            border-radius: 0.5rem;
        }

        .result-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark);
            text-align: center;
        }

        .probability-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0.5rem 0;
            text-align: center;
        }

        .suitability-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            font-weight: 600;
            margin-top: 1rem;
            text-align: center;
            width: 100%;
        }

        .suitable {
            background-color: var(--success-bg);
            color: var(--success-text);
        }

        .not-suitable {
            background-color: var(--danger-bg);
            color: var(--danger-text);
        }

        .nepal-factor-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .terai {
            background-color: #55efc4;
            color: #00b894;
        }

        .hill {
            background-color: #74b9ff;
            color: #0984e3;
        }

        .mountain {
            background-color: #a29bfe;
            color: #6c5ce7;
        }

        .monsoon {
            background-color: #81ecec;
            color: #00cec9;
        }

        .predictions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .prediction-card {
            background-color: var(--white);
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .prediction-card:hover {
            transform: translateY(-5px);
        }

        .prediction-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background-color: var(--primary);
        }

        .prediction-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .prediction-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--dark);
        }

        .prediction-status {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }

        .status-pending {
            background-color: var(--warning-bg);
            color: var(--warning-text);
        }

        .status-success {
            background-color: var(--success-bg);
            color: var(--success-text);
        }

        .status-failed {
            background-color: var(--danger-bg);
            color: var(--danger-text);
        }

        .prediction-details {
            margin-bottom: 1.5rem;
        }

        .prediction-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .detail-label {
            color: var(--gray);
        }

        .detail-value {
            font-weight: 500;
        }

        .progress-container {
            margin-top: 1.5rem;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .progress-label {
            font-size: 0.875rem;
            font-weight: 500;
        }

        .progress-value {
            font-weight: 600;
            color: var(--primary);
        }

        .progress-bar {
            height: 8px;
            background-color: var(--light-gray);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--light-gray);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .predictions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="user-profile">
                <div class="user-avatar">
                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                </div>
                <div class="user-info">
                    <h3><?= htmlspecialchars($user['username']) ?></h3>
                    <p><?= htmlspecialchars($user['email']) ?></p>
                </div>
            </div>
            <div class="notification-bell">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </div>
        </div>

        <div class="nav-bar">
            <a href="user_dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <div class="content-grid">
            <!-- Prediction Form Section -->
            <div class="card prediction-form-container">
                <div class="card-header">
                    <h2 class="card-title">Crop Prediction (Nepal)</h2>
                </div>
                
                <form class="form-grid" method="POST" action="">
                <div class="form-group">
    <label for="crop_id" class="form-label">Select Crop</label>
    <select id="crop_id" name="crop_id" class="form-control" required>
        <option value="">-- Select Crop --</option>
        
        <!-- Cereals -->
        <optgroup label="Cereals">
            <option value="maize_hybrid">Maize (Hybrid)</option>
            <option value="maize_local">Maize (Local)</option>
            <option value="rice_basmati">Rice (Basmati)</option>
            <option value="rice_nonbasmati">Rice (Non-Basmati)</option>
            <option value="wheat_sharbati">Wheat (Sharbati)</option>
            <option value="wheat_lokwan">Wheat (Lokwan)</option>
            <option value="barley">Barley</option>
            <option value="sorghum">Sorghum</option>
            <option value="millet_bajra">Millet (Bajra)</option>
            <option value="millet_jowar">Millet (Jowar)</option>
        </optgroup>
        
        <!-- Oilseeds -->
        <optgroup label="Oilseeds">
            <option value="mustard_yellow">Mustard (Yellow)</option>
            <option value="mustard_black">Mustard (Black)</option>
            <option value="soybean">Soybean</option>
            <option value="groundnut">Groundnut</option>
            <option value="sunflower">Sunflower</option>
            <option value="sesame">Sesame</option>
            <option value="castor">Castor</option>
        </optgroup>
        
        <!-- Pulses -->
        <optgroup label="Pulses">
            <option value="chickpea_kabuli">Chickpea (Kabuli)</option>
            <option value="chickpea_deshi">Chickpea (Deshi)</option>
            <option value="pigeonpea">Pigeonpea (Tur/Arhar)</option>
            <option value="lentil">Lentil (Masoor)</option>
            <option value="blackgram">Blackgram (Urad)</option>
            <option value="greengram">Greengram (Moong)</option>
            <option value="kidneybean">Kidney Bean (Rajma)</option>
        </optgroup>
        
        <!-- Commercial Crops -->
        <optgroup label="Commercial Crops">
            <option value="cotton_long">Cotton (Long Staple)</option>
            <option value="cotton_medium">Cotton (Medium Staple)</option>
            <option value="sugarcane">Sugarcane</option>
            <option value="tobacco">Tobacco</option>
            <option value="jute">Jute</option>
            <option value="rubber">Rubber</option>
        </optgroup>
        
        <!-- Vegetables -->
        <optgroup label="Vegetables">
            <option value="potato">Potato</option>
            <option value="onion">Onion</option>
            <option value="tomato">Tomato</option>
            <option value="chilli">Chilli</option>
            <option value="brinjal">Brinjal</option>
            <option value="okra">Okra (Bhindi)</option>
        </optgroup>
        
        <!-- Fruits -->
        <optgroup label="Fruits">
            <option value="mango">Mango</option>
            <option value="banana">Banana</option>
            <option value="apple">Apple</option>
            <option value="orange">Orange</option>
            <option value="grapes">Grapes</option>
        </optgroup>
        
        <!-- Spices -->
        <optgroup label="Spices">
            <option value="pepper">Pepper</option>
            <option value="turmeric">Turmeric</option>
            <option value="ginger">Ginger</option>
            <option value="coriander">Coriander</option>
            <option value="cumin">Cumin</option>
            <option value="cardamom">Cardamom</option>
        </optgroup>
        
        <!-- You can keep your PHP loop for additional crops -->
        <?php foreach ($crops as $crop): ?>
        <option value="<?= $crop['id'] ?>"><?= htmlspecialchars($crop['crop_name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
                    
<div class="form-group">
    <label for="region_id" class="form-label">Select Field/Region</label>
    <select id="region_id" name="region_id" class="form-control" required>
        <option value="">-- Select Field/Region --</option>
        
        <!-- Nepal's Ecological Belts -->
        <optgroup label="Ecological Belts">
            <option value="terai">Terai Plain (Southern Lowlands)</option>
            <option value="siwalik">Siwalik Hills (Chure)</option>
            <option value="midhills">Middle Hills (Pahad)</option>
            <option value="highmountains">High Mountains</option>
            <option value="himal">Himalayan Region</option>
        </optgroup>
        
        <!-- Nepal's Development Regions -->
        <optgroup label="Development Regions">
            <option value="eastern">Eastern Development Region</option>
            <option value="central">Central Development Region</option>
            <option value="western">Western Development Region</option>
            <option value="midwestern">Mid-Western Development Region</option>
            <option value="farwestern">Far-Western Development Region</option>
        </optgroup>
        
        <!-- Major Agricultural Zones -->
        <optgroup label="Agricultural Zones">
            <option value="koshi">Koshi Zone (Rice Belt)</option>
            <option value="janakpur">Janakpur Zone (Maize-Wheat)</option>
            <option value="bagmati">Bagmati Zone (Vegetables)</option>
            <option value="narayani">Narayani Zone (Commercial Crops)</option>
            <option value="lumbini">Lumbini Zone (Rice-Wheat)</option>
            <option value="karnali">Karnali Zone (High Altitude Crops)</option>
            <option value="mahakali">Mahakali Zone (Fruits)</option>
        </optgroup>
        
        <!-- Important Agricultural Districts -->
        <optgroup label="Key Districts">
            <option value="jhapa">Jhapa (Tea, Rice)</option>
            <option value="morang">Morang (Jute, Rice)</option>
            <option value="sunsari">Sunsari (Sugarcane)</option>
            <option value="sarlahi">Sarlahi (Oilseeds)</option>
            <option value="chitwan">Chitwan (Vegetables)</option>
            <option value="nawalparasi">Nawalparasi (Rice, Wheat)</option>
            <option value="rukum">Rukum (Apples, Potatoes)</option>
            <option value="mustang">Mustang (Apples, Buckwheat)</option>
        </optgroup>
        
        <!-- Major River Basins -->
        <optgroup label="River Basins">
            <option value="koshi_basin">Koshi River Basin</option>
            <option value="gandaki_basin">Gandaki River Basin</option>
            <option value="karnali_basin">Karnali River Basin</option>
            <option value="mahakali_basin">Mahakali River Basin</option>
        </optgroup>
        
        <!-- Your existing PHP loop for specific fields -->
        <?php foreach ($regions as $region): ?>
        <option value="<?= $region['id'] ?>"><?= htmlspecialchars($region['region_name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
                    
                    <div class="form-group">
                        <label for="temperature" class="form-label">Temperature (°C)</label>
                        <input type="number" id="temperature" name="temperature" class="form-control" placeholder="e.g., 25" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="rainfall" class="form-label">Rainfall (mm)</label>
                        <input type="number" id="rainfall" name="rainfall" class="form-control" placeholder="e.g., 1500" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="soil_ph" class="form-label">Soil pH</label>
                        <input type="number" id="soil_ph" name="soil_ph" step="0.1" min="0" max="14" class="form-control" placeholder="e.g., 6.5" required>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" class="btn-primary" style="width: 100%;">
                            <i class="fas fa-calculator"></i>
                            Calculate Prediction
                        </button>
                    </div>
                </form>
                
                <!-- Prediction Result Display -->
                <?php if (isset($_SESSION['prediction_result'])): ?>
                <div class="prediction-result">
                    <h3 class="result-title">Prediction Result</h3>
                    <div class="probability-value"><?= $_SESSION['prediction_result']['probability'] ?></div>
                    <p>Probability of Suitability</p>
                    <div class="suitability-badge <?= $_SESSION['prediction_result']['suitable'] ? 'suitable' : 'not-suitable' ?>">
                        Crop Suitable: <?= $_SESSION['prediction_result']['suitable'] ? 'Yes' : 'No' ?>
                    </div>
                    
                    <!-- Nepal-specific factors -->
                    <div style="margin-top: 1.5rem;">
                        <h4 style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--gray);">Nepal Factors:</h4>
                        <div>
                            <span class="nepal-factor-badge <?= 
                                strpos($_SESSION['prediction_inputs']['nepal_factors']['altitude_effect'], 'Terai') !== false ? 'terai' : 
                                (strpos($_SESSION['prediction_inputs']['nepal_factors']['altitude_effect'], 'Hill') !== false ? 'hill' : 'mountain')
                            ?>">
                                <?= $_SESSION['prediction_inputs']['nepal_factors']['altitude_effect'] ?>
                            </span>
                            <span class="nepal-factor-badge monsoon">
                                <?= $_SESSION['prediction_inputs']['nepal_factors']['season_effect'] ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Display input values -->
                    <div style="margin-top: 1.5rem;">
                        <h4 style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--gray);">Input Values:</h4>
                        <div class="prediction-detail">
                            <span class="detail-label">Temperature:</span>
                            <span class="detail-value"><?= $_SESSION['prediction_inputs']['temperature'] ?>°C</span>
                        </div>
                        <div class="prediction-detail">
                            <span class="detail-label">Rainfall:</span>
                            <span class="detail-value"><?= $_SESSION['prediction_inputs']['rainfall'] ?>mm</span>
                        </div>
                        <div class="prediction-detail">
                            <span class="detail-label">Soil pH:</span>
                            <span class="detail-value"><?= $_SESSION['prediction_inputs']['soil_ph'] ?></span>
                        </div>
                        <div class="prediction-detail">
                            <span class="detail-label">Crop Type:</span>
                            <span class="detail-value"><?= $_SESSION['prediction_inputs']['crop_type'] ?></span>
                        </div>
                        <div class="prediction-detail">
                            <span class="detail-label">Climate:</span>
                            <span class="detail-value"><?= $_SESSION['prediction_inputs']['climate'] ?></span>
                        </div>
                        <div class="prediction-detail">
                            <span class="detail-label">Soil Type:</span>
                            <span class="detail-value"><?= $_SESSION['prediction_inputs']['soil_type'] ?></span>
                        </div>
                        <div class="prediction-detail">
                            <span class="detail-label">Altitude:</span>
                            <span class="detail-value"><?= $_SESSION['prediction_inputs']['altitude'] ?> meters</span>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['prediction_result']); ?>
                <?php unset($_SESSION['prediction_inputs']); ?>
                <?php endif; ?>
            </div>

            <!-- Recent Predictions Section -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Predictions</h2>
                </div>
                
                <?php if (count($predictions) > 0): ?>
                <div class="predictions-grid">
                    <?php foreach ($predictions as $pred): 
                        $status_class = 'status-pending';
                        if (isset($pred['harvest_date'])) {
                            $status_class = ($pred['is_suitable']) ? 'status-success' : 'status-failed';
                        }
                    ?>
                    <div class="prediction-card">
                        <div class="prediction-header">
                            <h3 class="prediction-title"><?= htmlspecialchars($pred['crop_name']) ?></h3>
                            <span class="prediction-status <?= $status_class ?>">
                                <?= isset($pred['harvest_date']) ? 'Completed' : 'Pending' ?>
                            </span>
                        </div>
                        
                        <div class="prediction-details">
                            <div class="prediction-detail">
                                <span class="detail-label">Field:</span>
                                <span class="detail-value"><?= htmlspecialchars($pred['region_name']) ?></span>
                            </div>
                            <div class="prediction-detail">
                                <span class="detail-label">Altitude:</span>
                                <span class="detail-value"><?= htmlspecialchars($pred['altitude']) ?>m</span>
                            </div>
                            <div class="prediction-detail">
                                <span class="detail-label">Climate:</span>
                                <span class="detail-value"><?= htmlspecialchars($pred['climate']) ?></span>
                            </div>
                            <div class="prediction-detail">
                                <span class="detail-label">Predicted:</span>
                                <span class="detail-value"><?= date('M j, Y', strtotime($pred['prediction_date'])) ?></span>
                            </div>
                            <?php if (isset($pred['harvest_date'])): ?>
                            <div class="prediction-detail">
                                <span class="detail-label">Harvested:</span>
                                <span class="detail-value"><?= date('M j, Y', strtotime($pred['harvest_date'])) ?></span>
                            </div>
                            <div class="prediction-detail">
                                <span class="detail-label">Yield:</span>
                                <span class="detail-value"><?= isset($pred['actual_yield']) ? htmlspecialchars($pred['actual_yield']) : 'N/A' ?> kg/ha</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="progress-container">
                            <div class="progress-header">
                                <span class="progress-label">Success Probability</span>
                                <span class="progress-value"><?= round($pred['probability'] * 100) ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= round($pred['probability'] * 100) ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <h3>No Predictions Found</h3>
                    <p>Create your first prediction using the form</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Simple animation for prediction cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.prediction-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Dynamic altitude information
            const regionSelect = document.getElementById('region_id');
            const temperatureInput = document.getElementById('temperature');
            
            regionSelect.addEventListener('change', async function() {
                const regionId = this.value;
                if (!regionId) return;
                
                try {
                    const response = await fetch(`get_region_data.php?region_id=${regionId}`);
                    const data = await response.json();
                    
                    if (data.altitude) {
                        // Suggest temperature based on altitude
                        if (data.altitude < 1000) { // Terai
                            temperatureInput.placeholder = "25-35°C (Terai)";
                        } else if (data.altitude < 2000) { // Hill
                            temperatureInput.placeholder = "18-25°C (Hill)";
                        } else { // Mountain
                            temperatureInput.placeholder = "10-18°C (Mountain)";
                        }
                    }
                } catch (error) {
                    console.error('Error fetching region data:', error);
                }
            });
        });
    </script>
</body>
</html>
<?php
$connection = null; // Close PDO connection
?>