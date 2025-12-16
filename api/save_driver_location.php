<?php
/**
 * API endpoint to save driver location from mobile device
 * Requires driver to be logged in (session-based)
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
    // Get driver_id from session
    $driver_id = (int)$_SESSION['driver_id'];
    
    // Get location data from request (supports JSON, POST, and GET)
    $raw = file_get_contents('php://input');
    $jsonData = json_decode($raw, true);
    
    $latitude = null;
    $longitude = null;
    
    // Try to get from JSON first, then POST, then GET
    if (isset($jsonData['latitude']) && isset($jsonData['longitude'])) {
        $latitude = floatval($jsonData['latitude']);
        $longitude = floatval($jsonData['longitude']);
    } elseif (isset($_POST['latitude']) && isset($_POST['longitude'])) {
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
    } elseif (isset($_GET['latitude']) && isset($_GET['longitude'])) {
        $latitude = floatval($_GET['latitude']);
        $longitude = floatval($_GET['longitude']);
    }
    
    // Validate coordinates
    if ($latitude === null || $longitude === null || 
        !is_numeric($latitude) || !is_numeric($longitude)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid or missing latitude/longitude']);
        exit;
    }
    
    // Validate coordinate ranges
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Coordinates out of valid range']);
        exit;
    }
    
    // Check if driver_id column exists in gps_location table, if not, add it
    $checkColumn = $pdo->query("SHOW COLUMNS FROM gps_location LIKE 'driver_id'");
    if ($checkColumn->rowCount() === 0) {
        // Add driver_id column
        $pdo->exec("ALTER TABLE gps_location ADD COLUMN driver_id INT(11) NULL AFTER location_id");
        $pdo->exec("ALTER TABLE gps_location ADD INDEX idx_driver_id (driver_id)");
    }
    
    // Check if is_active column exists, if not, add it
    $checkColumn = $pdo->query("SHOW COLUMNS FROM gps_location LIKE 'is_active'");
    if ($checkColumn->rowCount() === 0) {
        $pdo->exec("ALTER TABLE gps_location ADD COLUMN is_active TINYINT(1) DEFAULT 1 COMMENT '1=GPS active, 0=GPS off but location recorded'");
    }
    
    // Check for recent location for this driver and update, otherwise insert
    // We'll update the most recent location for this driver if it's recent (within last 5 minutes)
    // Otherwise, we'll insert a new record
    $checkStmt = $pdo->prepare("
        SELECT location_id, timestamp FROM gps_location 
        WHERE driver_id = ? 
        ORDER BY timestamp DESC 
        LIMIT 1
    ");
    $checkStmt->execute([$driver_id]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        // Check if the existing location is recent (within 2 seconds for real-time tracking)
        $existingTimestamp = strtotime($existing['timestamp']);
        $currentTimestamp = time();
        $timeDiff = $currentTimestamp - $existingTimestamp;
        
        if ($timeDiff < 2) { // Less than 2 seconds old - update for real-time tracking
            // Update existing record and mark as active (GPS is on) - real-time update
            $updateStmt = $pdo->prepare("
                UPDATE gps_location 
                SET latitude = ?, longitude = ?, timestamp = NOW(), is_active = 1 
                WHERE location_id = ?
            ");
            $updateStmt->execute([$latitude, $longitude, $existing['location_id']]);
        } else {
            // Existing record is too old or new location significantly different, insert new one (active GPS)
            // This allows real-time tracking when driver moves significantly
            $insertStmt = $pdo->prepare("
                INSERT INTO gps_location (driver_id, latitude, longitude, timestamp, is_active) 
                VALUES (?, ?, ?, NOW(), 1)
            ");
            $insertStmt->execute([$driver_id, $latitude, $longitude]);
        }
    } else {
        // No existing location, insert new record (active GPS) - immediate appearance on map
        $insertStmt = $pdo->prepare("
            INSERT INTO gps_location (driver_id, latitude, longitude, timestamp, is_active) 
            VALUES (?, ?, ?, NOW(), 1)
        ");
        $insertStmt->execute([$driver_id, $latitude, $longitude]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Location saved successfully',
        'driver_id' => $driver_id,
        'latitude' => $latitude,
        'longitude' => $longitude
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>

