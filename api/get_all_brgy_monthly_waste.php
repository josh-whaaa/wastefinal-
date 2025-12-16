<?php
header('Content-Type: application/json');
include '../includes/conn.php';

// Params: year (default current), month (default current), brgy_id (should be 'all')
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$brgy_id = isset($_GET['brgy_id']) ? $_GET['brgy_id'] : null;

// This API should only be called when brgy_id is 'all'
if ($brgy_id !== 'all' || $month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters - this API is only for "all" barangays']);
    exit;
}

// Compute month range
$start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
$endDate = new DateTime($start);
$endDate->modify('last day of this month')->setTime(23, 59, 59);
$end = $endDate->format('Y-m-d H:i:s');

// Get all barangays with their monthly totals for the current month
$sql = "SELECT b.brgy_id, b.barangay, SUM(a.total_count) AS total_count
        FROM sensor_agg_daily a
        JOIN barangays_table b ON a.brgy_id = b.brgy_id
        WHERE a.date BETWEEN DATE(?) AND DATE(?)
        GROUP BY a.brgy_id, b.barangay
        ORDER BY b.barangay";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$res = $stmt->get_result();
$barangayData = [];

while ($row = $res->fetch_assoc()) {
    $barangayData[] = [
        'brgy_id' => (int)$row['brgy_id'],
        'barangay' => $row['barangay'],
        'total_count' => (int)$row['total_count'],
        'tons' => round($row['total_count'] * 0.001, 2)
    ];
}

// Fallback to raw sensor table if no aggregated data
if (empty($barangayData)) {
    $sqlRaw = "SELECT b.brgy_id, b.barangay, SUM(s.count) AS total_count
               FROM sensor s
               JOIN barangays_table b ON s.brgy_id = b.brgy_id
               WHERE s.timestamp BETWEEN ? AND ?
               GROUP BY s.brgy_id, b.barangay
               ORDER BY b.barangay";
    $stmtR = $conn->prepare($sqlRaw);
    $stmtR->bind_param('ss', $start, $end);
    $stmtR->execute();
    $resR = $stmtR->get_result();
    
    while ($row = $resR->fetch_assoc()) {
        $barangayData[] = [
            'brgy_id' => (int)$row['brgy_id'],
            'barangay' => $row['barangay'],
            'total_count' => (int)$row['total_count'],
            'tons' => round($row['total_count'] * 0.001, 2)
        ];
    }
    $stmtR->close();
}

// Get yearly monthly totals for each barangay
$yearlyData = [];
foreach ($barangayData as $brgy) {
    $brgy_id = $brgy['brgy_id'];
    
    // Try sensor_agg_daily first
    $yearSql = "SELECT MONTH(date) AS month_num, SUM(total_count) AS month_total
                FROM sensor_agg_daily
                WHERE brgy_id = ? AND YEAR(date) = ?
                GROUP BY MONTH(date)";
    $stmtY = $conn->prepare($yearSql);
    $stmtY->bind_param('ii', $brgy_id, $year);
    $stmtY->execute();
    $yearRes = $stmtY->get_result();
    $monthlyCounts = array_fill(0, 12, 0);
    
    while ($yr = $yearRes->fetch_assoc()) {
        $idx = max(1, min(12, (int)$yr['month_num'])) - 1;
        $monthlyCounts[$idx] = (int)$yr['month_total'];
    }
    $stmtY->close();
    
    // Fallback to raw sensor table if no aggregated data
    $allZero = true;
    foreach ($monthlyCounts as $c) {
        if ($c > 0) {
            $allZero = false;
            break;
        }
    }
    
    if ($allZero) {
        $yearSqlRaw = "SELECT MONTH(timestamp) AS month_num, SUM(count) AS month_total
                       FROM sensor
                       WHERE brgy_id = ? AND YEAR(timestamp) = ?
                       GROUP BY MONTH(timestamp)";
        $stmtYR = $conn->prepare($yearSqlRaw);
        $stmtYR->bind_param('ii', $brgy_id, $year);
        $stmtYR->execute();
        $yearResR = $stmtYR->get_result();
        $monthlyCounts = array_fill(0, 12, 0);
        
        while ($yr = $yearResR->fetch_assoc()) {
            $idx = max(1, min(12, (int)$yr['month_num'])) - 1;
            $monthlyCounts[$idx] = (int)$yr['month_total'];
        }
        $stmtYR->close();
    }
    
    $monthlyTons = array_map(function($c) { return round($c * 0.001, 3); }, $monthlyCounts);
    
    $yearlyData[] = [
        'brgy_id' => $brgy_id,
        'barangay' => $brgy['barangay'],
        'monthlyTons' => $monthlyTons
    ];
}

// Calculate total waste for all barangays
$totalTons = array_sum(array_column($barangayData, 'tons'));
$totalCount = array_sum(array_column($barangayData, 'total_count'));

// Progress heuristic (cap at 98%)
$progress = 0;
if ($totalTons > 0) {
    $progress = (int)min(98, round($totalTons * 100));
}

echo json_encode([
    'success' => true,
    'barangay' => 'All Barangays',
    'brgy_id' => 'all',
    'year' => $year,
    'month' => $month,
    'total_count' => $totalCount,
    'tons' => round($totalTons, 2),
    'progress' => $progress,
    'barangayData' => $yearlyData,
    'monthlyTons' => array_map(function($monthIndex) use ($yearlyData) {
        $total = 0;
        foreach ($yearlyData as $brgy) {
            $total += $brgy['monthlyTons'][$monthIndex] ?? 0;
        }
        return $total;
    }, range(0, 11))
]);
?>
