<?php
include '../includes/conn.php';

header('Content-Type: application/json');

if (!isset($_GET['route'])) {
    echo json_encode(['success' => false, 'error' => 'Route parameter required']);
    exit();
}

$route = intval($_GET['route']);

// Get all route data
$checkSql = "SELECT id, route_details, latitude, longitude FROM route_info ORDER BY id";
$checkResult = $conn->query($checkSql);

if ($checkResult && $checkResult->num_rows > 0) {
    $allRoutes = $checkResult->fetch_all(MYSQLI_ASSOC);
    
    // Debug: Log all routes
    error_log("All routes: " . json_encode($allRoutes));
    error_log("Requested route: " . $route);
    
    // Calculate which records to use based on route number
    $startIndex = ($route - 1) * 2; // Route 1 = 0,1; Route 2 = 2,3; etc.
    $endIndex = $startIndex + 1;
    
    error_log("Start index: " . $startIndex . ", End index: " . $endIndex);
    
    if (isset($allRoutes[$startIndex]) && isset($allRoutes[$endIndex])) {
        $startRoute = $allRoutes[$startIndex];
        $endRoute = $allRoutes[$endIndex];
        
        error_log("Start route: " . json_encode($startRoute));
        error_log("End route: " . json_encode($endRoute));
        
        echo json_encode([
            'success' => true,
            'startId' => $startRoute['id'],
            'endId' => $endRoute['id'],
            'startPoint' => $startRoute['route_details'],
            'endPoint' => $endRoute['route_details'],
            'startLat' => $startRoute['latitude'],
            'startLong' => $startRoute['longitude'],
            'endLat' => $endRoute['latitude'],
            'endLong' => $endRoute['longitude'],
            'debug' => [
                'totalRoutes' => count($allRoutes),
                'requestedRoute' => $route,
                'startIndex' => $startIndex,
                'endIndex' => $endIndex
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Route ' . $route . ' not found. Available routes: ' . floor(count($allRoutes) / 2)]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No route data found in database']);
}
?>
