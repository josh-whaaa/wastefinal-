<?php
/**
 * Test ML Server Connection
 * Tests connection to your Heroku ML server
 */

require_once 'api/ml_server_connector.php';

echo "🧪 Testing ML Server Connection\n";
echo "===============================\n\n";

// Test 1: Basic Connection Test
echo "1. Testing basic connection...\n";
$connector = new MLServerConnector();
$testResult = $connector->testConnection();

if ($testResult['success']) {
    echo "   ✅ Connection successful!\n";
    echo "   📡 Server: " . $testResult['server_url'] . "\n";
} else {
    echo "   ❌ Connection failed: " . $testResult['error'] . "\n";
}

echo "\n";

// Test 2: Simple Prediction Test
echo "2. Testing simple prediction...\n";
$testData = [
    'input1' => 100,
    'input2' => 50,
    'test_mode' => true
];

$predictionResult = $connector->predict('predict', $testData);

if ($predictionResult['success']) {
    echo "   ✅ Prediction successful!\n";
    echo "   🤖 Source: " . $predictionResult['source'] . "\n";
    echo "   📊 Data: " . json_encode($predictionResult['data'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   ❌ Prediction failed: " . $predictionResult['error'] . "\n";
}

echo "\n";

// Test 3: CEMO System Data Test
echo "3. Testing with CEMO system data...\n";
$cemoData = [
    'lookback_days' => 7,
    'barangay_data' => [
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
    ],
    'model_type' => 'waste_forecast'
];

$cemoResult = $connector->predict('predict', $cemoData);

if ($cemoResult['success']) {
    echo "   ✅ CEMO data prediction successful!\n";
    echo "   🤖 Source: " . $cemoResult['source'] . "\n";
    echo "   📊 Forecasts: " . (isset($cemoResult['data']['forecasts']) ? count($cemoResult['data']['forecasts']) : 0) . " generated\n";
} else {
    echo "   ❌ CEMO data prediction failed: " . $cemoResult['error'] . "\n";
}

echo "\n";

// Test 4: Enhanced API Test
echo "4. Testing enhanced API endpoint...\n";
$apiUrl = 'http://localhost/CEMO_System/final/api/get_waste_forecast_enhanced.php?lookback_days=7&use_ml_server=true';
$context = stream_context_create([
    'http' => [
        'timeout' => 30,
        'method' => 'GET'
    ]
]);

$apiResponse = @file_get_contents($apiUrl, false, $context);
if ($apiResponse !== false) {
    $apiData = json_decode($apiResponse, true);
    if ($apiData && $apiData['success']) {
        echo "   ✅ Enhanced API working!\n";
        echo "   🤖 ML Source: " . $apiData['ml_source'] . "\n";
        echo "   📊 Forecasts: " . count($apiData['forecasts']) . " generated\n";
        echo "   🏷️  Model: " . $apiData['model_info']['type'] . "\n";
    } else {
        echo "   ❌ Enhanced API failed: " . ($apiData['error'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "   ❌ Enhanced API not accessible\n";
}

echo "\n";
echo "🏁 Test completed!\n";
echo "\n📋 Summary:\n";
echo "- ML Server URL: https://wastetracker-3e73822f0171.herokuapp.com\n";
echo "- Fallback: Local Python scripts\n";
echo "- Enhanced API: api/get_waste_forecast_enhanced.php\n";
echo "\n🔧 Usage Examples:\n";
echo "1. Test connection: curl 'http://localhost/CEMO_System/final/api/ml_server_connector.php?action=test'\n";
echo "2. Make prediction: curl 'http://localhost/CEMO_System/final/api/ml_server_connector.php?action=predict&input1=100&input2=50'\n";
echo "3. Enhanced forecast: curl 'http://localhost/CEMO_System/final/api/get_waste_forecast_enhanced.php?lookback_days=7'\n";
?>
