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
    $notification_id = $_POST['notification_id'] ?? null;
    
    if (!$notification_id) {
        echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
        exit();
    }
    
    try {
        // Verify the notification belongs to the client
        $stmt = $pdo->prepare("SELECT id FROM client_notifications WHERE id = ? AND client_id = ?");
        $stmt->execute([$notification_id, $client_id]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Notification not found or access denied']);
            exit();
        }
        
        // Delete the notification
        $stmt = $pdo->prepare("DELETE FROM client_notifications WHERE id = ? AND client_id = ?");
        $stmt->execute([$notification_id, $client_id]);
        
        echo json_encode(['success' => true, 'message' => 'Notification deleted successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting notification: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
