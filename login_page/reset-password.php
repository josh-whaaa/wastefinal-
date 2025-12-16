<?php
session_start();
require_once '../includes/conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $new_password = $_POST['password'];

    if (empty($new_password)) {
        header("Location: reset-password.php?token=$token&status=empty_password");
        exit;
    }

    // Find user with token
    $stmt = $pdo->prepare("SELECT * FROM admin_table WHERE reset_token = :token");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: reset-password.php?status=invalid_token");
        exit;
    }

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE admin_table SET password = :password, reset_token = NULL WHERE reset_token = :token");
    $stmt->execute([':password' => $hashed_password, ':token' => $token]);

    header("Location: sign-in.php?status=password_reset_success");
    exit;
}
?>