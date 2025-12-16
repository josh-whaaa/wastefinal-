<?php
header('Content-Type: application/json');
include '../includes/conn.php';

try {
    // Check if driver_waste_uploads table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'driver_waste_uploads'");
    $hasUploadsTable = $tableCheck && $tableCheck->num_rows > 0;
    
    $monthlyCount = 0;
    
    if ($hasUploadsTable) {
        // Get monthly waste count from driver_waste_uploads
        $stmt = $conn->prepare("SELECT COALESCE(SUM(waste_count), 0) AS monthly_count
                                FROM driver_waste_uploads
                                WHERE MONTH(collection_date) = MONTH(CURDATE())
                                  AND YEAR(collection_date) = YEAR(CURDATE())");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $monthlyCount = $row['monthly_count'] ?? 0;
        $stmt->close();
    }
    
    // Convert count to tons (1 count = 0.001 tons)
    $monthlyTons = $monthlyCount * 0.001;
    
    echo json_encode([
        'success' => true,
        'monthlyCount' => (int)$monthlyCount,
        'monthlyTons' => round($monthlyTons, 2),
        'month' => date('F Y')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>


