<?php
session_start();
require '../includes/conn.php';

if (!isset($_SESSION['client_id']) || !isset($_GET['id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$client_id = $_SESSION['client_id'];
$id = $_GET['id'];

$stmt = $pdo->prepare("UPDATE client_notifications SET is_read = 1 WHERE id = ? AND client_id = ?");
$stmt->execute([$id, $client_id]);

echo 'success';
?>
