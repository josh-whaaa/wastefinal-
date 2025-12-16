<?php
require_once '../includes/conn.php';

header('Content-Type: application/json');

if (isset($_GET['email'])) {
    $email = trim($_GET['email']);
    $exists = false;

    try {
        // Check admin_table
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_table WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetchColumn() > 0) $exists = true;

        // Check client_table
        if (!$exists) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM client_table WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetchColumn() > 0) $exists = true;
        }

        // Check driver_table
        if (!$exists) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM driver_table WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetchColumn() > 0) $exists = true;
        }

        echo json_encode(["exists" => $exists]);
    } catch (Exception $e) {
        echo json_encode(["exists" => false, "error" => "db_error"]);
    }
} else {
    echo json_encode(["exists" => false, "error" => "missing_param"]);
}
?>
