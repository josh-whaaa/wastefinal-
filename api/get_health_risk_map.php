<?php
header('Content-Type: application/json');
include '../includes/conn.php';

// Parameters: lookback_days (default 7), thresholds (tons)
$lookbackDays = isset($_GET['lookback_days']) ? max(1, (int)$_GET['lookback_days']) : 7;
$highThreshold = isset($_GET['high_tons']) ? (float)$_GET['high_tons'] : 3.0;   // example: >= 3 tons in lookback => high
$medThreshold  = isset($_GET['med_tons'])  ? (float)$_GET['med_tons']  : 1.0;   // example: >= 1 ton => medium

try {
    $start = date('Y-m-d 00:00:00', strtotime('-' . $lookbackDays . ' days'));
    $end   = date('Y-m-d 23:59:59');

    // Aggregate recent waste per barangay (last N days)
    $sql = "
        SELECT b.brgy_id, b.barangay, b.latitude, b.longitude,
               SUM(s.count) AS total_count
        FROM barangays_table b
        LEFT JOIN sensor s ON s.brgy_id = b.brgy_id AND s.timestamp BETWEEN ? AND ?
        GROUP BY b.brgy_id, b.barangay, b.latitude, b.longitude
        ORDER BY b.barangay ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $start, $end);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $count = (int)($r['total_count'] ?? 0);
        $tons = $count * 0.001; // consistent conversion
        $risk = 'low';
        if ($tons >= $highThreshold) {
            $risk = 'high';
        } elseif ($tons >= $medThreshold) {
            $risk = 'medium';
        }
        $rows[] = [
            'brgy_id'   => (int)$r['brgy_id'],
            'barangay'  => $r['barangay'],
            'latitude'  => $r['latitude'] !== null ? (float)$r['latitude'] : null,
            'longitude' => $r['longitude'] !== null ? (float)$r['longitude'] : null,
            'count'     => $count,
            'tons'      => round($tons, 3),
            'risk'      => $risk
        ];
    }

    echo json_encode([
        'success' => true,
        'lookback_days' => $lookbackDays,
        'high_tons' => $highThreshold,
        'med_tons' => $medThreshold,
        'data' => $rows
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>


