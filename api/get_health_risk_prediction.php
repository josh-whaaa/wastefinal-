<?php
header('Content-Type: application/json');
include '../includes/conn.php';

// Parameters
$lookbackDays = isset($_GET['lookback_days']) ? max(1, (int)$_GET['lookback_days']) : 14;
$predictionDays = isset($_GET['prediction_days']) ? max(1, (int)$_GET['prediction_days']) : 7;
$highThreshold = isset($_GET['high_tons']) ? (float)$_GET['high_tons'] : 3.0;
$medThreshold = isset($_GET['med_tons']) ? (float)$_GET['med_tons'] : 1.0;

try {
    $start = date('Y-m-d 00:00:00', strtotime('-' . $lookbackDays . ' days'));
    $end = date('Y-m-d 23:59:59');

    // Get historical data for feature engineering
    $sql = "
        SELECT b.brgy_id, b.barangay, b.latitude, b.longitude,
               DATE(s.timestamp) as date,
               SUM(s.count) as daily_count
        FROM barangays_table b
        LEFT JOIN sensor s ON s.brgy_id = b.brgy_id 
            AND s.timestamp BETWEEN ? AND ?
        GROUP BY b.brgy_id, b.barangay, b.latitude, b.longitude, DATE(s.timestamp)
        ORDER BY b.barangay, DATE(s.timestamp)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();

    $barangayData = [];
    while ($row = $result->fetch_assoc()) {
        $brgyId = $row['brgy_id'];
        if (!isset($barangayData[$brgyId])) {
            $barangayData[$brgyId] = [
                'brgy_id' => $brgyId,
                'barangay' => $row['barangay'],
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'daily_counts' => []
            ];
        }
        $barangayData[$brgyId]['daily_counts'][$row['date']] = (int)$row['daily_count'];
    }

    $predictions = [];

    foreach ($barangayData as $brgyId => $data) {
        $dailyCounts = $data['daily_counts'];
        
        // Feature Engineering
        $features = calculateFeatures($dailyCounts, $lookbackDays);
        
        // Classification Model (Simple Decision Tree-like logic)
        $predictedRisk = classifyRisk($features, $highThreshold, $medThreshold);
        
        // Calculate confidence score
        $confidence = calculateConfidence($features);
        
        // Predict next week's waste volume
        $predictedTons = predictNextWeekTons($features);
        
        $predictions[] = [
            'brgy_id' => $brgyId,
            'barangay' => $data['barangay'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'predicted_risk' => $predictedRisk,
            'predicted_tons' => round($predictedTons, 3),
            'confidence' => round($confidence, 2),
            'features' => [
                'avg_weekly_tons' => round($features['avg_weekly_tons'], 3),
                'trend' => round($features['trend'], 3),
                'volatility' => round($features['volatility'], 3),
                'recent_peak' => round($features['recent_peak'], 3),
                'collection_consistency' => round($features['collection_consistency'], 2)
            ]
        ];
    }

    echo json_encode([
        'success' => true,
        'lookback_days' => $lookbackDays,
        'prediction_days' => $predictionDays,
        'high_tons' => $highThreshold,
        'med_tons' => $medThreshold,
        'predictions' => $predictions,
        'model_info' => [
            'type' => 'Classification Model',
            'features' => ['avg_weekly_tons', 'trend', 'volatility', 'recent_peak', 'collection_consistency'],
            'algorithm' => 'Decision Tree + Trend Analysis'
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function calculateFeatures($dailyCounts, $lookbackDays) {
    $counts = array_values($dailyCounts);
    $count = count($counts);
    
    if ($count === 0) {
        return [
            'avg_weekly_tons' => 0,
            'trend' => 0,
            'volatility' => 0,
            'recent_peak' => 0,
            'collection_consistency' => 0
        ];
    }
    
    // Convert to tons (count * 0.001)
    $tons = array_map(function($c) { return $c * 0.001; }, $counts);
    
    // Average weekly tons
    $avgWeeklyTons = array_sum($tons) / max(1, ceil($count / 7));
    
    // Trend calculation (linear regression slope)
    $trend = 0;
    if ($count > 1) {
        $x = range(0, $count - 1);
        $n = $count;
        $sumX = array_sum($x);
        $sumY = array_sum($tons);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $count; $i++) {
            $sumXY += $x[$i] * $tons[$i];
            $sumX2 += $x[$i] * $x[$i];
        }
        
        $trend = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    }
    
    // Volatility (standard deviation)
    $mean = $avgWeeklyTons;
    $variance = 0;
    foreach ($tons as $t) {
        $variance += pow($t - $mean, 2);
    }
    $volatility = sqrt($variance / max(1, $count));
    
    // Recent peak (max in last 7 days)
    $recentCounts = array_slice($counts, -7);
    $recentPeak = max($recentCounts) * 0.001;
    
    // Collection consistency (percentage of days with collection)
    $nonZeroDays = count(array_filter($counts, function($c) { return $c > 0; }));
    $collectionConsistency = $count > 0 ? ($nonZeroDays / $count) * 100 : 0;
    
    return [
        'avg_weekly_tons' => $avgWeeklyTons,
        'trend' => $trend,
        'volatility' => $volatility,
        'recent_peak' => $recentPeak,
        'collection_consistency' => $collectionConsistency
    ];
}

function classifyRisk($features, $highThreshold, $medThreshold) {
    $avgWeeklyTons = $features['avg_weekly_tons'];
    $trend = $features['trend'];
    $volatility = $features['volatility'];
    $recentPeak = $features['recent_peak'];
    $consistency = $features['collection_consistency'];
    
    // Classification rules (Decision Tree-like)
    $riskScore = 0;
    
    // Base risk from average weekly tons
    if ($avgWeeklyTons >= $highThreshold) {
        $riskScore += 3;
    } elseif ($avgWeeklyTons >= $medThreshold) {
        $riskScore += 2;
    } else {
        $riskScore += 1;
    }
    
    // Trend factor
    if ($trend > 0.1) { // Increasing trend
        $riskScore += 1;
    } elseif ($trend < -0.1) { // Decreasing trend
        $riskScore -= 1;
    }
    
    // Volatility factor
    if ($volatility > 1.0) { // High volatility
        $riskScore += 1;
    }
    
    // Recent peak factor
    if ($recentPeak >= $highThreshold) {
        $riskScore += 1;
    }
    
    // Consistency factor
    if ($consistency < 50) { // Low collection consistency
        $riskScore += 1;
    }
    
    // Classify based on risk score
    if ($riskScore >= 5) {
        return 'high';
    } elseif ($riskScore >= 3) {
        return 'medium';
    } else {
        return 'low';
    }
}

function calculateConfidence($features) {
    $consistency = $features['collection_consistency'];
    $volatility = $features['volatility'];
    $avgTons = $features['avg_weekly_tons'];
    
    // Higher confidence with more consistent data and lower volatility
    $confidence = 50; // Base confidence
    
    // Consistency factor
    $confidence += ($consistency / 100) * 30;
    
    // Volatility factor (lower volatility = higher confidence)
    $confidence -= min(20, $volatility * 10);
    
    // Data availability factor
    if ($avgTons > 0) {
        $confidence += 10;
    }
    
    return max(10, min(95, $confidence));
}

function predictNextWeekTons($features) {
    $avgWeeklyTons = $features['avg_weekly_tons'];
    $trend = $features['trend'];
    
    // Simple linear prediction: current average + trend
    $predictedTons = $avgWeeklyTons + ($trend * 7); // 7 days ahead
    
    return max(0, $predictedTons);
}
?>
