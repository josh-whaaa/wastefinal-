<?php
header('Content-Type: application/json');

// ==========================
// 🔹 DATABASE CONFIGURATION
// ==========================
require_once '../includes/conn.php';

// Use the shared database connection from conn.php

// ==========================
// 🔹 READ RAW JSON INPUT
// ==========================
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
    exit;
}

// Extract data
$sensor_id   = (int)($data['sensor_id'] ?? 0);
$count       = (int)($data['count'] ?? 0);
$brgy_id     = (int)($data['brgy_id'] ?? 0);
$location_id = (int)($data['location_id'] ?? 0);
$distance    = isset($data['distance']) ? (float)$data['distance'] : 0.0;
$latitude    = isset($data['latitude']) ? (float)$data['latitude'] : 0.0;
$longitude   = isset($data['longitude']) ? (float)$data['longitude'] : 0.0;
$status      = isset($data['status']) ? trim($data['status']) : 'ACTIVE';

// ==========================
// 🔹 INSERT INTO SENSOR TABLE
// ==========================
$sql = "INSERT INTO sensor (sensor_id, count, brgy_id, location_id, timestamp, distance, status)
        VALUES (?, ?, ?, ?, NOW(), ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
    exit;
}
$stmt->bind_param("iiiids", $sensor_id, $count, $brgy_id, $location_id, $distance, $status);
$sensor_ok = $stmt->execute();
$stmt->close();

// ==========================
// 🔹 INSERT GPS LOG (Optional)
// ==========================
$gps_ok = false;
if (!empty($latitude) && !empty($longitude)) {
    $gps_sql = "INSERT INTO gps_location (latitude, longitude, timestamp) VALUES (?, ?, NOW())";
    $stmtGps = $conn->prepare($gps_sql);
    $stmtGps->bind_param("dd", $latitude, $longitude);
    $gps_ok = $stmtGps->execute();
    $stmtGps->close();
}

// ==========================
// 🔹 RESPONSE TO NODEMCU
// ==========================
echo json_encode([
    "status" => ($sensor_ok ? "success" : "error"),
    "sensor_insert" => $sensor_ok,
    "gps_insert" => $gps_ok
]);

$conn->close();
?>
