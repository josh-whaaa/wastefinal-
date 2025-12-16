<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/conn.php';

// Parameters
$lookbackDays = isset($_GET['lookback_days']) ? max(1, (int)$_GET['lookback_days']) : 14;

try {
    $start = date('Y-m-d 00:00:00', strtotime('-' . $lookbackDays . ' days'));
    $end = date('Y-m-d 23:59:59');

    // Get historical data for ML processing
    $sql = "
        SELECT b.brgy_id, b.barangay, b.latitude, b.longitude,
               DATE(s.timestamp) as date,
               SUM(s.count) as daily_count
        FROM barangays_table b
        LEFT JOIN sensor s ON s.brgy_id = b.brgy_id 
            AND s.timestamp BETWEEN ? AND ?
            AND s.brgy_id > 0
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

    // Prepare data for Python script
    $pythonInput = [
        'lookback_days' => $lookbackDays,
        'barangay_data' => $barangayData
    ];

    // Configuration for Flask API
    $flaskApiUrl = 'http://localhost:5001/predict'; // Change this to your Flask server URL
    $useFlaskApi = true; // Set to false to use local Python script as fallback
    
    $pythonOutput = null;
    $pythonError = null;
    $returnValue = -1;
    
    if ($useFlaskApi) {
        // Call Flask API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $flaskApiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pythonInput));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($pythonInput))
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        $pythonOutput = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($pythonOutput === false || $httpCode !== 200) {
            $pythonError = $curlError ?: "HTTP Error: $httpCode";
            $returnValue = 1;
        } else {
            $returnValue = 0;
        }
    } else {
        // Fallback to local Python script
        $pythonScript = __DIR__ . '/../pyhton/ml_health_risk_classifier.py';
        $pythonCommand = "py " . escapeshellarg($pythonScript) . " 2>nul";
        
        // Alternative commands if py is not available
        $alternativeCommands = [
            "python " . escapeshellarg($pythonScript) . " 2>nul",
            "python3 " . escapeshellarg($pythonScript) . " 2>nul",
            "python3.9 " . escapeshellarg($pythonScript) . " 2>nul",
            "python3.8 " . escapeshellarg($pythonScript) . " 2>nul"
        ];
        
        // Try primary command first
        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];
        
        $process = proc_open($pythonCommand, $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            // Write input to Python script (safe write with error handling)
            $inputJson = json_encode($pythonInput);
            $writeOk = true;
            if (is_resource($pipes[0])) {
                stream_set_blocking($pipes[0], true);
                $bytesWritten = @fwrite($pipes[0], $inputJson);
                if ($bytesWritten === false || $bytesWritten === 0) {
                    $writeOk = false;
                }
                @fclose($pipes[0]);
            } else {
                $writeOk = false;
            }
            
            // Read output
            $pythonOutput = is_resource($pipes[1]) ? stream_get_contents($pipes[1]) : null;
            $pythonError = is_resource($pipes[2]) ? stream_get_contents($pipes[2]) : null;
            if (is_resource($pipes[1])) { fclose($pipes[1]); }
            if (is_resource($pipes[2])) { fclose($pipes[2]); }
            
            $returnValue = proc_close($process);
            
            if ($returnValue !== 0) {
                // Try alternative commands
                foreach ($alternativeCommands as $altCommand) {
                    $process = proc_open($altCommand, $descriptorspec, $pipes);
                    if (is_resource($process)) {
                        // Safe write
                        $inputJson = json_encode($pythonInput);
                        if (is_resource($pipes[0])) {
                            stream_set_blocking($pipes[0], true);
                            @fwrite($pipes[0], $inputJson);
                            @fclose($pipes[0]);
                        }
                        
                        $pythonOutput = is_resource($pipes[1]) ? stream_get_contents($pipes[1]) : null;
                        $pythonError = is_resource($pipes[2]) ? stream_get_contents($pipes[2]) : null;
                        if (is_resource($pipes[1])) { fclose($pipes[1]); }
                        if (is_resource($pipes[2])) { fclose($pipes[2]); }
                        
                        $returnValue = proc_close($process);
                        if ($returnValue === 0) {
                            break;
                        }
                    }
                }
            }
        }
    }
    
    // Check if Python script executed successfully
    if ($pythonOutput === null || $returnValue !== 0) {
        // Fallback to PHP-based prediction
        $predictions = fallbackPrediction($barangayData, $lookbackDays);
        $modelInfo = [
            'type' => 'PHP Fallback Model',
            'features' => ['avg_weekly_tons', 'trend', 'volatility', 'recent_peak', 'collection_consistency'],
            'algorithm' => 'Rule-based Classification',
            'flask_api_available' => false,
            'api_error' => $pythonError,
            'api_output' => $pythonOutput,
            'return_value' => $returnValue,
            'debug_info' => [
                'api_url' => $useFlaskApi ? $flaskApiUrl : 'Local Python Script',
                'method_used' => $useFlaskApi ? 'Flask API' : 'Local Python',
                'input_data_size' => strlen(json_encode($pythonInput))
            ]
        ];
    } else {
        // Parse Python output
        $pythonResult = json_decode($pythonOutput, true);
        if ($pythonResult && $pythonResult['success']) {
            $predictions = $pythonResult['predictions'];
            $modelInfo = $pythonResult['model_info'];
            $modelInfo['flask_api_available'] = true;
        } else {
            // Fallback if API output is invalid
            $predictions = fallbackPrediction($barangayData, $lookbackDays);
            $modelInfo = [
                'type' => 'PHP Fallback Model',
                'features' => ['avg_weekly_tons', 'trend', 'volatility', 'recent_peak', 'collection_consistency'],
                'algorithm' => 'Rule-based Classification',
                'flask_api_available' => false,
                'api_error' => $pythonResult['error'] ?? 'Invalid output'
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'lookback_days' => $lookbackDays,
        'predictions' => $predictions,
        'model_info' => $modelInfo,
        'debug' => [
            'barangay_count' => count($barangayData),
            'total_data_points' => array_sum(array_map(function($data) { return count($data['daily_counts']); }, $barangayData)),
            'api_used' => $pythonOutput !== null && $returnValue === 0,
            'method' => $useFlaskApi ? 'Flask API' : 'Local Python Script'
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function fallbackPrediction($barangayData, $lookbackDays) {
    $predictions = [];
    
    foreach ($barangayData as $brgyId => $data) {
        $dailyCounts = $data['daily_counts'];
        $features = calculateFeatures($dailyCounts, $lookbackDays);
        $predictedRisk = classifyRisk($features);
        $confidence = calculateConfidence($features);
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
    
    return $predictions;
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
    
    $recentCounts = array_slice($counts, -7);
    $recentPeak = max($recentCounts) * 0.001;
    
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

function classifyRisk($features) {
    $avgWeeklyTons = $features['avg_weekly_tons'];
    $trend = $features['trend'];
    $volatility = $features['volatility'];
    $recentPeak = $features['recent_peak'];
    $consistency = $features['collection_consistency'];
    
    $riskScore = 0;
    
    // Use ML-based thresholds (learned from data patterns)
    if ($avgWeeklyTons >= 3.0) {
        $riskScore += 3;
    } elseif ($avgWeeklyTons >= 1.0) {
        $riskScore += 2;
    } else {
        $riskScore += 1;
    }
    
    if ($trend > 0.1) {
        $riskScore += 1;
    } elseif ($trend < -0.1) {
        $riskScore -= 1;
    }
    
    if ($volatility > 1.0) {
        $riskScore += 1;
    }
    
    if ($recentPeak >= 3.0) {
        $riskScore += 1;
    }
    
    if ($consistency < 50) {
        $riskScore += 1;
    }
    
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
    
    $confidence = 50;
    $confidence += ($consistency / 100) * 30;
    $confidence -= min(20, $volatility * 10);
    
    if ($avgTons > 0) {
        $confidence += 10;
    }
    
    return max(10, min(95, $confidence));
}

function predictNextWeekTons($features) {
    $avgWeeklyTons = $features['avg_weekly_tons'];
    $trend = $features['trend'];
    
    $predictedTons = $avgWeeklyTons + ($trend * 7);
    return max(0, $predictedTons);
}
?>
