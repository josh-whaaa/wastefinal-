<?php
session_start();
include '../includes/conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['client_id']) && !isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    $response = ['success' => true, 'hasNewNotifications' => false, 'count' => 0];
    
    if (isset($_SESSION['client_id'])) {
        // Check for new client notifications
        $client_id = $_SESSION['client_id'];
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as unread_count 
            FROM client_notifications 
            WHERE client_id = ? AND is_read = 0
        ");
        $stmt->execute([$client_id]);
        $result = $stmt->fetch();
        
        $unread_count = $result['unread_count'] ?? 0;
        $response['hasNewNotifications'] = $unread_count > 0;
        $response['count'] = $unread_count;
        
    } elseif (isset($_SESSION['admin_id'])) {
        // Check for new admin notifications (admin_notifications table doesn't have admin_id column)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as unread_count 
            FROM admin_notifications 
            WHERE is_read = 0
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        
        $admin_unread = $result['unread_count'] ?? 0;
        
        // Check for new client requests
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as new_requests 
            FROM client_requests 
            WHERE status = 'pending' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        
        $new_requests = $result['new_requests'] ?? 0;
        
        $total_new = $admin_unread + $new_requests;
        $response['hasNewNotifications'] = $total_new > 0;
        $response['count'] = $total_new;
        $response['admin_notifications'] = $admin_unread;
        $response['new_requests'] = $new_requests;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Notification check error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error checking notifications: ' . $e->getMessage()]);
}
?>
