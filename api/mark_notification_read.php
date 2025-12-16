<?php
require_once __DIR__ . '/../includes/conn.php';

session_start();

header('Content-Type: application/json');

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['id'])) {
        $id = $input['id'];
        
        try {
            if (isset($_SESSION['client_id'])) {
                $stmt = $conn->prepare("UPDATE client_notifications 
                                       SET is_read = 1 
                                       WHERE id = ? AND client_id = ?");
                $stmt->bind_param("ii", $id, $_SESSION['client_id']);
            } elseif (isset($_SESSION['admin_id'])) {
                $stmt = $conn->prepare("UPDATE admin_notifications 
                                       SET is_read = 1 
                                       WHERE id = ?");
                $stmt->bind_param("i", $id);
            }
            
            if (isset($stmt)) {
                $stmt->execute();
                $response['success'] = $stmt->affected_rows > 0;
                $stmt->close();
            }
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
        }
    }
}

echo json_encode($response);
?>