<?php
// Backward-compatible endpoint: accepts JSON body or GET/POST and responds
// with JSON if requested, otherwise plain text (as before).

$servername = "localhost";
$username = "root"; // default for XAMPP
$password = "";     // default for XAMPP
$dbname = "cemo_db"; // change to your DB name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check
if ($conn->connect_error) {
    $msg = "Connection failed: " . $conn->connect_error;
    header('Content-Type: text/plain');
    http_response_code(500);
    echo $msg;
    exit;
}

// Detect JSON request and parse
$raw = file_get_contents('php://input');
$asJson = json_decode($raw, true);
$isJsonReq = is_array($asJson) || (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
$wantsJson = $isJsonReq || (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
if ($wantsJson) {
    header('Content-Type: application/json');
}

// Read params with fallback order: JSON -> POST -> GET -> defaults
$count = isset($asJson['count']) ? (int)$asJson['count'] : (isset($_POST['count']) ? (int)$_POST['count'] : (isset($_GET['count']) ? (int)$_GET['count'] : 0));
$location_id = isset($asJson['location_id']) ? (int)$asJson['location_id'] : (isset($_POST['location_id']) ? (int)$_POST['location_id'] : (isset($_GET['location_id']) ? (int)$_GET['location_id'] : 1));

if ($count < 0) $count = 0;
if ($location_id < 0) $location_id = 0;

// Insert into your table (prepared statement)
$stmt = $conn->prepare("INSERT INTO sensor (`count`, `location_id`) VALUES (?, ?)");
if ($stmt) {
    $stmt->bind_param("ii", $count, $location_id);
    $ok = $stmt->execute();
    if ($ok) {
        if ($wantsJson) {
            echo json_encode(["success" => true, "message" => "Inserted", "id" => $stmt->insert_id]);
        } else {
            echo "Data inserted successfully";
        }
    } else {
        if ($wantsJson) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => $stmt->error]);
        } else {
            echo "Error: " . $stmt->error;
        }
    }
    $stmt->close();
} else {
    if ($wantsJson) {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $conn->error]);
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>
