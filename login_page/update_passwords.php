<?php
require_once '../includes/conn.php';

$stmt = $pdo->query("SELECT admin_id, password FROM admin_table");
$users = $stmt->fetchAll();

foreach ($users as $user) {
    if (!password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
        continue; // Skip if already hashed
    }

    $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE admin_table SET password = :password WHERE admin_id = :admin_id");
    $updateStmt->bindParam(':password', $hashed_password);
    $updateStmt->bindParam(':admin_id', $user['admin_id']);
    $updateStmt->execute();
}

echo "Passwords updated successfully!";
?>
