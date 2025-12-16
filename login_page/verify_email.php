<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/conn.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

// Log verification attempt
error_log("Verification attempt - Email: $email, Token: " . substr($token, 0, 10) . "...");

if (empty($token) || empty($email)) {
    error_log("Verification failed - Missing token or email");
    header("Location: sign-in.php?error=invalid_verification");
    exit;
}

try {
    // First, ensure the pending_registrations table exists
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS pending_registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            barangay VARCHAR(100) NOT NULL,
            verification_token VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('pending', 'verified', 'expired') DEFAULT 'pending',
            UNIQUE KEY unique_email (email),
            UNIQUE KEY unique_token (verification_token)
        )
    ";
    $pdo->exec($createTableSQL);
    
    // Check if verification token is valid and not expired
    $stmt = $pdo->prepare("
        SELECT * FROM pending_registrations 
        WHERE email = :email AND verification_token = :token 
        AND status = 'pending' AND expires_at > NOW()
    ");
    $stmt->execute([
        ':email' => $email,
        ':token' => $token
    ]);
    
    $pending_registration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pending_registration) {
        // Check if token exists but is expired or already used
        $stmt = $pdo->prepare("
            SELECT * FROM pending_registrations 
            WHERE email = :email AND verification_token = :token
        ");
        $stmt->execute([
            ':email' => $email,
            ':token' => $token
        ]);
        
        $expired_registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($expired_registration) {
            if ($expired_registration['status'] === 'verified') {
                error_log("Verification failed - Token already used");
                header("Location: sign-in.php?error=already_verified");
            } else {
                error_log("Verification failed - Token expired");
                header("Location: sign-in.php?error=token_expired");
            }
        } else {
            error_log("Verification failed - Token not found");
            header("Location: sign-in.php?error=invalid_token");
        }
        exit;
    }
    
    // Mark as verified
    $stmt = $pdo->prepare("
        UPDATE pending_registrations 
        SET status = 'verified' 
        WHERE email = :email AND verification_token = :token
    ");
    $stmt->execute([
        ':email' => $email,
        ':token' => $token
    ]);
    
    // Check if user already exists in client_table
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM client_table WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $userExists = $stmt->fetchColumn() > 0;
    
    if (!$userExists) {
        // Move to actual client table
        $hashed_password = password_hash('temp_password_' . time(), PASSWORD_DEFAULT); // Temporary password
        
        $stmt = $pdo->prepare("
            INSERT INTO client_table (first_name, last_name, contact, barangay, email, password) 
            VALUES (:first_name, :last_name, :contact, :barangay, :email, :password)
        ");
        
        $stmt->execute([
            ':first_name' => $pending_registration['first_name'],
            ':last_name' => $pending_registration['last_name'],
            ':contact' => '00000000000', // Default contact, user will update later
            ':barangay' => $pending_registration['barangay'],
            ':email' => $pending_registration['email'],
            ':password' => $hashed_password
        ]);
        
        error_log("User created successfully: $email");
    } else {
        error_log("User already exists in client_table: $email");
    }
    
    // Clean up pending registration
    $stmt = $pdo->prepare("DELETE FROM pending_registrations WHERE email = :email");
    $stmt->execute([':email' => $email]);
    
    // Redirect to success page
    error_log("Verification successful for: $email");
    header("Location: sign-in.php?verified=success&email=" . urlencode($email));
    exit;
    
} catch (Exception $e) {
    error_log("Email verification error: " . $e->getMessage());
    header("Location: sign-in.php?error=verification_failed");
    exit;
}
?>
