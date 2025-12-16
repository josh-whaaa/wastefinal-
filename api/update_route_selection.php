<?php
include '../includes/conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST method allowed']);
    exit();
}

if (!isset($_POST['action']) || $_POST['action'] !== 'update_route') {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit();
}

if (!isset($_POST['route_number'])) {
    echo json_encode(['success' => false, 'error' => 'Route number required']);
    exit();
}

$routeNumber = intval($_POST['route_number']);

try {
    // Update the waste_service_table to use the selected route
    // Route 1 = ID 1, Route 2 = ID 3, etc.
    $newRouteId = ($routeNumber - 1) * 2 + 1; // Route 1 = ID 1, Route 2 = ID 3, Route 3 = ID 5, etc.
    
    $sql = "UPDATE waste_service_table SET route_id = ? WHERE waste_service_id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $newRouteId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Route selection updated successfully',
            'new_route_id' => $newRouteId
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update route selection']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
