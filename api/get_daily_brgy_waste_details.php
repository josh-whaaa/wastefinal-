<?php
header('Content-Type: application/json');
include '../includes/conn.php';

try {
    // Input validation
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
    $week = isset($_GET['week']) ? (int)$_GET['week'] : ceil(date('j') / 7);
    $day = isset($_GET['day']) ? (int)$_GET['day'] : null;

    if ($month < 1 || $month > 12) {
        throw new Exception('Invalid month. Must be 1–12.');
    }
    if ($year < 2000 || $year > 2100) {
        throw new Exception('Invalid year.');
    }

    // Calculate week range
    $firstDayOfMonth = new DateTime("$year-$month-01");
    $firstMonday = clone $firstDayOfMonth;
    $dow = (int)$firstMonday->format('N');
    if ($dow != 1) {
        $firstMonday->modify('last Monday');
    }

    $weekMonday = clone $firstMonday;
    $daysToAdd = 7 * ($week - 1);
    $weekMonday->add(new DateInterval("P{$daysToAdd}D"));

    $weekSunday = clone $weekMonday;
    $weekSunday->add(new DateInterval('P6D'));

    $weekStart = $weekMonday->format('Y-m-d 00:00:00');
    $weekEnd = $weekSunday->format('Y-m-d 23:59:59');

    // Check if driver_waste_uploads table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'driver_waste_uploads'");
    $hasUploadsTable = $tableCheck && $tableCheck->num_rows > 0;
    
    // If specific day is requested, get data for that day only
    if ($day !== null) {
        $targetDate = clone $weekMonday;
        $targetDate->add(new DateInterval('P' . ($day - 1) . 'D'));
        $dayStart = $targetDate->format('Y-m-d 00:00:00');
        $dayEnd = $targetDate->format('Y-m-d 23:59:59');
        $dayDate = $targetDate->format('Y-m-d');
        
        $brgyData = [];
        
        // PRIORITY: Use driver_waste_uploads if available
        if ($hasUploadsTable) {
            $query = "
                SELECT 
                    b.brgy_id,
                    b.barangay,
                    COALESCE(SUM(dwu.waste_count), 0) as daily_count,
                    DATE(dwu.collection_date) as date_only,
                    DATE_FORMAT(dwu.collection_date, '%a') as day_name,
                    DAYOFMONTH(dwu.collection_date) as day_number,
                    GROUP_CONCAT(DISTINCT CONCAT(w.vehicle_name, ' (', w.vehicle_capacity, ')') SEPARATOR ', ') as vehicles,
                    GROUP_CONCAT(DISTINCT CONCAT(d.first_name, ' ', d.last_name) SEPARATOR ', ') as drivers,
                    GROUP_CONCAT(DISTINCT w.plate_no SEPARATOR ', ') as plate_numbers
                FROM 
                    barangays_table b
                LEFT JOIN 
                    driver_waste_uploads dwu ON b.brgy_id = dwu.brgy_id 
                    AND DATE(dwu.collection_date) = DATE(?)
                LEFT JOIN 
                    route_table r ON b.brgy_id = r.brgy_id
                LEFT JOIN 
                    waste_service_table w ON r.waste_service_id = w.waste_service_id
                LEFT JOIN 
                    driver_table d ON r.driver_id = d.driver_id
                GROUP BY 
                    b.brgy_id, b.barangay, DATE(dwu.collection_date)
                HAVING 
                    daily_count > 0
                ORDER BY 
                    daily_count DESC, b.barangay
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $dayDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $brgyData[] = [
                    'brgy_id' => (int)$row['brgy_id'],
                    'barangay' => $row['barangay'],
                    'daily_count' => (int)$row['daily_count'],
                    'date_only' => $row['date_only'],
                    'day_name' => $row['day_name'],
                    'day_number' => (int)$row['day_number'],
                    'tons' => round($row['daily_count'] * 0.001, 3),
                    'vehicles' => $row['vehicles'] ?: 'No vehicle assigned',
                    'drivers' => $row['drivers'] ?: 'No driver assigned',
                    'plate_numbers' => $row['plate_numbers'] ?: 'N/A'
                ];
            }
            $stmt->close();
        }

        echo json_encode([
            'success' => true,
            'date' => $targetDate->format('Y-m-d'),
            'day_name' => $targetDate->format('l'),
            'day_number' => (int)$targetDate->format('j'),
            'barangay_data' => $brgyData,
            'total_count' => array_sum(array_column($brgyData, 'daily_count')),
            'total_tons' => round(array_sum(array_column($brgyData, 'tons')), 3)
        ]);
        
    } else {
        // Get daily breakdown for the entire week
        $dailyBrgyData = [];
        $current = clone $weekMonday;
        
        for ($i = 0; $i < 7; $i++) {
            $dateStr = $current->format('Y-m-d');
            $dayStart = $current->format('Y-m-d 00:00:00');
            $dayEnd = $current->format('Y-m-d 23:59:59');
            
            $dayBrgyData = [];
            
            // PRIORITY: Use driver_waste_uploads if available
            if ($hasUploadsTable) {
                $query = "
                    SELECT 
                        b.brgy_id,
                        b.barangay,
                        COALESCE(SUM(dwu.waste_count), 0) as daily_count,
                        GROUP_CONCAT(DISTINCT CONCAT(w.vehicle_name, ' (', w.vehicle_capacity, ')') SEPARATOR ', ') as vehicles,
                        GROUP_CONCAT(DISTINCT CONCAT(d.first_name, ' ', d.last_name) SEPARATOR ', ') as drivers,
                        GROUP_CONCAT(DISTINCT w.plate_no SEPARATOR ', ') as plate_numbers
                    FROM 
                        barangays_table b
                    LEFT JOIN 
                        driver_waste_uploads dwu ON b.brgy_id = dwu.brgy_id 
                        AND DATE(dwu.collection_date) = DATE(?)
                    LEFT JOIN 
                        route_table r ON b.brgy_id = r.brgy_id
                    LEFT JOIN 
                        waste_service_table w ON r.waste_service_id = w.waste_service_id
                    LEFT JOIN 
                        driver_table d ON r.driver_id = d.driver_id
                    GROUP BY 
                        b.brgy_id, b.barangay
                    HAVING 
                        daily_count > 0
                    ORDER BY 
                        daily_count DESC, b.barangay
                ";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $dateStr);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $dayBrgyData[] = [
                        'brgy_id' => (int)$row['brgy_id'],
                        'barangay' => $row['barangay'],
                        'daily_count' => (int)$row['daily_count'],
                        'tons' => round($row['daily_count'] * 0.001, 3),
                        'vehicles' => $row['vehicles'] ?: 'No vehicle assigned',
                        'drivers' => $row['drivers'] ?: 'No driver assigned',
                        'plate_numbers' => $row['plate_numbers'] ?: 'N/A'
                    ];
                }
                $stmt->close();
            }
            

            $dayEntry = [
                'date' => $dateStr,
                'day_name' => $current->format('D'),
                'day_number' => (int)$current->format('j'),
                'barangay_data' => $dayBrgyData,
                'total_count' => array_sum(array_column($dayBrgyData, 'daily_count')),
                'total_tons' => round(array_sum(array_column($dayBrgyData, 'tons')), 3)
            ];

            $dailyBrgyData[] = $dayEntry;
            
            $current->add(new DateInterval('P1D'));
        }
        
        echo json_encode([
            'success' => true,
            'week_start' => $weekMonday->format('Y-m-d'),
            'week_end' => $weekSunday->format('Y-m-d'),
            'week_range' => $weekMonday->format('j') . ' - ' . $weekSunday->format('j'),
            'daily_data' => $dailyBrgyData
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
