<?php
header('Content-Type: application/json');
include '../includes/conn.php';

// Params: brgy_id (optional - "all" for all barangays), year (default current), month (default current)
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$brgy_id = isset($_GET['brgy_id']) ? $_GET['brgy_id'] : null;
$isAllBrgy = ($brgy_id === 'all' || $brgy_id === '' || $brgy_id === null);

if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// Compute month range
$start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
$endDate = new DateTime($start);
$endDate->modify('last day of this month')->setTime(23, 59, 59);
$end = $endDate->format('Y-m-d H:i:s');

if ($isAllBrgy) {
    // Get all barangays with their monthly waste data
    // First, get all barangays
    $brgySql = "SELECT brgy_id, barangay FROM barangays_table ORDER BY barangay";
    $brgyRes = $conn->query($brgySql);
    $allBarangays = [];
    
    while ($brgy = $brgyRes->fetch_assoc()) {
        $bId = (int)$brgy['brgy_id'];
        $bName = $brgy['barangay'];
        
        // Get monthly totals for this barangay for the year
        $yearSql = "SELECT MONTH(date) AS month_num, SUM(total_count) AS month_total
                    FROM sensor_agg_daily
                    WHERE brgy_id = ? AND YEAR(date) = ?
                    GROUP BY MONTH(date)";
        $stmtY = $conn->prepare($yearSql);
        $stmtY->bind_param('ii', $bId, $year);
        $stmtY->execute();
        $yearRes = $stmtY->get_result();
        $monthlyCounts = array_fill(0, 12, 0);
        while ($yr = $yearRes->fetch_assoc()) {
            $idx = max(1, min(12, (int)$yr['month_num'])) - 1;
            $monthlyCounts[$idx] = (int)$yr['month_total'];
        }
        $monthlyTons = array_map(function($c){ return round($c * 0.001, 3); }, $monthlyCounts);
        
        $stmtY->close();
        
        // Get total for current month
        $monthSql = "SELECT SUM(total_count) AS total_count
                     FROM sensor_agg_daily
                     WHERE brgy_id = ? AND date BETWEEN DATE(?) AND DATE(?)";
        $stmtM = $conn->prepare($monthSql);
        $stmtM->bind_param('iss', $bId, $start, $end);
        $stmtM->execute();
        $monthRes = $stmtM->get_result();
        $monthRow = $monthRes->fetch_assoc();
        $monthTotal = (int)($monthRow['total_count'] ?? 0);
        
        $stmtM->close();
        
        // Get waste for selected month (from monthlyTons array)
        $selectedMonthTons = isset($monthlyTons[$month - 1]) ? $monthlyTons[$month - 1] : 0;
        
        $allBarangays[] = [
            'brgy_id' => $bId,
            'barangay' => $bName,
            'monthlyTons' => $monthlyTons,
            'monthTotal' => round($monthTotal * 0.001, 2),
            'selectedMonthTons' => round($selectedMonthTons, 3) // Waste for selected month
        ];
    }
    
    // Calculate total for all barangays
    $totalTons = array_sum(array_column($allBarangays, 'monthTotal'));
    $totalCount = (int)($totalTons * 1000);
    
    echo json_encode([
        'success' => true,
        'isAllBrgy' => true,
        'barangay' => 'All Barangays',
        'brgy_id' => 'all',
        'year' => $year,
        'month' => $month,
        'total_count' => $totalCount,
        'tons' => round($totalTons, 2),
        'progress' => min(98, (int)round($totalTons * 100)),
        'allBarangays' => $allBarangays,
        'monthlyTons' => null, // Not used for all barangays
    ]);
    exit;
}

// Single barangay logic (existing code)
$brgy_id = (int)$brgy_id;

// Total for this month for the selected barangay
$sql = "SELECT b.barangay, SUM(a.total_count) AS total_count
        FROM sensor_agg_daily a
        JOIN barangays_table b ON a.brgy_id = b.brgy_id
        WHERE a.brgy_id = ? AND a.date BETWEEN DATE(?) AND DATE(?)
        GROUP BY a.brgy_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iss', $brgy_id, $start, $end);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

$barangay = $row['barangay'] ?? '';
$totalCount = (int)($row['total_count'] ?? 0);
$tons = round($totalCount * 0.001, 2);


// Yearly monthly totals for the selected barangay
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
$monthlyTons = array_map(function($c){ return round($c * 0.001, 3); }, $monthlyCounts);


// Optionally compute daily breakdown for the month to show richer chart later
$dailySql = "SELECT DAYOFMONTH(date) AS day_number, SUM(total_count) AS daily_count
             FROM sensor_agg_daily
             WHERE brgy_id = ? AND date BETWEEN DATE(?) AND DATE(?)
             GROUP BY date
             ORDER BY date";
$stmt2 = $conn->prepare($dailySql);
$stmt2->bind_param('iss', $brgy_id, $start, $end);
$stmt2->execute();
$dailyRes = $stmt2->get_result();
$dailyData = [];
while ($r = $dailyRes->fetch_assoc()) {
    $dailyData[] = [
        'day_number' => (int)$r['day_number'],
        'daily_count' => (int)$r['daily_count'],
        'daily_tons' => round(((int)$r['daily_count']) * 0.001, 3),
    ];
}


// Progress heuristic (cap at 98%)
$progress = 0;
if ($tons > 0) {
    $progress = (int)min(98, round($tons * 100));
}

echo json_encode([
    'success' => true,
    'isAllBrgy' => false,
    'barangay' => $barangay,
    'brgy_id' => $brgy_id,
    'year' => $year,
    'month' => $month,
    'total_count' => $totalCount,
    'tons' => $tons,
    'progress' => $progress,
    'dailyData' => $dailyData,
    'monthlyCounts' => $monthlyCounts,
    'monthlyTons' => $monthlyTons,
]);
?>
