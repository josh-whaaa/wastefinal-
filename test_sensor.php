<?php
// Test script to simulate sensor data for vehicle capacity testing
// Run this to add test sensor data to your database

require_once 'includes/conn.php';

// Simulate different capacity levels for testing
$testCounts = [10, 25, 50, 75, 90, 100, 95, 80, 60, 30];

foreach ($testCounts as $count) {
    $sql = "INSERT INTO sensor (count, location_id, timestamp) VALUES (?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$count, 1]);
    
    echo "Added sensor data: count = $count\n";
    sleep(2); // Wait 2 seconds between each insert
}

echo "Test sensor data added successfully!\n";
echo "Check your vehicle panel to see the capacity changes.\n";
?>
