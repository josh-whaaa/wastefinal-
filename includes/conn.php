<?php
// Database connection file for online
// $host = 'localhost'; 
// $dbname = 'u520834156_DBWasteTracker'; 
// $username = 'u520834156_userWT2025'; 
// $password = '^Lx|Aii1'; 

// Local (comment/uncomment when switching)
$host = 'localhost'; 
$dbname = 'u520834156_dbwastetracker'; 
$username = 'root'; 
$password = ''; 

$conn = new mysqli($host, $username, $password, $dbname);

// Fix collation & charset for mysqli
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// PDO connection (for prepared statements)
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Ensure collation consistency
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
