<?php
session_start();
require_once '../includes/conn.php';

header('Content-Type: application/json');

// Allow access only for drivers
if (!isset($_SESSION['driver_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$driver_id = $_SESSION['driver_id'];
$limit = intval($_GET['limit'] ?? 10);

try {
    // Check if table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'driver_waste_uploads'");
    if ($tableCheck->rowCount() === 0) {
        echo json_encode(['success' => true, 'uploads' => []]);
        exit();
    }

    $stmt = $pdo->prepare("SELECT collection_date, barangay, bin_fill_percentage, waste_count as count, weight, created_at 
                           FROM driver_waste_uploads 
                           WHERE driver_id = ? 
                           ORDER BY created_at DESC 
                           LIMIT ?");
    $stmt->execute([$driver_id, $limit]);
    $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'uploads' => $uploads
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

