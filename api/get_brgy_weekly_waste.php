<?php
header('Content-Type: application/json');
include '../includes/conn.php';

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$week = isset($_GET['week']) ? (int)$_GET['week'] : ceil(date('j') / 7);

$brgy_id = isset($_GET['brgy_id']) ? (int)$_GET['brgy_id'] : null;
if (!$brgy_id) {
    echo json_encode(['success' => false, 'error' => 'No barangay ID specified.']);
    exit;
}

// Calculate week range
$firstDayOfMonth = new DateTime("$year-$month-01");
$dow = (int)$firstDayOfMonth->format('N');
if ($dow != 1) {
    $firstDayOfMonth->modify('last Monday');
}
$weekMonday = clone $firstDayOfMonth;
$weekMonday->add(new DateInterval('P' . (7 * ($week - 1)) . 'D'));
$weekSunday = clone $weekMonday;
$weekSunday->add(new DateInterval('P6D'));
$weekStart = $weekMonday->format('Y-m-d 00:00:00');
$weekEnd = $weekSunday->format('Y-m-d 23:59:59');

// Join sensor and barangays_table to get barangay name and waste
$query = "SELECT b.barangay, SUM(a.total_count) as total_count FROM sensor_agg_daily a JOIN barangays_table b ON a.brgy_id = b.brgy_id WHERE a.brgy_id = ? AND a.date >= DATE(?) AND a.date <= DATE(?) GROUP BY a.brgy_id";
$stmt = $conn->prepare($query);
$stmt->bind_param('iss', $brgy_id, $weekStart, $weekEnd);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$totalCount = (int)($row['total_count'] ?? 0);
$barangay = $row['barangay'] ?? '';

// Fallback to raw `sensor` if aggregate had no data
if ($totalCount === 0) {
    $queryRaw = "SELECT b.barangay, SUM(s.count) as total_count FROM sensor s JOIN barangays_table b ON s.brgy_id = b.brgy_id WHERE s.brgy_id = ? AND s.timestamp >= ? AND s.timestamp <= ? GROUP BY s.brgy_id";
    $stmtR = $conn->prepare($queryRaw);
    $stmtR->bind_param('iss', $brgy_id, $weekStart, $weekEnd);
    $stmtR->execute();
    $resR = $stmtR->get_result();
    $rowR = $resR->fetch_assoc();
    if ($rowR) {
        $totalCount = (int)($rowR['total_count'] ?? 0);
        $barangay = $rowR['barangay'] ?? $barangay;
    }
    $stmtR->close();
}

// Calculate progress (limit to 98% maximum)
$tons = round($totalCount * 0.001, 2);
if ($tons <= 0) {
    $progress = 0;
} elseif ($tons >= 0.98) {
    $progress = 98;
} else {
    $progress = intval(round($tons * 100));
    if ($progress > 98) $progress = 98;
}

$response = [
    'success' => true,
    'barangay' => $barangay,
    'brgy_id' => $brgy_id,
    'week' => $week,
    'total_count' => $totalCount,
    'tons' => round($totalCount * 0.001, 2),
    'progress' => $progress
];
echo json_encode($response);
