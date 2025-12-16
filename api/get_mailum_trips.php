<?php
header('Content-Type: application/json');
include '../includes/conn.php';

// Configurable Mailum center and radius (in meters)
$MAILUM_LAT = isset($_GET['lat']) ? floatval($_GET['lat']) : 10.461300; // Mailum default
$MAILUM_LNG = isset($_GET['lng']) ? floatval($_GET['lng']) : 123.049200; // Mailum default
$RADIUS_M   = isset($_GET['radius']) ? floatval($_GET['radius']) : 120.0; // detection radius
// Additional margin required to consider that the vehicle has truly left the zone (hysteresis)
$LEAVE_MARGIN_M = isset($_GET['leave_margin']) ? floatval($_GET['leave_margin']) : 60.0;

// Optional date filter (default: today)
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Haversine distance in meters
function haversine_m($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000.0; // meters
    $phi1 = deg2rad($lat1); $phi2 = deg2rad($lat2);
    $dphi = deg2rad($lat2 - $lat1); $dl = deg2rad($lon2 - $lon1);
    $a = sin($dphi/2)**2 + cos($phi1)*cos($phi2)*sin($dl/2)**2;
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

try {
    // Fetch GPS logs for the day ordered by time
    $stmt = $conn->prepare("SELECT latitude, longitude, timestamp FROM gps_location WHERE DATE(timestamp) = ? ORDER BY timestamp ASC");
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $res = $stmt->get_result();
    $points = [];
    while ($row = $res->fetch_assoc()) {
        $points[] = [
            'lat' => (float)$row['latitude'],
            'lng' => (float)$row['longitude'],
            'ts'  => $row['timestamp'],
        ];
    }
    $stmt->close();

    // State machine: count a trip on every re-entry after having left beyond (RADIUS + LEAVE_MARGIN)
    $insideStart = null;  // whether the very first point is inside
    $inside = false;      // current inside state based on RADIUS_M
    $hasLeft = false;     // whether we've left beyond outer threshold since last inside
    $reentries = 0;       // number of re-entries (each counts as a trip per spec)
    $lastDistance = null; // for debugging

    foreach ($points as $idx => $p) {
        $d = haversine_m($MAILUM_LAT, $MAILUM_LNG, $p['lat'], $p['lng']);
        $lastDistance = $d;
        $nowInside = ($d <= $RADIUS_M);
        $nowFarOutside = ($d >= ($RADIUS_M + $LEAVE_MARGIN_M));

        if ($idx === 0) {
            $insideStart = $nowInside;
            $inside = $nowInside;
            $hasLeft = $nowFarOutside; // if first point is already far outside
            continue;
        }

        if ($nowFarOutside) {
            // Mark that we've left sufficiently
            $hasLeft = true;
        }

        // Count a trip only when we re-enter after having left sufficiently
        if ($nowInside && !$inside && $hasLeft) {
            $reentries++;
            $hasLeft = false; // reset until next leave
        }

        $inside = $nowInside;
    }

    // Trips: every re-entry after a leave counts as one trip.
    // If the day starts inside Mailum and never leaves, trips remain 0.
    $trips = $reentries;

    echo json_encode([
        'success' => true,
        'date' => $date,
        'center' => ['lat' => $MAILUM_LAT, 'lng' => $MAILUM_LNG],
        'radius_m' => $RADIUS_M,
        'points' => count($points),
        'reentries' => $reentries,
        'trips' => $trips,
        'inside_start' => (bool)$insideStart,
        'inside_now' => (bool)$inside,
        'last_distance_m' => $lastDistance,
        'leave_margin_m' => $LEAVE_MARGIN_M
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>


