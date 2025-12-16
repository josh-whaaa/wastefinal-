<?php
$pdo = new PDO("mysql:host=localhost;dbname=cemo_db", "root", "");
$stmt = $pdo->query("SELECT latitude, longitude FROM gps_location ORDER BY timestamp DESC LIMIT 1");
$data = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($data);
?>