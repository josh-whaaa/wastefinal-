<?php
/**
 * Test page for Request Threshold Validation System
 * This page demonstrates how the validation system works
 */

include 'includes/conn.php';
include 'includes/request_threshold_validator.php';

// Initialize validator
$validator = new RequestThresholdValidator($pdo);

// Test data
$test_client_id = 9; // Using existing client from database
$test_date = date('Y-m-d');
$test_time = '10:00:00';

echo "<h2>Request Threshold Validation Test</h2>";
echo "<hr>";

// Test 1: Normal request (should pass)
echo "<h3>Test 1: Normal Request</h3>";
$test_request_1 = [
    'client_id' => $test_client_id,
    'request_date' => $test_date,
    'request_time' => $test_time,
    'request_description' => 'Please clean the street in front of my house',
    'request_details' => 'Street Cleaning'
];

$result_1 = $validator->validateRequest($test_request_1);
echo "<strong>Result:</strong> " . ($result_1['valid'] ? 'PASS' : 'FAIL') . "<br>";
if (!empty($result_1['errors'])) {
    echo "<strong>Errors:</strong> " . implode(', ', $result_1['errors']) . "<br>";
}
if (!empty($result_1['warnings'])) {
    echo "<strong>Warnings:</strong> " . implode(', ', $result_1['warnings']) . "<br>";
}
echo "<br>";

// Test 2: Spam request (should show warning)
echo "<h3>Test 2: Spam Request</h3>";
$test_request_2 = [
    'client_id' => $test_client_id,
    'request_date' => $test_date,
    'request_time' => $test_time,
    'request_description' => 'This is a test spam request',
    'request_details' => 'Garbage Collection'
];

$result_2 = $validator->validateRequest($test_request_2);
echo "<strong>Result:</strong> " . ($result_2['valid'] ? 'PASS' : 'FAIL') . "<br>";
if (!empty($result_2['errors'])) {
    echo "<strong>Errors:</strong> " . implode(', ', $result_2['errors']) . "<br>";
}
if (!empty($result_2['warnings'])) {
    echo "<strong>Warnings:</strong> " . implode(', ', $result_2['warnings']) . "<br>";
}
echo "<br>";

// Test 3: Outside business hours (should fail if time validation enabled)
echo "<h3>Test 3: Outside Business Hours</h3>";
$test_request_3 = [
    'client_id' => $test_client_id,
    'request_date' => $test_date,
    'request_time' => '20:00:00', // 8 PM
    'request_description' => 'Request outside business hours',
    'request_details' => 'Grass-Cutting'
];

$result_3 = $validator->validateRequest($test_request_3);
echo "<strong>Result:</strong> " . ($result_3['valid'] ? 'PASS' : 'FAIL') . "<br>";
if (!empty($result_3['errors'])) {
    echo "<strong>Errors:</strong> " . implode(', ', $result_3['errors']) . "<br>";
}
if (!empty($result_3['warnings'])) {
    echo "<strong>Warnings:</strong> " . implode(', ', $result_3['warnings']) . "<br>";
}
echo "<br>";

// Test 4: Get client statistics
echo "<h3>Test 4: Client Statistics</h3>";
$stats = $validator->getClientStats($test_client_id, $test_date);
echo "<strong>Client ID:</strong> {$test_client_id}<br>";
echo "<strong>Daily Requests:</strong> {$stats['daily']}<br>";
echo "<strong>Weekly Requests:</strong> {$stats['weekly']}<br>";
echo "<strong>Hourly Requests:</strong> {$stats['hourly']}<br>";
echo "<br>";

// Test 5: Show current configuration
echo "<h3>Test 5: Current Configuration</h3>";
$config = $validator->getConfig();
echo "<strong>Daily Limit:</strong> {$config['daily_request_limit']}<br>";
echo "<strong>Hourly Limit:</strong> {$config['hourly_request_limit']}<br>";
echo "<strong>Weekly Limit:</strong> {$config['weekly_request_limit']}<br>";
echo "<strong>Min Time Between Requests:</strong> {$config['min_time_between_requests']} minutes<br>";
echo "<strong>Max Requests Per Date:</strong> {$config['max_requests_per_date']}<br>";
echo "<strong>Business Hours:</strong> {$config['business_hours']['start']} - {$config['business_hours']['end']}<br>";
echo "<strong>Spam Detection Words:</strong> " . implode(', ', $config['spam_detection_words']) . "<br>";
echo "<strong>Duplicate Check Enabled:</strong> " . ($config['enable_duplicate_check'] ? 'Yes' : 'No') . "<br>";
echo "<strong>Time Validation Enabled:</strong> " . ($config['enable_time_validation'] ? 'Yes' : 'No') . "<br>";

echo "<br><hr>";
echo "<p><strong>Note:</strong> This is a test page. In production, validation would be integrated into the actual request submission process.</p>";
?>
