<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/conn.php';

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
    // Start session to get logged-in user role
    session_start();
    $sessionDriverId = isset($_SESSION['driver_id']) ? (int)$_SESSION['driver_id'] : null;
    $userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
    
    // 1) Get the active vehicle, route and driver
    // Priority: Always show the logged-in driver's vehicle if driver_id exists in session
    // This ensures that when a driver logs in, their vehicle details show on all dashboards (admin, driver, client)
    $sql = "SELECT w.waste_service_id, w.vehicle_name, w.vehicle_capacity, w.plate_no, w.driver_id,
                    d.first_name, d.last_name,
                    r.start_point, r.end_point
            FROM waste_service_table w
            LEFT JOIN driver_table d ON d.driver_id = w.driver_id
            LEFT JOIN route_table r ON r.route_id = w.route_id
            WHERE w.driver_id IS NOT NULL";
    
    // Always prioritize driver_id from session if it exists (regardless of user role)
    // This ensures the logged-in driver's vehicle shows on all dashboards
    if ($sessionDriverId) {
        $sql .= " AND w.driver_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$sessionDriverId]);
    } else {
        // If no driver_id in session, get first active vehicle with driver and route info
        $sql .= " AND d.driver_id IS NOT NULL AND r.route_id IS NOT NULL
                 ORDER BY w.waste_service_id ASC LIMIT 1";
        $stmt = $pdo->query($sql);
    }
    $vehicle = $stmt->fetch();

    if (!$vehicle) {
        // Return empty values when no vehicle is found (let frontend handle display)
        echo json_encode([
            'success' => false,
            'error' => 'No vehicle found for the specified driver',
            'vehicle_name' => '',
            'plate_no' => '',
            'vehicle_capacity' => '',
            'driver_name' => '',
            'driver_id' => $sessionDriverId,
            'status' => '',
            'current_location' => '',
            'capacity_percent' => 0.0,
            'capacity_count' => 0,
            'capacity_max' => 0,
            'capacity_status' => 'normal',
            'upload_count' => 0,
            'last_upload_time' => null,
            'start_point' => '',
            'end_point' => '',
            'gps' => ['latitude' => null, 'longitude' => null]
        ]);
        exit;
    }

    // Extract vehicle data directly from database - no hardcoded defaults
    $vehicleName = isset($vehicle['vehicle_name']) ? trim($vehicle['vehicle_name']) : '';
    $plateNo = isset($vehicle['plate_no']) ? trim($vehicle['plate_no']) : '';
    $vehicleCapacity = isset($vehicle['vehicle_capacity']) ? trim($vehicle['vehicle_capacity']) : '';
    
    // Extract driver name from database
    $driverFirstName = isset($vehicle['first_name']) ? trim($vehicle['first_name']) : '';
    $driverLastName = isset($vehicle['last_name']) ? trim($vehicle['last_name']) : '';
    $driverName = trim($driverFirstName . ' ' . $driverLastName);
    
    // Extract route information from database
    $startPointName = isset($vehicle['start_point']) ? trim($vehicle['start_point']) : '';
    $endPointName = isset($vehicle['end_point']) ? trim($vehicle['end_point']) : '';
    
    // Use session driver_id if available (prioritize logged-in driver for all dashboards)
    // This ensures the logged-in driver's vehicle shows on admin, driver, and client dashboards
    // If no session driver_id, use vehicle's driver_id from the query result
    $driverId = $sessionDriverId ?? (isset($vehicle['driver_id']) ? (int)$vehicle['driver_id'] : null);

    // 2) Get GPS location - prioritize driver's own location if available
    $currentLat = null;
    $currentLng = null;
    
    // First, try to get driver's own location from gps_location table
    if ($driverId) {
        $driverGpsStmt = $pdo->prepare("SELECT latitude, longitude FROM gps_location 
                                        WHERE driver_id = ? 
                                        ORDER BY timestamp DESC, location_id DESC LIMIT 1");
        $driverGpsStmt->execute([$driverId]);
        $driverGps = $driverGpsStmt->fetch();
        if ($driverGps && isset($driverGps['latitude']) && isset($driverGps['longitude'])) {
            $currentLat = (float)$driverGps['latitude'];
            $currentLng = (float)$driverGps['longitude'];
        }
    }
    
    // Fallback: Get latest GPS from any driver if driver-specific location not found
    if ($currentLat === null || $currentLng === null) {
        $gpsStmt = $pdo->query("SELECT latitude, longitude FROM gps_location 
                               WHERE driver_id IS NOT NULL
                               ORDER BY timestamp DESC, location_id DESC LIMIT 1");
        $gps = $gpsStmt->fetch();
        if ($gps && isset($gps['latitude']) && isset($gps['longitude'])) {
            $currentLat = (float)$gps['latitude'];
            $currentLng = (float)$gps['longitude'];
        }
    }
    
    // Final fallback: Use route start point coordinates if no GPS available
    if ($currentLat === null || $currentLng === null) {
        // Will be set below when resolving route coordinates
    }

    // Set current location from start point (will be updated if GPS is available)
    $currentLocation = $startPointName;

    // 3) Resolve coordinates for start and end points from database
    $startLat = null;
    $startLng = null;
    
    // Get start point coordinates from barangays_table
    if (!empty($startPointName)) {
        $sp = $pdo->prepare("SELECT latitude, longitude FROM barangays_table WHERE barangay = ? LIMIT 1");
        $sp->execute([$startPointName]);
        if ($row = $sp->fetch()) {
            $startLat = (float)$row['latitude'];
            $startLng = (float)$row['longitude'];
        }
    }

    $endLat = null; $endLng = null;
    if (!empty($endPointName)) {
        $ep = $pdo->prepare("SELECT latitude, longitude FROM barangays_table WHERE barangay = ? LIMIT 1");
        $ep->execute([$endPointName]);
        if ($row = $ep->fetch()) {
            $endLat = (float)$row['latitude'];
            $endLng = (float)$row['longitude'];
        }
    }

    // 4) Determine nearest barangay to current GPS as the human-readable location
    $status = ''; // Will be determined based on location and activity
    $nearestBarangay = null;
    if ($currentLat !== null && $currentLng !== null) {
        $bStmt = $pdo->query("SELECT barangay, latitude, longitude FROM barangays_table WHERE city = 'Bago City'");
        $minDist = PHP_FLOAT_MAX;
        while ($b = $bStmt->fetch()) {
            $bLat = (float)$b['latitude'];
            $bLng = (float)$b['longitude'];
            $dist = haversineDistanceKm($currentLat, $currentLng, $bLat, $bLng);
            if ($dist < $minDist) {
                $minDist = $dist;
                $nearestBarangay = $b['barangay'];
            }
        }
        if ($nearestBarangay) {
            $currentLocation = $nearestBarangay;
        } else {
            $currentLocation = number_format($currentLat, 5) . ", " . number_format($currentLng, 5);
        }
        } else {
            // No GPS available, use route start point as location if available
            if (!empty($startPointName)) {
                $currentLocation = $startPointName;
            }
            // Set coordinates to start point for status calculation if available
            if ($startLat !== null && $startLng !== null) {
                $currentLat = $startLat;
                $currentLng = $startLng;
            }
        }

    // Check if driver_waste_uploads table exists (needed for status and capacity calculations)
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'driver_waste_uploads'");
    $hasUploadsTable = $tableCheck->rowCount() > 0;
    
    // 5) Derive status (three states based on location and waste uploads)
    // Check if driver has recent waste uploads (within last 5 minutes) to determine collecting status
    $isCollecting = false;
    if ($hasUploadsTable && $driverId) {
        $fiveMinutesAgo = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        $stmt = $pdo->prepare("SELECT COUNT(*) as recent_uploads 
                             FROM driver_waste_uploads 
                             WHERE driver_id = ? 
                             AND created_at >= ? 
                             AND waste_count > 0");
        $stmt->execute([(int)$driverId, $fiveMinutesAgo]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $isCollecting = intval($result['recent_uploads'] ?? 0) > 0;
    }
    
    $nearEndPoint = (!empty($endPointName) && $nearestBarangay === $endPointName);
    $insideAnyBarangay = !empty($nearestBarangay);

    // Determine status based on location and activity data
    if ($nearEndPoint && !$isCollecting) {
        $status = 'Route Accomplished';
    } elseif ($isCollecting) {
        $status = 'Collecting';
    } elseif ($insideAnyBarangay) {
        $status = 'Collected';
    } elseif (!empty($currentLocation)) {
        $status = 'Ongoing';
    } else {
        $status = ''; // No status if no location data available
    }

    // 6) Calculate capacity percentage based on waste collected TODAY (same as dashboard)
    // This ensures capacity UI updates in real-time based on actual waste collected today
    // Uses driver_waste_uploads table only
    
    // Extract max capacity from vehicle_capacity field if it contains numeric value
    // vehicle_capacity is stored as string like "3 - 5 tons", extract max value
    $maxCapacity = 1000; // Default fallback
    if (!empty($vehicleCapacity)) {
        // Try to extract numeric value from capacity string (e.g., "3 - 5 tons" -> 5)
        if (preg_match('/(\d+)\s*-\s*(\d+)/', $vehicleCapacity, $matches)) {
            $maxCapacity = (int)$matches[2] * 200; // Convert tons to units (1 ton = 200 units approx)
        } elseif (preg_match('/(\d+)/', $vehicleCapacity, $matches)) {
            $maxCapacity = (int)$matches[1] * 200; // Single number, convert to units
        }
    }
    
    $capacityPercent = 0; // Default to 0 if no data
    $uploadData = null; // Initialize for metadata
    $todayCount = 0;
    
    // Check if driver_waste_uploads table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'driver_waste_uploads'");
    $hasUploadsTable = $tableCheck->rowCount() > 0;
    
    // Get waste collected today from driver_waste_uploads table (volume-based)
    // This uses the EXACT SAME calculation as "Collected Waste Today" on the dashboard
    // Formula: (waste_count * 0.001) * 100 = waste_count * 0.1 = volume %
    $todayDate = date('Y-m-d');
    
    if ($hasUploadsTable && $driverId) {
        // Get today's waste collection from driver_waste_uploads for this driver
        // Use the EXACT SAME query as get_driver_dashboard_summary.php
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(waste_count), 0) as today_count 
                               FROM driver_waste_uploads 
                               WHERE driver_id = ? AND DATE(collection_date) = DATE(?)");
        $stmt->execute([(int)$driverId, $todayDate]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $todayCount = intval($result['today_count'] ?? 0);
        $todayTons = $todayCount * 0.001; // Convert count to tons (same as dashboard)
        
        // Calculate bin fill percentage using EXACT SAME formula as "Collected Waste Today"
        // Collected Waste Today = (todayTons * 100).toFixed(0) + '% volume'
        // So bin level = todayTons * 100 = todayCount * 0.1
        $capacityPercent = ($todayTons * 100); // Same calculation as dashboard
        $capacityPercent = min(100, max(0, $capacityPercent)); // Cap between 0-100%
        
        // Also get upload metadata for debugging
        $stmt = $pdo->prepare("SELECT COUNT(*) as upload_count,
                                      MAX(created_at) as last_upload_time
                               FROM driver_waste_uploads 
                               WHERE driver_id = ? AND DATE(collection_date) = DATE(?)");
        $stmt->execute([(int)$driverId, $todayDate]);
        $uploadData = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Determine capacity status
    $capacityStatus = 'normal';
    if ($capacityPercent >= 100) {
        $capacityStatus = 'full';
    } elseif ($capacityPercent >= 80) {
        $capacityStatus = 'warning';
    }

    // Ensure capacity_percent is always a number
    $capacityPercent = floatval($capacityPercent ?? 0);
    
    // Build response with actual database values (no hardcoded defaults)
    $response = [
        'success' => true,
        'vehicle_name' => $vehicleName,
        'plate_no' => $plateNo,
        'vehicle_capacity' => $vehicleCapacity,
        'driver_name' => $driverName,
        'driver_id' => $driverId,
        'status' => $status,
        'current_location' => $currentLocation,
        'capacity_percent' => round($capacityPercent, 1),
        'capacity_count' => $todayCount,
        'capacity_max' => $maxCapacity,
        'capacity_status' => $capacityStatus,
        'upload_count' => isset($uploadData['upload_count']) ? intval($uploadData['upload_count']) : 0,
        'last_upload_time' => isset($uploadData['last_upload_time']) ? $uploadData['last_upload_time'] : null,
        'start_point' => $startPointName,
        'end_point' => $endPointName,
        'gps' => [
            'latitude' => $currentLat !== null ? $currentLat : ($startLat !== null ? $startLat : null),
            'longitude' => $currentLng !== null ? $currentLng : ($startLng !== null ? $startLng : null)
        ]
    ];
    
    echo json_encode($response);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>


