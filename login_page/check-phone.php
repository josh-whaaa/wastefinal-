<?php
require_once '../includes/conn.php';

header('Content-Type: application/json');

if (isset($_GET['contact'])) {
    $contact = trim($_GET['contact']);
    $exists = false;

    try {
        // Check client_table
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM client_table WHERE contact = ?");
        $stmt->execute([$contact]);
        if ($stmt->fetchColumn() > 0) $exists = true;

        // Check admin_table
        if (!$exists) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_table WHERE contact = ?");
            $stmt->execute([$contact]);
            if ($stmt->fetchColumn() > 0) $exists = true;
        }

        // Check driver_table
        if (!$exists) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM driver_table WHERE contact = ?");
            $stmt->execute([$contact]);
            if ($stmt->fetchColumn() > 0) $exists = true;
        }

        echo json_encode(['exists' => $exists]);
    } catch (Exception $e) {
        echo json_encode(['exists' => false, 'error' => 'db_error']);
    }
} else {
    echo json_encode(['exists' => false, 'error' => 'missing_param']);
}
?>
