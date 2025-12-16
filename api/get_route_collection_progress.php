<?php
header('Content-Type: application/json');
include '../includes/conn.php';

function haversineDistanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $earthRadius = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

try {
    // Get parameters
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
    $week = isset($_GET['week']) ? (int)$_GET['week'] : ceil(date('j') / 7);

    // Calculate week date range
    $firstDayOfMonth = new DateTime("$year-$month-01");
    $lastDayOfMonth = clone $firstDayOfMonth;
    $lastDayOfMonth->modify('last day of this month');

    // First Monday of month
    $firstMonday = clone $firstDayOfMonth;
    $dow = (int)$firstMonday->format('N');
    if ($dow != 1) {
        $firstMonday->modify('last Monday');
    }

    // Selected week
    $weekMonday = clone $firstMonday;
    $daysToAdd = 7 * ($week - 1);
    $weekMonday->add(new DateInterval("P{$daysToAdd}D"));

    $weekSunday = clone $weekMonday;
    $weekSunday->add(new DateInterval('P6D'));

    $weekStart = $weekMonday->format('Y-m-d 00:00:00');
    $weekEnd = $weekSunday->format('Y-m-d');
    $weekEndExt = $weekSunday->format('Y-m-d 23:59:59');

    // Fetch all vehicles with their routes
    $sql = "SELECT w.waste_service_id, w.vehicle_name, w.route_id,
                   r.start_point, r.end_point, r.brgy_id
            FROM waste_service_table w
            LEFT JOIN route_table r ON w.route_id = r.route_id
            WHERE w.route_id IS NOT NULL";
    
    $result = $conn->query($sql);
    $vehicles = [];
    $totalRoutes = 0;

    while ($row = $result->fetch_assoc()) {
        $vehicleId = $row['waste_service_id'];
        $routeId = $row['route_id'];
        $startPoint = $row['start_point'];
        $endPoint = $row['end_point'];
        $brgyId = $row['brgy_id'];

        // Get barangay coordinates for start and end points
        $startLat = null; $startLng = null;
        $endLat = null; $endLng = null;

        // Resolve start point coordinates
        if (strtolower($startPoint) !== 'bago city hall') {
            $spStmt = $conn->prepare("SELECT latitude, longitude FROM barangays_table WHERE barangay = ? OR brgy_id = ? LIMIT 1");
            $spStmt->bind_param("si", $startPoint, $brgyId);
            $spStmt->execute();
            $spResult = $spStmt->get_result();
            if ($sp = $spResult->fetch_assoc()) {
                $startLat = (float)$sp['latitude'];
                $startLng = (float)$sp['longitude'];
            }
            $spStmt->close();
        } else {
            // Bago City Hall default coordinates
            $startLat = 10.538274;
            $startLng = 122.835230;
        }

        // Resolve end point coordinates
        if (!empty($endPoint)) {
            $epStmt = $conn->prepare("SELECT latitude, longitude FROM barangays_table WHERE barangay = ? LIMIT 1");
            $epStmt->bind_param("s", $endPoint);
            $epStmt->execute();
            $epResult = $epStmt->get_result();
            if ($ep = $epResult->fetch_assoc()) {
                $endLat = (float)$ep['latitude'];
                $endLng = (float)$ep['longitude'];
            }
            $epStmt->close();
        }

        // Check GPS data for this vehicle during the week
        $routeCompleted = false;
        $inMailum = false;
        
        // Mailum coordinates
        $mailumLat = 10.46211;
        $mailumLng = 123.0492;
        $mailumRadius = 0.5; // 500 meters (0.5 km)
        
        // Get the LATEST GPS location regardless of week for current status
        $latestGpsStmt = $conn->prepare("
            SELECT latitude, longitude
            FROM gps_location 
            ORDER BY timestamp DESC
            LIMIT 1
        ");
        $latestGpsStmt->execute();
        $latestGpsResult = $latestGpsStmt->get_result();

        // Check latest GPS location for Mailum entry
        if ($latestGps = $latestGpsResult->fetch_assoc()) {
            $latestGpsLat = (float)$latestGps['latitude'];
            $latestGpsLng = (float)$latestGps['longitude'];
            
            // Check if currently in Mailum
            $distToMailum = haversineDistanceKm($latestGpsLat, $latestGpsLng, $mailumLat, $mailumLng);
            if ($distToMailum <= $mailumRadius) {
                $inMailum = true;
            }
        }
        $latestGpsStmt->close();
        
        // Get existing trip count from database for this vehicle and week
        $existingTripStmt = $conn->prepare("
            SELECT trip_count, last_entry_time 
            FROM vehicle_trip_counts 
            WHERE vehicle_id = ? AND week_start = ?
        ");
        $existingTripStmt->bind_param("is", $vehicleId, $weekStart);
        $existingTripStmt->execute();
        $existingTripResult = $existingTripStmt->get_result();
        
        $tripsToMailum = 0;
        $lastEntryTime = null;
        
        if ($existingTrip = $existingTripResult->fetch_assoc()) {
            $tripsToMailum = (int)$existingTrip['trip_count'];
            $lastEntryTime = $existingTrip['last_entry_time'];
        }
        $existingTripStmt->close();
        
        // Check if vehicle is currently in Mailum and should increment trip count
        if ($inMailum) {
            $currentTime = date('Y-m-d H:i:s');
            $shouldIncrement = false;
            
            // If no previous entry time, this is the first trip
            if ($lastEntryTime === null) {
                $shouldIncrement = true;
            } else {
                // Check if enough time has passed since last entry (2 minutes minimum)
                $timeDiff = strtotime($currentTime) - strtotime($lastEntryTime);
                if ($timeDiff >= 120) { // 2 minutes = 120 seconds
                    $shouldIncrement = true;
                }
            }
            
            // Increment trip count if conditions are met
            if ($shouldIncrement) {
                $tripsToMailum++;
                
                // Update or insert trip count in database
                $updateTripStmt = $conn->prepare("
                    INSERT INTO vehicle_trip_counts (vehicle_id, week_start, week_end, trip_count, last_entry_time)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    trip_count = VALUES(trip_count),
                    last_entry_time = VALUES(last_entry_time),
                    updated_at = CURRENT_TIMESTAMP
                ");
                $updateTripStmt->bind_param("issis", $vehicleId, $weekStart, $weekEnd, $tripsToMailum, $currentTime);
                $updateTripStmt->execute();
                $updateTripStmt->close();
                
                error_log("Vehicle: " . $row['vehicle_name'] . " - Trip count incremented to: $tripsToMailum");
            }
        }
        
        error_log("Vehicle: " . $row['vehicle_name'] . " - Current trips: $tripsToMailum, In Mailum: " . ($inMailum ? 'Yes' : 'No'));
        
        // Debug output
        error_log("Vehicle: " . $row['vehicle_name'] . " - Trips to Mailum: " . $tripsToMailum);
        
        if ($startLat !== null && $startLng !== null) {
            // Get GPS points during the week for route completion check
            $gpsStmt = $conn->prepare("
                SELECT latitude, longitude 
                FROM gps_location 
                WHERE timestamp >= ? AND timestamp <= ? 
                ORDER BY timestamp ASC
            ");
            $gpsStmt->bind_param("ss", $weekStart, $weekEndExt);
            $gpsStmt->execute();
            $gpsResult = $gpsStmt->get_result();
            
            $startReached = false;
            $endReached = false;

            while ($gps = $gpsResult->fetch_assoc()) {
                $gpsLat = (float)$gps['latitude'];
                $gpsLng = (float)$gps['longitude'];

                // Check if GPS reached start point (within 1km)
                if (!$startReached) {
                    $distToStart = haversineDistanceKm($gpsLat, $gpsLng, $startLat, $startLng);
                    if ($distToStart < 1.0) { // 1 kilometer
                        $startReached = true;
                    }
                }

                // Check if GPS reached end point (within 1km)
                if ($endLat !== null && $endLng !== null && $startReached && !$endReached) {
                    $distToEnd = haversineDistanceKm($gpsLat, $gpsLng, $endLat, $endLng);
                    if ($distToEnd < 1.0) { // 1 kilometer
                        $endReached = true;
                        $routeCompleted = true;
                        break; // Route completed
                    }
                }
            }

            $gpsStmt->close();
        }

        $vehicles[] = [
            'vehicle_name' => $row['vehicle_name'],
            'route' => "$startPoint → $endPoint",
            'completed' => $routeCompleted,
            'in_mailum' => $inMailum,
            'trips' => $tripsToMailum
        ];

        $totalRoutes++;
    }

    // Calculate progress based on days with collection data (14.285714285% per day)
    // Count distinct days in the current week that have collection data
    $daysWithData = 0;
    
    // Get current week dates (Monday to Sunday)
    $today = new DateTime();
    $dayOfWeek = (int)$today->format('w'); // 0 = Sunday, 1 = Monday, etc.
    $daysFromMonday = ($dayOfWeek == 0) ? 6 : $dayOfWeek - 1;
    
    $currentWeekMonday = clone $today;
    $currentWeekMonday->modify('-' . $daysFromMonday . ' days');
    $currentWeekMonday->setTime(0, 0, 0);
    
    $currentWeekSunday = clone $currentWeekMonday;
    $currentWeekSunday->modify('+6 days');
    $currentWeekSunday->setTime(23, 59, 59);
    
    $currentWeekStart = $currentWeekMonday->format('Y-m-d');
    $currentWeekEnd = $currentWeekSunday->format('Y-m-d');
    
    // Check driver_waste_uploads table for days with data in current week
    $tableCheck = $conn->query("SHOW TABLES LIKE 'driver_waste_uploads'");
    $hasUploadsTable = $tableCheck && $tableCheck->num_rows > 0;
    
    if ($hasUploadsTable) {
        // Count distinct days with waste uploads in the current week
        $daysStmt = $conn->prepare("
            SELECT COUNT(DISTINCT DATE(collection_date)) as days_count
            FROM driver_waste_uploads 
            WHERE DATE(collection_date) BETWEEN DATE(?) AND DATE(?)
            AND waste_count > 0
        ");
        $daysStmt->bind_param("ss", $currentWeekStart, $currentWeekEnd);
        $daysStmt->execute();
        $daysResult = $daysStmt->get_result();
        if ($daysRow = $daysResult->fetch_assoc()) {
            $daysWithData = (int)$daysRow['days_count'];
        }
        $daysStmt->close();
    }
    
    // Calculate progress: each day = 14.285714285714% (100% / 7 days)
    // 1 day = 14.28%, 2 days = 28.57%, 3 days = 42.86%, etc.
    $progressPercentage = $daysWithData * (100 / 7);
    $progressPercentage = round($progressPercentage, 2); // 2 decimal places
    
    // Cap at 100%
    if ($progressPercentage > 100) {
        $progressPercentage = 100.00;
    }
    
    // Calculate overall progress based on trips (for backward compatibility)
    $completedRoutes = array_filter($vehicles, function($v) { 
        return $v['completed'] || ($v['trips'] && $v['trips'] > 0); 
    });

    echo json_encode([
        'success' => true,
        'week' => $week,
        'week_range' => $weekMonday->format('M j') . ' - ' . $weekSunday->format('M j'),
        'total_routes' => $totalRoutes,
        'completed_routes' => count($completedRoutes),
        'progress' => $progressPercentage,
        'days_with_data' => $daysWithData,
        'vehicles' => $vehicles
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

