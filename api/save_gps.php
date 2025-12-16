<?php
// Backward-compatible: Accept JSON or form params, reply JSON if requested.

$host = 'localhost'; 
$database = 'cemo_db'; 
$user = 'root'; 
$password = ''; 

// Detect if caller prefers JSON
$raw = file_get_contents('php://input');
$asJson = json_decode($raw, true);
$isJsonReq = is_array($asJson) || (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
$wantsJson = $isJsonReq || (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
if ($wantsJson) {
    header('Content-Type: application/json');
}

// ✅ Connect to MySQL and log errors
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    file_put_contents("gps_debug.txt", date("Y-m-d H:i:s") . " DB Connection Error: " . $conn->connect_error . "\n", FILE_APPEND);
    http_response_code(500);
    if ($wantsJson) {
        echo json_encode(["success" => false, "error" => $conn->connect_error]);
    } else {
        echo "❌ DB Connection failed: " . $conn->connect_error;
    }
    exit;
}

// ✅ Get and sanitize inputs (JSON -> POST -> GET)
$lat = isset($asJson['latitude']) ? trim($asJson['latitude']) : (isset($_POST['latitude']) ? trim($_POST['latitude']) : (isset($_GET['latitude']) ? trim($_GET['latitude']) : null));
$lng = isset($asJson['longitude']) ? trim($asJson['longitude']) : (isset($_POST['longitude']) ? trim($_POST['longitude']) : (isset($_GET['longitude']) ? trim($_GET['longitude']) : null));

// ✅ Validate data
if (is_numeric($lat) && is_numeric($lng)) {
    $stmt = $conn->prepare("INSERT INTO gps_location (latitude, longitude) VALUES (?, ?)");
    if ($stmt) {
        $latf = (float)$lat; $lngf = (float)$lng;
        $stmt->bind_param("dd", $latf, $lngf);
        if ($stmt->execute()) {
            if ($wantsJson) {
                echo json_encode(["success" => true, "message" => "GPS saved", "id" => $stmt->insert_id]);
            } else {
                echo "✔ GPS saved";
            }
            file_put_contents("gps_debug.txt", date("Y-m-d H:i:s") . " SQL Insert Success: $latf, $lngf\n", FILE_APPEND);
        } else {
            http_response_code(500);
            if ($wantsJson) {
                echo json_encode(["success" => false, "error" => $stmt->error]);
            } else {
                echo "❌ SQL Execute error: " . $stmt->error;
            }
            file_put_contents("gps_debug.txt", date("Y-m-d H:i:s") . " SQL Execute Error: " . $stmt->error . "\n", FILE_APPEND);
        }
        $stmt->close();
    } else {
        http_response_code(500);
        if ($wantsJson) {
            echo json_encode(["success" => false, "error" => $conn->error]);
        } else {
            echo "❌ SQL Prepare error: " . $conn->error;
        }
        file_put_contents("gps_debug.txt", date("Y-m-d H:i:s") . " SQL Prepare Error: " . $conn->error . "\n", FILE_APPEND);
    }
} else {
    http_response_code(400);
    if ($wantsJson) {
        echo json_encode(["success" => false, "error" => "Invalid or missing latitude/longitude"]);
    } else {
        echo "❌ Invalid or missing latitude/longitude";
    }
    file_put_contents("gps_debug.txt", date("Y-m-d H:i:s") . " Invalid Data: lat=$lat, lng=$lng\n", FILE_APPEND);
}

$conn->close();
?>
