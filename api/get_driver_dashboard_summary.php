<?php
session_start();
require_once '../includes/conn.php';

header('Content-Type: application/json');

// Allow access only for drivers
if (!isset($_SESSION['driver_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$driver_id = intval($_GET['driver_id'] ?? $_SESSION['driver_id']);

try {
    // Get driver's barangay IDs (from their uploads or assigned routes)
    $brgy_ids = [];
    
    // Check if driver_waste_uploads table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'driver_waste_uploads'");
    if ($tableCheck->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT DISTINCT brgy_id FROM driver_waste_uploads WHERE driver_id = ?");
        $stmt->execute([$driver_id]);
        $brgy_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // If no uploads, try to get from route_table
    if (empty($brgy_ids)) {
        $stmt = $pdo->prepare("SELECT DISTINCT brgy_id FROM route_table WHERE driver_id = ?");
        $stmt->execute([$driver_id]);
        $brgy_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // 1. Collected Waste Today - Use driver_waste_uploads table only
    $todayDate = date('Y-m-d');
    $todayTons = 0;
    $todayCount = 0;
    
    // Check if driver_waste_uploads table exists
    if ($tableCheck->rowCount() > 0) {
        // Get today's waste from driver_waste_uploads for this driver
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(waste_count), 0) as today_count 
                               FROM driver_waste_uploads 
                               WHERE driver_id = ? AND DATE(collection_date) = DATE(?)");
        $stmt->execute([$driver_id, $todayDate]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $todayCount = intval($result['today_count'] ?? 0);
        $todayTons = $todayCount * 0.001; // Convert count to tons
    }
    
    // 2. Monthly Collected Waste - Use driver_waste_uploads table only
    $monthlyCount = 0;
    
    if ($tableCheck->rowCount() > 0) {
        // Get monthly waste from driver_waste_uploads for this driver
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(waste_count), 0) as monthly_count 
                               FROM driver_waste_uploads 
                               WHERE driver_id = ? 
                               AND MONTH(collection_date) = MONTH(CURDATE())
                               AND YEAR(collection_date) = YEAR(CURDATE())");
        $stmt->execute([$driver_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $monthlyCount = intval($result['monthly_count'] ?? 0);
    }
    $monthlyTons = $monthlyCount * 0.001;
    
    // 3. Forecasted Waste Volume (Next Week) - Use average of last 4 weeks from driver_waste_uploads
    $forecastedTons = 0;
    
    if ($tableCheck->rowCount() > 0) {
        // Get current week dates
        $today = new DateTime();
        $dayOfWeek = $today->format('w');
        $daysFromMonday = ($dayOfWeek == 0) ? 6 : $dayOfWeek - 1;
        $monday = clone $today;
        $monday->modify('-' . $daysFromMonday . ' days');
        $monday->setTime(0, 0, 0);
        
        // Get last 4 weeks of data from driver_waste_uploads (each week Monday to Sunday)
        $weekTotals = [];
        for ($i = 1; $i <= 4; $i++) {
            $weekStart = clone $monday;
            $weekStart->modify('-' . ($i * 7) . ' days');
            $weekEnd = clone $weekStart;
            $weekEnd->modify('+6 days');
            $weekEnd->setTime(23, 59, 59);
            
            $weekStartDate = $weekStart->format('Y-m-d');
            $weekEndDate = $weekEnd->format('Y-m-d');
            
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(waste_count), 0) as weekly_count 
                                   FROM driver_waste_uploads 
                                   WHERE driver_id = ? 
                                   AND DATE(collection_date) BETWEEN DATE(?) AND DATE(?)");
            $stmt->execute([$driver_id, $weekStartDate, $weekEndDate]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $weekCount = intval($result['weekly_count'] ?? 0);
            if ($weekCount > 0) {
                $weekTotals[] = $weekCount;
            }
        }
        
        if (!empty($weekTotals)) {
            $avgWeeklyCount = array_sum($weekTotals) / count($weekTotals);
            $forecastedTons = $avgWeeklyCount * 0.001;
        } else {
            // Fallback: use current week as forecast if no historical data
            $forecastedTons = $todayTons * 7; // Estimate based on today's collection
        }
    }
    
    // 4. Route Collection Progress - Check driver's assigned route completion
    $progress = 0;
    $totalRoutes = 0;
    $completedRoutes = 0;
    
    // Get driver's assigned route
    $stmt = $pdo->prepare("SELECT w.route_id, r.start_point, r.end_point 
                           FROM waste_service_table w 
                           LEFT JOIN route_table r ON w.route_id = r.route_id 
                           WHERE w.driver_id = ? AND w.route_id IS NOT NULL");
    $stmt->execute([$driver_id]);
    $routeData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($routeData && $routeData['route_id']) {
        $totalRoutes = 1;
        
        // Get current week
        $today = new DateTime();
        $dayOfWeek = $today->format('w');
        $daysFromMonday = ($dayOfWeek == 0) ? 6 : $dayOfWeek - 1;
        $monday = clone $today;
        $monday->modify('-' . $daysFromMonday . ' days');
        $monday->setTime(0, 0, 0);
        $sunday = clone $monday;
        $sunday->modify('+6 days');
        $sunday->setTime(23, 59, 59);
        
        $weekStart = $monday->format('Y-m-d H:i:s');
        $weekEnd = $sunday->format('Y-m-d H:i:s');
        
        // Check if driver has waste uploads this week (indicating route completion)
        if ($tableCheck->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as upload_count 
                                   FROM driver_waste_uploads 
                                   WHERE driver_id = ? 
                                   AND collection_date BETWEEN ? AND ?
                                   AND bin_fill_percentage > 0");
            $stmt->execute([$driver_id, $monday->format('Y-m-d'), $sunday->format('Y-m-d')]);
            $uploadResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (intval($uploadResult['upload_count'] ?? 0) > 0) {
                $completedRoutes = 1;
                $progress = 100.0;
            }
        }
    }
    
    // Calculate Waste Collected This Week (current week, Monday to Sunday)
    $today = new DateTime();
    $dayOfWeek = $today->format('w');
    $daysFromMonday = ($dayOfWeek == 0) ? 6 : $dayOfWeek - 1;
    
    $monday = clone $today;
    $monday->modify('-' . $daysFromMonday . ' days');
    $monday->setTime(0, 0, 0);
    
    $sunday = clone $monday;
    $sunday->modify('+6 days');
    $sunday->setTime(23, 59, 59);
    
    $weekStart = $monday->format('Y-m-d');
    $weekEnd = $sunday->format('Y-m-d');
    
    $weeklyWasteCount = 0;
    if ($tableCheck->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(waste_count), 0) as weekly_count 
                               FROM driver_waste_uploads 
                               WHERE driver_id = ? 
                               AND DATE(collection_date) BETWEEN DATE(?) AND DATE(?)");
        $stmt->execute([$driver_id, $weekStart, $weekEnd]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $weeklyWasteCount = intval($result['weekly_count'] ?? 0);
    }
    $weeklyTons = $weeklyWasteCount * 0.001;
    
    // Calculate Average Daily Collection (this week)
    $daysElapsed = $today->diff($monday)->days + 1; // +1 to include today
    $daysElapsed = min($daysElapsed, 7); // Cap at 7
    $daysElapsed = max($daysElapsed, 1); // At least 1
    $avgDailyCollection = $daysElapsed > 0 ? $weeklyWasteCount / $daysElapsed : 0;
    $avgDailyTons = $avgDailyCollection * 0.001;
    
    // ============================================
    // COLLECTION EFFICIENCY CALCULATION (Volume-based, matching admin dashboard)
    // ============================================
    // Formula: (Days with Collection / 7) * 100 * (Volume Quality Factor)
    // Volume Quality Factor = (Average Daily Collection Volume % / Target Daily Volume %)
    // Target: 1 ton/day = 100% volume
    // ============================================
    $daysWithCollection = 0;
    if ($tableCheck->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT collection_date) as days_with_collection
                               FROM driver_waste_uploads 
                               WHERE driver_id = ?
                               AND collection_date BETWEEN DATE(?) AND DATE(?)
                               AND waste_count > 0");
        $stmt->execute([$driver_id, $weekStart, $weekEnd]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $daysWithCollection = intval($result['days_with_collection'] ?? 0);
    }
    
    // Calculate frequency component (days with collection / 7)
    $frequencyPercentage = ($daysWithCollection / 7) * 100;
    
    // Calculate volume quality factor based on average daily collection
    // Target: 1 ton/day = 100% volume
    $targetDailyVolumePercent = 100.0; // 1 ton/day = 100% volume
    $actualDailyVolumePercent = 0.0;
    
    if ($daysElapsed > 0 && $avgDailyCollection > 0) {
        // Convert average daily collection (count) to volume %
        // avgDailyCollection is in count, so: count * 0.1 = volume %
        $actualDailyVolumePercent = $avgDailyCollection * 0.1;
    }
    
    // Calculate volume quality factor
    // Cap at 1.0 (100%) - can't exceed target
    $volumeQualityFactor = 0.0;
    if ($targetDailyVolumePercent > 0) {
        $volumeQualityFactor = min(1.0, $actualDailyVolumePercent / $targetDailyVolumePercent);
    }
    
    // Final efficiency = frequency * volume quality factor
    $collectionEfficiency = $frequencyPercentage * $volumeQualityFactor;
    
    echo json_encode([
        'success' => true,
        'todayTons' => round($todayTons, 2),
        'monthlyTons' => round($monthlyTons, 2),
        'forecastedTons' => round($forecastedTons, 2),
        'routeProgress' => round($progress, 1),
        'totalRoutes' => $totalRoutes,
        'completedRoutes' => $completedRoutes,
        // New fields matching admin dashboard calculations
        'weeklyTons' => round($weeklyTons, 2),
        'weeklyWasteCount' => $weeklyWasteCount,
        'avgDailyCollection' => round($avgDailyCollection, 0), // Count, not tons
        'avgDailyTons' => round($avgDailyTons, 2),
        'collectionEfficiency' => round($collectionEfficiency, 0)
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

