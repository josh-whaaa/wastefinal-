<?php
header('Content-Type: application/json');
include '../includes/conn.php';

try {
    // Check if driver_waste_uploads table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'driver_waste_uploads'");
    $hasUploadsTable = $tableCheck && $tableCheck->num_rows > 0;
    
    // Today's total waste collected from driver_waste_uploads
    $todayDate = date('Y-m-d');
    $todayTotalRaw = 0;
    $lastWeekTons = 0;
    $twoWeeksAgoTons = 0;
    
    if ($hasUploadsTable) {
        // Get today's total waste count from driver_waste_uploads
        $stmt = $conn->prepare("SELECT COALESCE(SUM(waste_count), 0) as today_total 
                               FROM driver_waste_uploads 
                               WHERE DATE(collection_date) = DATE(?)");
        $stmt->bind_param("s", $todayDate);
        $stmt->execute();
        $todayRow = $stmt->get_result()->fetch_assoc();
        $todayTotalRaw = isset($todayRow['today_total']) && $todayRow['today_total'] !== null ? intval($todayRow['today_total']) : 0;
        $stmt->close();

        // Last week: Use driver_waste_uploads table
        $lastMon = date('Y-m-d', strtotime('last week monday'));
        $lastSun = date('Y-m-d', strtotime('last week sunday'));
        
        $stmt = $conn->prepare("SELECT COALESCE(SUM(waste_count), 0) as total_count 
                               FROM driver_waste_uploads 
                               WHERE DATE(collection_date) BETWEEN DATE(?) AND DATE(?)");
        $stmt->bind_param("ss", $lastMon, $lastSun);
        $stmt->execute();
        $lastWeekRaw = ($stmt->get_result()->fetch_row()[0] ?? 0);
        $lastWeekTons = $lastWeekRaw * 0.001;
        $stmt->close();

        // Two weeks ago: Use driver_waste_uploads table
        $twoMon = date('Y-m-d', strtotime('last week monday -7 days'));
        $twoSun = date('Y-m-d', strtotime('last week sunday -7 days'));
        
        $stmt = $conn->prepare("SELECT COALESCE(SUM(waste_count), 0) as total_count 
                               FROM driver_waste_uploads 
                               WHERE DATE(collection_date) BETWEEN DATE(?) AND DATE(?)");
        $stmt->bind_param("ss", $twoMon, $twoSun);
        $stmt->execute();
        $twoWeeksAgoRaw = ($stmt->get_result()->fetch_row()[0] ?? 0);
        $twoWeeksAgoTons = $twoWeeksAgoRaw * 0.001;
        $stmt->close();
    }
    
    $todayTons = $todayTotalRaw * 0.001;

    // === OPTION 1: Week-over-week % change, capped at ±100% ===
    $pctChange = $twoWeeksAgoTons > 0 
        ? (($lastWeekTons - $twoWeeksAgoTons) / $twoWeeksAgoTons) * 100 
        : ($lastWeekTons > 0 ? 100 : 0);

    $pctChange = max(-100, min($pctChange, 100)); // Clamp between -100% and +100%
    $pctChange = round($pctChange, 1);

    // === OPTION 2: Utilization % (Recommended if you want 0–100%) ===
    $maxCapacityPerWeek = 100.0; // ← Adjust this to your real max (e.g., 40 tons?)
    $utilizationPercent = ($lastWeekTons / $maxCapacityPerWeek) * 100;
    $utilizationPercent = min(100, $utilizationPercent); // Can't exceed 100%
    $utilizationPercent = round($utilizationPercent, 1);

    // ✅ Send both (or choose one). We'll send both so you can decide in JS.
    echo json_encode([
        'success' => true,
        'todayTons' => round($todayTons, 2),
        'todayLatestRaw' => $todayTotalRaw,  // Changed to total instead of latest
        'todayTotalRaw' => $todayTotalRaw,   // Add alias for clarity
        'lastWeekTons' => round($lastWeekTons, 2),
        'lastWeekPercentageChange' => $pctChange,           // capped growth %
        'weeklyUtilization' => $utilizationPercent         // 0–100% of capacity
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>