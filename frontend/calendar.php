<?php
session_start();
require 'connection.php';

// Fetch all data first and store in a PHP array
$events = [];
$query = "SELECT c.crop_name, c.planting_date, c.harvest_date, 
                 r.region_name, r.soil_type
          FROM crops c
          LEFT JOIN regions r ON c.user_id = r.user_id
          WHERE c.user_id = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $events[] = [
        'crop_name' => $row['crop_name'],
        'planting_date' => $row['planting_date'],
        'harvest_date' => $row['harvest_date'],
        'region' => $row['region_name'] ?? 'Not specified',
        'soil' => $row['soil_type'] ?? 'Not specified'
    ];
}

// Convert PHP array to JSON for JavaScript
$events_json = json_encode($events, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <style>
        #calendar {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>
    
    <div id="calendar"></div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Parse the PHP-generated JSON
        const phpEvents = <?= $events_json ?>;
        const calendarEvents = [];
        
        // Process each event
        phpEvents.forEach(event => {
            if (event.crop_name && event.planting_date) {
                // Add planting event
                calendarEvents.push({
                    title: event.crop_name + ' - Planting',
                    start: event.planting_date,
                    color: '#2e7d32',
                    extendedProps: {
                        type: 'planting',
                        region: event.region,
                        soil: event.soil
                    }
                });
                
                // Add harvest event if exists
                if (event.harvest_date) {
                    calendarEvents.push({
                        title: event.crop_name + ' - Harvest',
                        start: event.harvest_date,
                        color: '#ff9800',
                        extendedProps: {
                            type: 'harvest',
                            region: event.region,
                            soil: event.soil
                        }
                    });
                }
            }
        });

        // Initialize calendar
        const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            events: calendarEvents,
            eventClick: function(info) {
                alert(
                    'Activity: ' + info.event.title + '\n' +
                    'Date: ' + info.event.start.toLocaleDateString() + '\n' +
                    'Region: ' + info.event.extendedProps.region + '\n' +
                    'Soil: ' + info.event.extendedProps.soil
                );
            }
        });
        
        calendar.render();
    });
    </script>
</body>
</html>