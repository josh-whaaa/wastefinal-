<?php
session_start();
require_once '../includes/conn.php';

if (!isset($_SESSION['client_id'])) {
    header('Location: ../login_page/sign-in.php');
    exit();
}

$clientId = intval($_SESSION['client_id']);

if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['msg'] = 'Failed to upload photo.';
    header('Location: ../client_management/client_profile.php');
    exit();
}

$file = $_FILES['profile_photo'];

// Validate size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    $_SESSION['msg'] = 'Photo is too large. Maximum size is 5MB.';
    header('Location: ../client_management/client_profile.php');
    exit();
}

// Validate MIME type using finfo
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];

if (!isset($allowed[$mime])) {
    $_SESSION['msg'] = 'Invalid image type. Allowed: JPG, PNG, WEBP.';
    header('Location: ../client_management/client_profile.php');
    exit();
}

$ext = $allowed[$mime];
$uploadDir = realpath(__DIR__ . '/../assets/img') . DIRECTORY_SEPARATOR . 'profiles';

// Ensure directory exists
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0775, true)) {
        $_SESSION['msg'] = 'Server error: cannot create upload directory.';
        header('Location: ../client_management/client_profile.php');
        exit();
    }
}

// Remove previous files for this client (any allowed extension)
$baseName = 'client_' . $clientId;
foreach (['jpg', 'jpeg', 'png', 'webp'] as $oldExt) {
    $oldPath = $uploadDir . DIRECTORY_SEPARATOR . $baseName . '.' . $oldExt;
    if (is_file($oldPath)) {
        @unlink($oldPath);
    }
}

$targetPath = $uploadDir . DIRECTORY_SEPARATOR . $baseName . '.' . $ext;

// Move the uploaded file
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    $_SESSION['msg'] = 'Failed to save uploaded photo.';
    header('Location: ../client_management/client_profile.php');
    exit();
}

// Tighten permissions
@chmod($targetPath, 0644);

$_SESSION['msg'] = 'Profile photo updated successfully.';
header('Location: ../client_management/client_profile.php');
exit();
?>


