<?php
/**
 * API endpoint to determine which barangay a location (latitude, longitude) belongs to
 * Returns the nearest barangay based on haversine distance calculation
 */
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
    // Get latitude and longitude from request
    $latitude = isset($_GET['latitude']) ? floatval($_GET['latitude']) : (isset($_POST['latitude']) ? floatval($_POST['latitude']) : null);
    $longitude = isset($_GET['longitude']) ? floatval($_GET['longitude']) : (isset($_POST['longitude']) ? floatval($_POST['longitude']) : null);
    
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
    
    // Get all barangays in Bago City
    $stmt = $pdo->query("SELECT barangay, brgy_id, latitude, longitude FROM barangays_table WHERE city = 'Bago City'");
    $barangays = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($barangays)) {
        echo json_encode([
            'success' => false,
            'error' => 'No barangays found in database'
        ]);
        exit;
    }
    
    // Find nearest barangay using haversine distance
    $nearestBarangay = null;
    $minDistance = PHP_FLOAT_MAX;
    
    foreach ($barangays as $barangay) {
        $bLat = floatval($barangay['latitude']);
        $bLng = floatval($barangay['longitude']);
        
        // Calculate distance
        $distance = haversineDistanceKm($latitude, $longitude, $bLat, $bLng);
        
        if ($distance < $minDistance) {
            $minDistance = $distance;
            $nearestBarangay = [
                'barangay' => $barangay['barangay'],
                'brgy_id' => intval($barangay['brgy_id']),
                'distance_km' => round($distance, 3)
            ];
        }
    }
    
    if ($nearestBarangay) {
        echo json_encode([
            'success' => true,
            'barangay' => $nearestBarangay['barangay'],
            'brgy_id' => $nearestBarangay['brgy_id'],
            'distance_km' => $nearestBarangay['distance_km']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Could not determine barangay'
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>


