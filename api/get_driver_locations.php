<?php
/**
 * API endpoint to fetch all active driver locations for display on map dashboards
 * Returns locations for all drivers who have updated their location in the last 2 seconds
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/conn.php';

try {
    // Check if driver_id column exists, if not return empty array
    $checkColumn = $pdo->query("SHOW COLUMNS FROM gps_location LIKE 'driver_id'");
    $hasDriverColumn = $checkColumn->rowCount() > 0;
    
    if (!$hasDriverColumn) {
        // Return empty array if column doesn't exist yet
        echo json_encode([
            'success' => true,
            'drivers' => [],
            'message' => 'Driver location tracking not yet initialized'
        ]);
        exit;
    }
    
    // Check if is_active column exists
    $checkColumn = $pdo->query("SHOW COLUMNS FROM gps_location LIKE 'is_active'");
    $hasIsActiveColumn = $checkColumn->rowCount() > 0;
    
    // Get all driver locations (active within 2 seconds OR inactive but still recent)
    // Use 2 second window for active locations for faster real-time updates
    // Include both active GPS locations and inactive (GPS off) locations
    if ($hasIsActiveColumn) {
        $sql = "
            SELECT 
                gl.driver_id,
                gl.latitude,
                gl.longitude,
                gl.timestamp,
                COALESCE(gl.is_active, 1) as is_active,
                d.first_name,
                d.last_name,
                d.contact,
                w.vehicle_name,
                w.plate_no
            FROM gps_location gl
            LEFT JOIN driver_table d ON d.driver_id = gl.driver_id
            LEFT JOIN waste_service_table w ON w.driver_id = gl.driver_id
            WHERE gl.driver_id IS NOT NULL
            AND (
                (COALESCE(gl.is_active, 1) = 1 AND gl.timestamp >= DATE_SUB(NOW(), INTERVAL 2 SECOND))
                OR 
                (COALESCE(gl.is_active, 1) = 0 AND gl.timestamp >= DATE_SUB(NOW(), INTERVAL 30 MINUTE))
            )
            ORDER BY gl.timestamp DESC
        ";
    } else {
        // Fallback if is_active column doesn't exist yet - use 2 second window
        $sql = "
            SELECT 
                gl.driver_id,
                gl.latitude,
                gl.longitude,
                gl.timestamp,
                1 as is_active,
                d.first_name,
                d.last_name,
                d.contact,
                w.vehicle_name,
                w.plate_no
            FROM gps_location gl
            LEFT JOIN driver_table d ON d.driver_id = gl.driver_id
            LEFT JOIN waste_service_table w ON w.driver_id = gl.driver_id
            WHERE gl.driver_id IS NOT NULL
            AND gl.timestamp >= DATE_SUB(NOW(), INTERVAL 2 SECOND)
            ORDER BY gl.timestamp DESC
        ";
    }
    
    $stmt = $pdo->query($sql);
    $drivers = [];
    
    // Group by driver_id to get only the latest location per driver
    $driverLocations = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $driverId = (int)$row['driver_id'];
        
        // Only keep the latest location for each driver
        if (!isset($driverLocations[$driverId])) {
            $driverLocations[$driverId] = [
                'driver_id' => $driverId,
                'first_name' => $row['first_name'] ?? 'Unknown',
                'last_name' => $row['last_name'] ?? 'Driver',
                'full_name' => trim(($row['first_name'] ?? 'Unknown') . ' ' . ($row['last_name'] ?? 'Driver')),
                'contact' => $row['contact'] ?? '',
                'vehicle_name' => $row['vehicle_name'] ?? 'No Vehicle',
                'plate_no' => $row['plate_no'] ?? 'N/A',
                'latitude' => floatval($row['latitude']),
                'longitude' => floatval($row['longitude']),
                'timestamp' => $row['timestamp'],
                'is_active' => isset($row['is_active']) ? (int)$row['is_active'] : 1
            ];
        }
    }
    
    // Convert associative array to indexed array
    $drivers = array_values($driverLocations);
    
    echo json_encode([
        'success' => true,
        'drivers' => $drivers,
        'count' => count($drivers)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>

