<?php
/**
 * API endpoint to mark driver location as inactive (GPS turned off)
 * Saves the last known location and marks it as inactive
 */
header('Content-Type: application/json');

session_start();

// Check if driver is logged in
if (!isset($_SESSION['driver_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Driver must be logged in.']);
    exit;
}

require_once __DIR__ . '/../includes/conn.php';

try {
    $driver_id = (int)$_SESSION['driver_id'];
    
    // Get data from request (JSON, POST, or GET)
    $raw = file_get_contents('php://input');
    $jsonData = json_decode($raw, true);
    
    $latitude = null;
    $longitude = null;
    $status = isset($jsonData['status']) ? $jsonData['status'] : (isset($_POST['status']) ? $_POST['status'] : null);
    
    // Try to get lat/lng from request if provided
    if (isset($jsonData['last_latitude']) && isset($jsonData['last_longitude'])) {
        $latitude = floatval($jsonData['last_latitude']);
        $longitude = floatval($jsonData['last_longitude']);
    } elseif (isset($_POST['last_latitude']) && isset($_POST['last_longitude'])) {
        $latitude = floatval($_POST['last_latitude']);
        $longitude = floatval($_POST['last_longitude']);
    }
    
    // Check if is_active column exists
    $checkColumn = $pdo->query("SHOW COLUMNS FROM gps_location LIKE 'is_active'");
    if ($checkColumn->rowCount() === 0) {
        $pdo->exec("ALTER TABLE gps_location ADD COLUMN is_active TINYINT(1) DEFAULT 1");
    }
    
    // Get or save last known location
    if ($latitude !== null && $longitude !== null && $status === 'inactive') {
        // Save the provided location as inactive (last known location)
        $insertStmt = $pdo->prepare("
            INSERT INTO gps_location (driver_id, latitude, longitude, timestamp, is_active) 
            VALUES (?, ?, ?, NOW(), 0)
            ON DUPLICATE KEY UPDATE 
                latitude = VALUES(latitude),
                longitude = VALUES(longitude),
                timestamp = NOW(),
                is_active = 0
        ");
        
        // Since ON DUPLICATE KEY might not work, use UPDATE if exists, else INSERT
        $checkStmt = $pdo->prepare("
            SELECT location_id FROM gps_location 
            WHERE driver_id = ? 
            ORDER BY timestamp DESC 
            LIMIT 1
        ");
        $checkStmt->execute([$driver_id]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            // Update existing record to mark as inactive
            $updateStmt = $pdo->prepare("
                UPDATE gps_location 
                SET latitude = ?, longitude = ?, timestamp = NOW(), is_active = 0 
                WHERE location_id = ?
            ");
            $updateStmt->execute([$latitude, $longitude, $existing['location_id']]);
        } else {
            // Insert new inactive record
            $insertStmt = $pdo->prepare("
                INSERT INTO gps_location (driver_id, latitude, longitude, timestamp, is_active) 
                VALUES (?, ?, ?, NOW(), 0)
            ");
            $insertStmt->execute([$driver_id, $latitude, $longitude]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Last known location saved (GPS off)',
            'last_location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'is_active' => 0
            ]
        ]);
    } else {
        // Get last known location for this driver
        $stmt = $pdo->prepare("
            SELECT location_id, latitude, longitude, timestamp 
            FROM gps_location 
            WHERE driver_id = ? 
            ORDER BY timestamp DESC 
            LIMIT 1
        ");
        $stmt->execute([$driver_id]);
        $lastLocation = $stmt->fetch();
        
        if ($lastLocation) {
            // Mark last location as inactive (GPS off, but location still recorded)
            $updateStmt = $pdo->prepare("
                UPDATE gps_location 
                SET is_active = 0, timestamp = NOW() 
                WHERE location_id = ?
            ");
            $updateStmt->execute([$lastLocation['location_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Location marked as inactive (GPS off)',
                'last_location' => [
                    'latitude' => floatval($lastLocation['latitude']),
                    'longitude' => floatval($lastLocation['longitude']),
                    'timestamp' => $lastLocation['timestamp']
                ]
            ]);
        } else {
            // No previous location found
            echo json_encode([
                'success' => false,
                'message' => 'No previous location found to mark as inactive'
            ]);
        }
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>

