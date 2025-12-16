<?php
/**
 * Enhanced Waste Forecast API with ML Server Integration
 * Uses external ML server with fallback to local Python
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/conn.php';
require_once __DIR__ . '/ml_server_connector.php';

// Parameters
$lookbackDays = isset($_GET['lookback_days']) ? max(1, (int)$_GET['lookback_days']) : 35;
$useMLServer = isset($_GET['use_ml_server']) ? filter_var($_GET['use_ml_server'], FILTER_VALIDATE_BOOLEAN) : true;

try {
    // Get historical data from database
    $start = date('Y-m-d 00:00:00', strtotime('-' . $lookbackDays . ' days'));
    $end = date('Y-m-d 23:59:59');

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

    // Prepare data for ML processing
    $mlInput = [
        'lookback_days' => $lookbackDays,
        'barangay_data' => $barangayData,
        'model_type' => 'waste_forecast',
        'features' => [
            'avg_weekly_tons',
            'trend',
            'volatility',
            'recent_peak',
            'collection_consistency',
            'waste_growth_rate',
            'collection_frequency',
            'peak_to_avg_ratio'
        ]
    ];

    $forecasts = [];
    $modelInfo = [];
    $mlSource = 'none';

    if ($useMLServer) {
        // Try ML server first
        $connector = new MLServerConnector();
        $mlResult = $connector->predict('predict', $mlInput);
        
        if ($mlResult['success']) {
            $forecasts = $mlResult['data']['forecasts'] ?? [];
            $modelInfo = $mlResult['data']['model_info'] ?? [];
            $modelInfo['source'] = $mlResult['source'];
            $modelInfo['server_url'] = $mlResult['server_url'] ?? null;
            $mlSource = $mlResult['source'];
        } else {
            // ML server failed, use fallback
            $forecasts = fallbackForecast($barangayData, $lookbackDays);
            $modelInfo = [
                'type' => 'PHP Fallback Forecaster',
                'algorithm' => 'Simple Linear Projection',
                'features' => ['avg_weekly_tons', 'trend', 'volatility', 'collection_consistency'],
                'source' => 'php_fallback',
                'ml_server_error' => $mlResult['error'] ?? 'Unknown error'
            ];
            $mlSource = 'php_fallback';
        }
    } else {
        // Use local Python directly
        $forecasts = executeLocalPython($mlInput);
        $modelInfo = [
            'type' => 'Local Python Forecaster',
            'algorithm' => 'Polynomial Regression',
            'features' => ['avg_weekly_tons', 'trend', 'volatility', 'collection_consistency'],
            'source' => 'local_python'
        ];
        $mlSource = 'local_python';
    }

    // Ensure we have forecasts
    if (empty($forecasts)) {
        $forecasts = fallbackForecast($barangayData, $lookbackDays);
        $modelInfo = [
            'type' => 'PHP Fallback Forecaster',
            'algorithm' => 'Simple Linear Projection',
            'features' => ['avg_weekly_tons', 'trend', 'volatility', 'collection_consistency'],
            'source' => 'php_fallback'
        ];
        $mlSource = 'php_fallback';
    }

    echo json_encode([
        'success' => true,
        'lookback_days' => $lookbackDays,
        'forecasts' => $forecasts,
        'model_info' => $modelInfo,
        'ml_source' => $mlSource,
        'debug' => [
            'barangay_count' => count($barangayData),
            'total_data_points' => array_sum(array_map(function($data) { 
                return count($data['daily_counts']); 
            }, $barangayData)),
            'ml_server_enabled' => $useMLServer,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Execute local Python ML script
 */
function executeLocalPython($mlInput) {
    $pythonScript = __DIR__ . '/../pyhton/ml_waste_forecaster.py';
    
    if (!file_exists($pythonScript)) {
        return [];
    }
    
    $descriptorspec = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ];
    
    $pythonCommands = [
        "py " . escapeshellarg($pythonScript) . " 2>nul",
        "python " . escapeshellarg($pythonScript) . " 2>nul",
        "python3 " . escapeshellarg($pythonScript) . " 2>nul"
    ];
    
    foreach ($pythonCommands as $command) {
        $process = proc_open($command, $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            fwrite($pipes[0], json_encode($mlInput));
            fclose($pipes[0]);
            
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            
            $returnValue = proc_close($process);
            
            if ($returnValue === 0 && !empty($output)) {
                $result = json_decode($output, true);
                return $result['forecasts'] ?? [];
            }
        }
    }
    
    return [];
}

/**
 * PHP fallback forecasting function
 */
function fallbackForecast($barangayData, $lookbackDays) {
    $forecasts = [];
    
    foreach ($barangayData as $brgyId => $data) {
        $dailyCounts = $data['daily_counts'];
        $features = calculateForecastFeatures($dailyCounts, $lookbackDays);
        $forecastResult = simpleForecast($features);
        
        $forecasts[] = [
            'brgy_id' => $brgyId,
            'barangay' => $data['barangay'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'forecasted_tons' => round($forecastResult['forecasted_tons'], 3),
            'confidence' => round($forecastResult['confidence'], 2),
            'model_used' => 'PHP Simple Linear Projection',
            'features' => [
                'avg_weekly_tons' => round($features['avg_weekly_tons'], 3),
                'trend' => round($features['trend'], 3),
                'volatility' => round($features['volatility'], 3),
                'collection_consistency' => round($features['collection_consistency'], 2)
            ]
        ];
    }
    
    return $forecasts;
}

function calculateForecastFeatures($dailyCounts, $lookbackDays) {
    $counts = array_values($dailyCounts);
    $count = count($counts);
    
    if ($count === 0) {
        return [
            'avg_weekly_tons' => 0,
            'trend' => 0,
            'volatility' => 0,
            'collection_consistency' => 0
        ];
    }
    
    $tons = array_map(function($c) { return $c * 0.001; }, $counts);
    $avgWeeklyTons = array_sum($tons) / max(1, ceil($count / 7));
    
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
    
    $mean = $avgWeeklyTons;
    $variance = 0;
    foreach ($tons as $t) {
        $variance += pow($t - $mean, 2);
    }
    $volatility = sqrt($variance / max(1, $count));
    
    $nonZeroDays = count(array_filter($counts, function($c) { return $c > 0; }));
    $collectionConsistency = $count > 0 ? ($nonZeroDays / $count) * 100 : 0;
    
    return [
        'avg_weekly_tons' => $avgWeeklyTons,
        'trend' => $trend,
        'volatility' => $volatility,
        'collection_consistency' => $collectionConsistency
    ];
}

function simpleForecast($features) {
    $avgWeeklyTons = $features['avg_weekly_tons'];
    $trend = $features['trend'];
    $volatility = $features['volatility'];
    $consistency = $features['collection_consistency'];
    
    $forecast = $avgWeeklyTons + ($trend * 7);
    $forecast = $forecast * ($consistency / 100);
    $forecast = $forecast * (1 + $volatility * 0.1);
    $forecast = max(0, $forecast);
    
    $confidence = min(95, max(30, 100 - $volatility * 20));
    
    return [
        'forecasted_tons' => $forecast,
        'confidence' => $confidence
    ];
}
?>
