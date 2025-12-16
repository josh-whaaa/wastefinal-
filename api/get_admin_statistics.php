<?php
session_start();
require_once '../includes/conn.php';

header('Content-Type: application/json');

// Allow access for admins and drivers (both need to see system-wide statistics)
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['driver_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    // Get current week dates (Monday to Sunday)
    $today = new DateTime();
    $dayOfWeek = $today->format('w'); // 0 = Sunday, 1 = Monday, etc.
    $daysFromMonday = ($dayOfWeek == 0) ? 6 : $dayOfWeek - 1;
    
    $monday = clone $today;
    $monday->modify('-' . $daysFromMonday . ' days');
    $monday->setTime(0, 0, 0);
    
    $sunday = clone $monday;
    $sunday->modify('+6 days');
    $sunday->setTime(23, 59, 59);
    
    $weekStart = $monday->format('Y-m-d H:i:s');
    $weekEnd = $sunday->format('Y-m-d H:i:s');
    
    // Check if driver_waste_uploads table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'driver_waste_uploads'");
    $hasUploadsTable = $tableCheck->rowCount() > 0;
    
    $weekly_avg_fill = 0;
    $weekly_waste_count = 0;
    $weekly_upload_count = 0;
    
    if ($hasUploadsTable) {
        // Get average bin fill percentage from ALL driver uploads (system-wide)
        $stmt = $pdo->prepare("SELECT AVG(bin_fill_percentage) as avg_fill, 
                                       SUM(waste_count) as total_count,
                                       COUNT(*) as upload_count
                               FROM driver_waste_uploads 
                               WHERE collection_date BETWEEN ? AND ?");
        $stmt->execute([$monday->format('Y-m-d'), $sunday->format('Y-m-d')]);
        $uploadData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $weekly_avg_fill = floatval($uploadData['avg_fill'] ?? 0);
        $weekly_waste_count = intval($uploadData['total_count'] ?? 0);
        $weekly_upload_count = intval($uploadData['upload_count'] ?? 0);
    }
    
    
    // Calculate average daily collection (this week)
    // Use actual days elapsed in the current week, not always 7
    $daysInWeek = 7;
    $daysElapsed = $today->diff($monday)->days + 1; // +1 to include today
    // Cap at 7 to avoid division issues if calculation is off
    $daysElapsed = min($daysElapsed, 7);
    // Use at least 1 to avoid division by zero
    $daysElapsed = max($daysElapsed, 1);
    
    // Average daily collection = total waste / days elapsed in week
    $avg_daily_collection = $daysElapsed > 0 ? $weekly_waste_count / $daysElapsed : 0;
    
    // ============================================
    // WEEKLY PERFORMANCE CALCULATION
    // ============================================
    // Formula: (Actual Waste Collected in volume % / Expected Weekly Target in volume %) * 100
    // 
    // Volume Conversion: 1 ton = 100% volume
    // - 1 count = 0.001 tons = 0.1% volume
    // - So: count * 0.1 = volume %
    // 
    // Expected Target: 1 ton/day * 7 days = 7 tons/week = 700% volume/week
    // - 7 tons = 7,000 count = 700% volume
    // 
    // This measures how well the system is performing against the expected target.
    // - 100% = Meeting or exceeding the weekly target (700% volume)
    // - < 100% = Below target (needs improvement)
    // - Capped at 100% (can't exceed 100% performance)
    // ============================================
    $expectedDailyTons = 1.0; // Expected daily collection target: 1 ton/day
    $expectedWeeklyTons = $expectedDailyTons * 7; // Expected weekly: 7 tons
    $expectedWeeklyCount = $expectedWeeklyTons * 1000; // Convert to count: 7,000 count
    $expectedWeeklyVolumePercent = $expectedWeeklyTons * 100; // Convert to volume %: 700% volume
    
    // Convert actual weekly waste count to volume percentage
    // 1 count = 0.001 tons = 0.1% volume
    $actualWeeklyVolumePercent = $weekly_waste_count * 0.1;
    
    // Calculate performance percentage
    if ($expectedWeeklyVolumePercent > 0) {
        $weekly_performance = min(100, ($actualWeeklyVolumePercent / $expectedWeeklyVolumePercent) * 100);
    } else {
        $weekly_performance = 0;
    }
    
    // ============================================
    // COLLECTION EFFICIENCY CALCULATION
    // ============================================
    // Formula: (Days with Collection / 7) * 100 * (Volume Quality Factor)
    // 
    // This measures how efficiently collections are happening:
    // 1. Frequency: Days with collection / 7 days (0-100%)
    // 2. Quality: Average daily collection volume compared to target (volume-based)
    // 
    // Final Efficiency = Frequency * (Volume Quality Factor)
    // - Volume Quality Factor = (Average Daily Collection Volume % / Target Daily Volume %)
    //   - Target: 1 ton/day = 100% volume
    //   - If average daily collection = 28% volume, quality factor = 28% / 100% = 0.28
    //   - If average daily collection = 100% volume, quality factor = 100% / 100% = 1.0
    //   - Rewards collecting closer to the daily target (1 ton/day = 100% volume)
    // 
    // Example:
    // - 3 days with collection = 42.86% frequency
    // - Average daily collection = 28% volume = 0.28 quality factor
    // - Efficiency = 42.86% * 0.28 = 12.0%
    // ============================================
    $days_with_collection = 0;
    if ($hasUploadsTable) {
        // Get days with collection
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT collection_date) as days_with_collection
                               FROM driver_waste_uploads 
                               WHERE collection_date BETWEEN ? AND ?
                               AND waste_count > 0");
        $stmt->execute([$monday->format('Y-m-d'), $sunday->format('Y-m-d')]);
        $daysData = $stmt->fetch(PDO::FETCH_ASSOC);
        $days_with_collection = intval($daysData['days_with_collection'] ?? 0);
    }
    
    // Calculate frequency component (days with collection / 7)
    $frequency_percentage = ($days_with_collection / $daysInWeek) * 100;
    
    // Calculate volume quality factor based on average daily collection
    // Target: 1 ton/day = 100% volume
    // Average daily collection is already calculated above as $avg_daily_collection (in count)
    // Convert to volume %: count * 0.1 = volume %
    $targetDailyVolumePercent = 100.0; // 1 ton/day = 100% volume
    $actualDailyVolumePercent = 0.0;
    
    if ($daysElapsed > 0 && $avg_daily_collection > 0) {
        // Convert average daily collection (count) to volume %
        // avg_daily_collection is in count, so: count * 0.1 = volume %
        $actualDailyVolumePercent = $avg_daily_collection * 0.1;
    }
    
    // Calculate volume quality factor
    // Cap at 1.0 (100%) - can't exceed target
    $volume_quality_factor = 0.0;
    if ($targetDailyVolumePercent > 0) {
        $volume_quality_factor = min(1.0, $actualDailyVolumePercent / $targetDailyVolumePercent);
    }
    
    // Final efficiency = frequency * volume quality factor
    $collection_efficiency = $frequency_percentage * $volume_quality_factor;
    
    // Ensure all values are numeric and have defaults
    $weekly_waste_count = floatval($weekly_waste_count ?? 0);
    $avg_daily_collection = floatval($avg_daily_collection ?? 0);
    $weekly_performance = floatval($weekly_performance ?? 0);
    $collection_efficiency = floatval($collection_efficiency ?? 0);
    
    echo json_encode([
        'success' => true,
        'weekly_waste_count' => $weekly_waste_count,
        'avg_daily_collection' => $avg_daily_collection,
        'weekly_performance' => $weekly_performance,
        'collection_efficiency' => $collection_efficiency,
        'week_range' => $monday->format('M d') . ' - ' . $sunday->format('M d, Y')
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

