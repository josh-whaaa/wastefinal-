<?php
/**
 * Test Remote ML Server Connection
 * Tests connection to Heroku ML server
 */

header('Content-Type: application/json');

require_once 'api/ml_remote_client.php';

try {
    $results = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'ml_server_url' => 'https://wastetracker-3e73822f0171.herokuapp.com',
        'tests' => []
    ];
    
    // Initialize ML client
    $mlClient = new MLRemoteClient('https://wastetracker-3e73822f0171.herokuapp.com', 60, true);
    
    // Test 1: Server Health Check
    $results['tests']['server_health'] = $mlClient->checkServerHealth();
    
    // Test 2: Sample Data for Testing
    $sampleData = [
        '1' => [
            'brgy_id' => 1,
            'barangay' => 'Test Barangay',
            'latitude' => 10.5379,
            'longitude' => 122.8333,
            'daily_counts' => [
                '2024-01-01' => 1000,
                '2024-01-02' => 1200,
                '2024-01-03' => 800,
                '2024-01-04' => 1500,
                '2024-01-05' => 1100,
                '2024-01-06' => 1300,
                '2024-01-07' => 900
            ]
        ]
    ];
    
    // Test 3: Waste Forecast API
    try {
        $wasteResult = $mlClient->getWasteForecast($sampleData, 7);
        $results['tests']['waste_forecast'] = [
            'status' => 'success',
            'response' => $wasteResult
        ];
    } catch (Exception $e) {
        $results['tests']['waste_forecast'] = [
            'status' => 'failed',
            'error' => $e->getMessage()
        ];
    }
    
    // Test 4: Health Risk Analysis API
    try {
        $healthResult = $mlClient->getHealthRiskAnalysis($sampleData, 7);
        $results['tests']['health_risk'] = [
            'status' => 'success',
            'response' => $healthResult
        ];
    } catch (Exception $e) {
        $results['tests']['health_risk'] = [
            'status' => 'failed',
            'error' => $e->getMessage()
        ];
    }
    
    // Test 5: Server Info
    $results['tests']['server_info'] = $mlClient->getServerInfo();
    
    // Overall status
    $allTestsPassed = true;
    foreach ($results['tests'] as $testName => $test) {
        if (is_array($test) && isset($test['status']) && $test['status'] === 'failed') {
            $allTestsPassed = false;
            break;
        }
        if (is_array($test) && isset($test['available']) && !$test['available']) {
            $allTestsPassed = false;
            break;
        }
    }
    
    $results['overall_status'] = $allTestsPassed ? 'healthy' : 'issues_detected';
    
    // Recommendations
    $results['recommendations'] = [];
    
    if ($results['tests']['server_health']['available']) {
        $results['recommendations'][] = '✅ ML server is accessible';
    } else {
        $results['recommendations'][] = '❌ ML server is not accessible - check URL and server status';
    }
    
    if (isset($results['tests']['waste_forecast']['status']) && $results['tests']['waste_forecast']['status'] === 'success') {
        $results['recommendations'][] = '✅ Waste forecast API working';
    } else {
        $results['recommendations'][] = '❌ Waste forecast API failed - check endpoint /api/waste-forecast';
    }
    
    if (isset($results['tests']['health_risk']['status']) && $results['tests']['health_risk']['status'] === 'success') {
        $results['recommendations'][] = '✅ Health risk API working';
    } else {
        $results['recommendations'][] = '❌ Health risk API failed - check endpoint /api/health-risk';
    }
    
    echo json_encode($results, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
