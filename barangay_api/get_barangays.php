<?php
header("Content-Type: application/json");
require_once "../includes/conn.php"; // Make sure this connects to your DB

try {
    $stmt = $pdo->query("SELECT brgy_id, barangay, latitude, longitude FROM barangays_table WHERE city = 'Bago City'");
    $barangays = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($barangays);
} catch (Exception $e) {
    echo json_encode(["error" => "Failed to fetch barangays"]);
}
?>