<?php
session_start();
require_once '../includes/conn.php';

// --- ADMIN PROFILE UPDATE ---
if (isset($_SESSION['admin_id']) && !isset($_POST['client_id'])) {
    $admin_id = $_SESSION['admin_id'];
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate required fields
    if ($first_name === '' || $last_name === '' || $email === '') {
        echo json_encode(['status' => 'error', 'message' => 'Please fill out all required fields.']);
        exit();
    }

    // Check for existing email (exclude current admin)
    $check = $conn->prepare("SELECT admin_id FROM admin_table WHERE email = ? AND admin_id != ?");
    $check->bind_param("si", $email, $admin_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email already in use.']);
        exit();
    }
    $check->close();

    // If password is provided, hash it
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE admin_table SET first_name = ?, last_name = ?, email = ?, contact = ?, password = ? WHERE admin_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $first_name, $last_name, $email, $contact, $hashed_password, $admin_id);
    } else {
        $sql = "UPDATE admin_table SET first_name = ?, last_name = ?, email = ?, contact = ? WHERE admin_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $contact, $admin_id);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Update failed.']);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// --- CLIENT PROFILE UPDATE (ADMIN OR CLIENT SIDE) ---
$isAdmin = isset($_SESSION['admin_id']) && isset($_POST['client_id']);
if ($isAdmin) {
    // Admin can update any client by client_id from POST
    $client_id = intval($_POST['client_id']);
} elseif (isset($_SESSION['client_id'])) {
    // Client can only update their own profile
    $client_id = $_SESSION['client_id'];
} else {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$barangay = trim($_POST['barangay'] ?? '');
$password = $_POST['password'] ?? '';

// Validate required fields
if ($first_name === '' || $last_name === '' || $email === '' || $barangay === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please fill out all required fields.']);
    exit();
}

// Check for existing email (exclude current client)
$check = $conn->prepare("SELECT client_id FROM client_table WHERE email = ? AND client_id != ?");
$check->bind_param("si", $email, $client_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email already in use.']);
    exit();
}
$check->close();

// If password is provided, hash it
if (!empty($password)) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "UPDATE client_table SET first_name = ?, last_name = ?, email = ?, contact = ?, barangay = ?, password = ? WHERE client_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $contact, $barangay, $hashed_password, $client_id);
} else {
    $sql = "UPDATE client_table SET first_name = ?, last_name = ?, email = ?, contact = ?, barangay = ? WHERE client_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $first_name, $last_name, $email, $contact, $barangay, $client_id);
}

if ($stmt->execute()) {
    // If admin, redirect with session message; if client, return JSON
    if ($isAdmin) {
        $_SESSION['msg'] = 'Profile updated successfully.';
        header("Location: ../admin_management/admin_client_profile.php?client_id=" . $client_id);
        exit();
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully.']);
    }
} else {
    if ($isAdmin) {
        $_SESSION['msg'] = 'Update failed.';
        header("Location: ../admin_management/admin_client_profile.php?client_id=" . $client_id);
        exit();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Update failed.']);
    }
}

$stmt->close();
$conn->close();
?>