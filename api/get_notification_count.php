<?php
require_once __DIR__ . '/../includes/conn.php';

session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'notifications' => []];

try {
    if (isset($_SESSION['client_id'])) {
        $client_id = $_SESSION['client_id'];
        $stmt = $conn->prepare("SELECT id, title, message, is_read, created_at 
                               FROM client_notifications 
                               WHERE client_id = ? 
                               ORDER BY created_at DESC 
                               LIMIT 10");
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $response['notifications'] = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $response['success'] = true;
        
    } elseif (isset($_SESSION['admin_id'])) {
        $stmt = $conn->prepare("SELECT id, title, message, is_read, created_at 
                               FROM admin_notifications 
                               ORDER BY created_at DESC 
                               LIMIT 10");
        $stmt->execute();
        $result = $stmt->get_result();
        $response['notifications'] = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $response['success'] = true;
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>