<?php
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'u520834156_DBWasteTracker';
$username = 'u520834156_userWT2025';
$password = '^Lx|Aii1';

$mysqli = new mysqli($host, $username, $password, $dbname);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed: ' . $mysqli->connect_error]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($_GET['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$action = $_GET['action'];

if ($action === 'gps') {
    if (!isset($data['location_id'], $data['latitude'], $data['longitude'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing GPS data']);
        exit;
    }

    $location_id = intval($data['location_id']);
    $latitude = floatval($data['latitude']);
    $longitude = floatval($data['longitude']);
    $timestamp = date('Y-m-d H:i:s');

    $stmt = $mysqli->prepare("INSERT INTO gps_location (location_id, latitude, longitude, timestamp) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idds", $location_id, $latitude, $longitude, $timestamp);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'GPS data saved']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save GPS data']);
    }
    $stmt->close();

} elseif ($action === 'sensor') {
    if (!isset($data['count'], $data['location_id'], $data['sensor_id'], $data['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing sensor data']);
        exit;
    }

    $count = intval($data['count']);
    $location_id = intval($data['location_id']);
    $sensor_id = intval($data['sensor_id']);
    $status = $mysqli->real_escape_string($data['status']);
    $timestamp = date('Y-m-d H:i:s');

    // Add `status` column to sensor table if not exists!
    $stmt = $mysqli->prepare("UPDATE sensor SET count = ?, location_id = ?, timestamp = ?, status = ? WHERE sensor_id = ?");
    $stmt->bind_param("iissi", $count, $location_id, $timestamp, $status, $sensor_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Sensor data updated']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update sensor data']);
    }
    $stmt->close();

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}

$mysqli->close();
