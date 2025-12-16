<?php
/**
 * Delete Admin Notification API
 * Handles deletion of admin notifications
 */

session_start();
require_once '../includes/conn.php';

header('Content-Type: application/json');

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['notification_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing notification ID'
    ]);
    exit;
}

$notification_id = intval($input['notification_id']);

try {
    // Delete the notification
    $stmt = $conn->prepare("DELETE FROM admin_notifications WHERE id = ?");
    $stmt->bind_param("i", $notification_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Notification not found'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete notification'
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
