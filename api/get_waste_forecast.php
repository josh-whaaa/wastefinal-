<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/conn.php';

// Parameters - Use 5 weeks (35 days) for better ML training
$lookbackDays = isset($_GET['lookback_days']) ? max(1, (int)$_GET['lookback_days']) : 35;

try {
    $start = date('Y-m-d 00:00:00', strtotime('-' . $lookbackDays . ' days'));
    $end = date('Y-m-d 23:59:59');

    // Check if driver_waste_uploads table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'driver_waste_uploads'");
    $hasUploadsTable = $tableCheck && $tableCheck->num_rows > 0;
    
    // Get historical data for forecasting from driver_waste_uploads table only
    $sql = null;
    if ($hasUploadsTable) {
        // Get data from driver_waste_uploads
        $sql = "
            SELECT b.brgy_id, b.barangay, b.latitude, b.longitude,
                   DATE(dwu.collection_date) as date,
                   COALESCE(SUM(dwu.waste_count), 0) as daily_count
            FROM barangays_table b
            LEFT JOIN driver_waste_uploads dwu ON dwu.brgy_id = b.brgy_id 
                AND dwu.collection_date BETWEEN ? AND ?
            GROUP BY b.brgy_id, b.barangay, b.latitude, b.longitude, DATE(dwu.collection_date)
            ORDER BY b.barangay, DATE(dwu.collection_date)
        ";
    }
    
    if (!$sql) {
        echo json_encode(['success' => false, 'error' => 'driver_waste_uploads table not found']);
        exit;
    }

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
        if ($row['date']) {
            $barangayData[$brgyId]['daily_counts'][$row['date']] = (int)$row['daily_count'];
        }
    }
    $stmt->close();

    // Prepare data for Python script
    $pythonInput = [
        'lookback_days' => $lookbackDays,
        'barangay_data' => $barangayData
    ];

    // Call Python script
    $pythonScript = __DIR__ . '/../pyhton/ml_waste_forecaster.py';
    $pythonCommand = "py " . escapeshellarg($pythonScript) . " 2>nul";
    
    // Alternative commands if py is not available
    $alternativeCommands = [
        "python " . escapeshellarg($pythonScript) . " 2>nul",
        "python3 " . escapeshellarg($pythonScript) . " 2>nul",
        "python3.9 " . escapeshellarg($pythonScript) . " 2>nul",
        "python3.8 " . escapeshellarg($pythonScript) . " 2>nul"
    ];

    $pythonOutput = null;
    $pythonError = null;
    $returnValue = -1;
    
    // Try primary command first, but only if script exists
    if (is_file($pythonScript)) {
        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];
        
        $process = @proc_open($pythonCommand, $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            $status = proc_get_status($process);
            if ($status && !empty($status['running'])) {
                $writeOk = @fwrite($pipes[0], json_encode($pythonInput));
            } else {
                $writeOk = false;
            }
            @fclose($pipes[0]);
            
            $pythonOutput = stream_get_contents($pipes[1]);
            $pythonError = stream_get_contents($pipes[2]);
            @fclose($pipes[1]);
            @fclose($pipes[2]);
            
            $returnValue = proc_close($process);
            
            if ($returnValue !== 0 || $writeOk === false) {
                // Try alternative commands
                foreach ($alternativeCommands as $altCommand) {
                    $process = @proc_open($altCommand, $descriptorspec, $pipes);
                    if (is_resource($process)) {
                        $status = proc_get_status($process);
                        if ($status && !empty($status['running'])) {
                            $writeOk = @fwrite($pipes[0], json_encode($pythonInput));
                        } else {
                            $writeOk = false;
                        }
                        @fclose($pipes[0]);
                        
                        $pythonOutput = stream_get_contents($pipes[1]);
                        $pythonError = stream_get_contents($pipes[2]);
                        @fclose($pipes[1]);
                        @fclose($pipes[2]);
                        
                        $returnValue = proc_close($process);
                        if ($returnValue === 0 && $writeOk !== false) {
                            break;
                        }
                    }
                }
            }
        } else {
            $pythonError = 'Failed to start Python process';
        }
    } else {
        $pythonError = 'Python script not found: ' . $pythonScript;
    }
    
    // Check if Python script executed successfully
    if ($pythonOutput === null || $returnValue !== 0) {
        // Fallback to PHP-based forecasting
        $forecasts = fallbackForecast($barangayData, $lookbackDays);
        $modelInfo = [
            'type' => 'PHP Fallback Forecaster',
            'algorithm' => 'Simple Linear Projection',
            'features' => ['avg_weekly_tons', 'trend', 'volatility', 'collection_consistency'],
            'python_available' => false,
            'python_error' => $pythonError,
            'python_output' => $pythonOutput,
            'return_value' => $returnValue,
            'debug_info' => [
                'script_path' => $pythonScript,
                'command_used' => $pythonCommand,
                'input_data_size' => strlen(json_encode($pythonInput))
            ]
        ];
    } else {
        // Extract JSON from mixed output (Python may output debug messages to stdout)
        $jsonStart = strpos($pythonOutput, '{');
        if ($jsonStart !== false) {
            $jsonOutput = substr($pythonOutput, $jsonStart);
            // Find the end of the JSON by looking for the last closing brace
            $jsonEnd = strrpos($jsonOutput, '}');
            if ($jsonEnd !== false) {
                $jsonOutput = substr($jsonOutput, 0, $jsonEnd + 1);
            }
            $pythonResult = json_decode($jsonOutput, true);
        } else {
            $pythonResult = json_decode($pythonOutput, true);
        }
        
        if ($pythonResult && $pythonResult['success']) {
            $forecasts = $pythonResult['forecasts'];
            $modelInfo = $pythonResult['model_info'];
            $modelInfo['python_available'] = true;
        } else {
            // Fallback if Python output is invalid
            $forecasts = fallbackForecast($barangayData, $lookbackDays);
            $modelInfo = [
                'type' => 'PHP Fallback Forecaster',
                'algorithm' => 'Simple Linear Projection',
                'features' => ['avg_weekly_tons', 'trend', 'volatility', 'collection_consistency'],
                'python_available' => false,
                'python_error' => $pythonResult['error'] ?? 'Invalid output'
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'lookback_days' => $lookbackDays,
        'forecasts' => $forecasts,
        'model_info' => $modelInfo,
        'debug' => [
            'barangay_count' => count($barangayData),
            'total_data_points' => array_sum(array_map(function($data) { return count($data['daily_counts']); }, $barangayData)),
            'python_used' => $pythonOutput !== null && $returnValue === 0
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

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
    
    // Simple linear projection
    $forecast = $avgWeeklyTons + ($trend * 7);
    
    // Adjust for collection consistency
    $forecast = $forecast * ($consistency / 100);
    
    // Add some volatility
    $forecast = $forecast * (1 + $volatility * 0.1);
    
    // Ensure non-negative
    $forecast = max(0, $forecast);
    
    // Calculate confidence
    $confidence = min(95, max(30, 100 - $volatility * 20));
    
    return [
        'forecasted_tons' => $forecast,
        'confidence' => $confidence
    ];
}
?>
