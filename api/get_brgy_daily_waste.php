<?php
header('Content-Type: application/json');
include '../includes/conn.php';

$brgy_id = isset($_GET['brgy_id']) ? (int)$_GET['brgy_id'] : 0;
$days = isset($_GET['days']) ? max(1, min(90, (int)$_GET['days'])) : 7;
if ($brgy_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid brgy_id']);
    exit;
}

$end = date('Y-m-d 23:59:59');
$start = date('Y-m-d 00:00:00', strtotime("-" . ($days - 1) . " days"));

$sql = "SELECT DATE(timestamp) AS date_only, SUM(count) AS daily_count
        FROM sensor
        WHERE brgy_id = ? AND timestamp BETWEEN ? AND ?
        GROUP BY DATE(timestamp)
        ORDER BY DATE(timestamp)";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iss', $brgy_id, $start, $end);
$stmt->execute();
$res = $stmt->get_result();
$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = [
        'date' => $row['date_only'],
        'daily_count' => (int)$row['daily_count'],
        'daily_tons' => round(((int)$row['daily_count']) * 0.001, 3)
    ];
}

echo json_encode(['success' => true, 'days' => $days, 'data' => $data]);
?>


