<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/conn.php';
require_once 'standalone_email_sender.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $barangay = trim($_POST['barangay']);
    
    // Validate required fields
    if (empty($email) || empty($first_name) || empty($last_name) || empty($barangay)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_table WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $email_exists = $stmt->fetchColumn() > 0;
        
        if (!$email_exists) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM client_table WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $email_exists = $stmt->fetchColumn() > 0;
        }
        
        if ($email_exists) {
            echo json_encode(['success' => false, 'message' => 'Email already registered']);
            exit;
        }
        
        // Generate verification token
        $verification_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Store pending registration in a temporary table or add status column
        // For now, we'll create a simple pending registrations table
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
        
        // Insert pending registration
        $stmt = $pdo->prepare("
            INSERT INTO pending_registrations (email, first_name, last_name, barangay, verification_token, expires_at) 
            VALUES (:email, :first_name, :last_name, :barangay, :token, :expires_at)
            ON DUPLICATE KEY UPDATE 
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            barangay = VALUES(barangay),
            verification_token = VALUES(verification_token),
            expires_at = VALUES(expires_at),
            status = 'pending',
            created_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            ':email' => $email,
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':barangay' => $barangay,
            ':token' => $verification_token,
            ':expires_at' => $expires_at
        ]);
        
        // Send verification email using your existing system
        $emailSent = StandaloneEmailSender::sendWelcomeEmail($email, $first_name, $last_name, $barangay, $verification_token);
        
        if ($emailSent) {
            // Store verification info in session for success message
            $_SESSION['pending_email'] = $email;
            $_SESSION['verification_sent'] = true;
            
            echo json_encode([
                'success' => true, 
                'message' => 'Verification email sent successfully',
                'email' => $email
            ]);
        } else {
            // Remove the pending registration if email failed
            $stmt = $pdo->prepare("DELETE FROM pending_registrations WHERE email = :email");
            $stmt->execute([':email' => $email]);
            
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to send verification email. Please try again.'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Email verification error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'An error occurred. Please try again later.'
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
