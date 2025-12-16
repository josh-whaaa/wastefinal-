<?php
header('Content-Type: application/json');
include '../includes/conn.php';

try {
    // -----------------------------
    // Input Validation & Defaults
    // -----------------------------
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
    $week = isset($_GET['week']) ? (int)$_GET['week'] : ceil(date('j') / 7); // Current week of month

    if ($month < 1 || $month > 12) {
        throw new Exception('Invalid month. Must be 1–12.');
    }
    if ($year < 2000 || $year > 2100) {
        throw new Exception('Invalid year.');
    }

    // -----------------------------
    // Create DateTime objects safely
    // -----------------------------
    $firstDayOfMonth = new DateTime("$year-$month-01");
    $lastDayOfMonth = clone $firstDayOfMonth;
    $lastDayOfMonth->modify('last day of this month'); // Safe way to get last day

    // -----------------------------
    // Calculate First Monday of the Month
    // -----------------------------
    $firstMonday = clone $firstDayOfMonth;
    $dow = (int)$firstMonday->format('N'); // 1=Mon, 7=Sun
    if ($dow != 1) {
        $firstMonday->modify('last Monday');
    }

    // -----------------------------
    // Determine Selected Week: Monday of week #N
    // -----------------------------
    $weekMonday = clone $firstMonday;
    $daysToAdd = 7 * ($week - 1);
    $weekMonday->add(new DateInterval("P{$daysToAdd}D"));

    $weekSunday = clone $weekMonday;
    $weekSunday->add(new DateInterval('P6D'));

    $weekStart = $weekMonday->format('Y-m-d 00:00:00');
    $weekEnd = $weekSunday->format('Y-m-d 23:59:59');

    // -----------------------------
    // Fetch Daily Data from driver_waste_uploads table only
    // -----------------------------
    $tableCheck = $conn->query("SHOW TABLES LIKE 'driver_waste_uploads'");
    $hasUploadsTable = $tableCheck && $tableCheck->num_rows > 0;
    
    $dailyData = [];
    
    if ($hasUploadsTable) {
        // Get daily waste count from driver_waste_uploads
        $dailyQuery = "
            SELECT 
                DATE(collection_date) as date_only,
                DATE_FORMAT(collection_date, '%a') as day_name,
                DAYOFMONTH(collection_date) as day_number,
                COALESCE(SUM(waste_count), 0) as daily_count
            FROM 
                driver_waste_uploads
            WHERE 
                collection_date >= ? AND collection_date <= ?
            GROUP BY 
                DATE(collection_date)
            ORDER BY 
                DATE(collection_date)
        ";
        $stmt = $conn->prepare($dailyQuery);
        $stmt->bind_param("ss", $weekStart, $weekEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $dailyData[] = [
                'date_only' => $row['date_only'],
                'day_name' => $row['day_name'],
                'day_number' => (int)$row['day_number'],
                'daily_count' => (int)$row['daily_count']
            ];
        }
        $stmt->close();
    }

    // -----------------------------
    // Build Full Week: Mon to Sun (fill missing days)
    // -----------------------------
    $fullWeekData = [];
    $current = clone $weekMonday;

    for ($i = 0; $i < 7; $i++) {
        $dateStr = $current->format('Y-m-d');
        $match = null;
        foreach ($dailyData as $day) {
            if ($day['date_only'] === $dateStr) {
                $match = $day;
                break;
            }
        }

        $fullWeekData[] = $match ? $match : [
            'day_name' => $current->format('D'),
            'day_number' => (int)$current->format('j'),
            'daily_count' => 0,
            'date_only' => $dateStr
        ];

        $current->add(new DateInterval('P1D'));
    }

    // -----------------------------
    // Get All Weekly Totals for This Month (with utilization)
    // -----------------------------
    $weeklyData = [];
    $currentWeekStart = clone $firstMonday;

    while ($currentWeekStart <= $lastDayOfMonth) {
        $weekEndCheck = clone $currentWeekStart;
        $weekEndCheck->add(new DateInterval('P6D')); // Sunday

        // Only include weeks that overlap the current month
        $startStr = $currentWeekStart->format('Y-m-d');
        $endStr = $weekEndCheck->format('Y-m-d');

        if ($weekEndCheck >= $firstDayOfMonth && $currentWeekStart <= $lastDayOfMonth) {
            // Check if driver_waste_uploads table exists
            $tableCheck2 = $conn->query("SHOW TABLES LIKE 'driver_waste_uploads'");
            $hasUploadsTable2 = $tableCheck2 && $tableCheck2->num_rows > 0;
            
            if ($hasUploadsTable2) {
                // Get weekly total from driver_waste_uploads
                $query = "SELECT COALESCE(SUM(waste_count), 0) as total_count 
                         FROM driver_waste_uploads 
                         WHERE collection_date >= ? AND collection_date <= ?";
            } else {
                // Fallback to sensor table
                $query = "SELECT SUM(count) as total_count 
                         FROM sensor 
                         WHERE sensor_id = 1 AND timestamp >= ? AND timestamp <= ?";
            }
            
            $stmt2 = $conn->prepare($query);
            $stmt2->bind_param("ss", $startStr, $endStr);
            $stmt2->execute();
            $res = $stmt2->get_result();
            $row = $res->fetch_assoc();
            $totalCount = (int)($row['total_count'] ?? 0);
            $stmt2->close();

            // Format date range: "Aug 4–10" or "Jul 29 – Aug 5"
            $startDay = (int)$currentWeekStart->format('j');
            $endDay = (int)$weekEndCheck->format('j');
            $startMonth = $currentWeekStart->format('M');
            $endMonth = $weekEndCheck->format('M');
            $dateRange = ($startMonth === $endMonth)
                ? "$startMonth $startDay-$endDay"
                : "$startMonth $startDay - $endMonth $endDay";

            // Compute utilization against a simple expected target
            // ExpectedPerDayTons can be tuned; keep aligned with frontend's 2 tons/day
            $expectedPerDayTons = 2.0;
            $expectedWeekTons = $expectedPerDayTons * 7.0;
            $weekTons = $totalCount * 0.001;
            $utilization = $expectedWeekTons > 0 ? ($weekTons / $expectedWeekTons) * 100.0 : 0.0;

            $weeklyData[] = [
                'week_of_month' => count($weeklyData) + 1,
                'date_range' => $dateRange,
                'total_count' => $totalCount,
                'utilization' => round($utilization, 1)
            ];
        }

        $currentWeekStart->add(new DateInterval('P7D'));
    }

    // -----------------------------
    // Last Week's Total (Previous Mon–Sun) - Use driver_waste_uploads (preferred) or sensor table (fallback)
    // -----------------------------
    $lastWeekStart = date('Y-m-d', strtotime('last week monday'));
    $lastWeekEnd = date('Y-m-d', strtotime('last week sunday'));

    $tableCheck3 = $conn->query("SHOW TABLES LIKE 'driver_waste_uploads'");
    $hasUploadsTable3 = $tableCheck3 && $tableCheck3->num_rows > 0;
    
    $lastWeekTons = "0.00";
    if ($hasUploadsTable3) {
        // Get last week total from driver_waste_uploads
        $lastWeekQuery = "SELECT COALESCE(SUM(waste_count), 0) as total_count 
                         FROM driver_waste_uploads 
                         WHERE DATE(collection_date) BETWEEN DATE(?) AND DATE(?)";
        $stmtLast = $conn->prepare($lastWeekQuery);
        $stmtLast->bind_param("ss", $lastWeekStart, $lastWeekEnd);
        $stmtLast->execute();
        $lastRes = $stmtLast->get_result();
        $lastRow = $lastRes->fetch_assoc();
        $lastWeekTons = $lastRow && $lastRow['total_count'] ? number_format($lastRow['total_count'] * 0.001, 2) : "0.00";
        $stmtLast->close();
    }

    // -----------------------------
    // Final Output (include weeklyUtilization for the selected week)
    // -----------------------------
    // Determine utilization for the selected week card if available
    $selectedWeekUtil = null;
    foreach ($weeklyData as $w) {
        if ((int)$w['week_of_month'] === (int)$week) {
            $selectedWeekUtil = $w['utilization'];
            break;
        }
    }

    echo json_encode([
        'success' => true,
        'month' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
        'weeklyData' => $weeklyData,
        'dailyData' => $fullWeekData,
        'selectedWeek' => $week,
        'weekRange' => $weekMonday->format('j') . ' - ' . $weekSunday->format('j'),
        'lastWeekWaste' => $lastWeekTons,
        'weeklyUtilization' => $selectedWeekUtil
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>