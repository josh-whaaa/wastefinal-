<?php
session_start();
include '../includes/conn.php';

if (!isset($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_SESSION['client_id'];
    $request_id = $_POST['request_id'] ?? null;
    
    if (!$request_id) {
        echo json_encode(['success' => false, 'message' => 'Request ID is required']);
        exit();
    }
    
    try {
        // Verify the request belongs to the client
        $stmt = $pdo->prepare("SELECT id FROM client_requests WHERE id = ? AND client_id = ?");
        $stmt->execute([$request_id, $client_id]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Request not found or access denied']);
            exit();
        }
        
        // Delete the request
        $stmt = $pdo->prepare("DELETE FROM client_requests WHERE id = ? AND client_id = ?");
        $stmt->execute([$request_id, $client_id]);
        
        echo json_encode(['success' => true, 'message' => 'Request deleted successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting request: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
