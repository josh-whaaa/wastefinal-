<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/conn.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $_SESSION['login_attempt_email'] = $email; // Store email for reset link

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "empty_fields";
        header("Location: sign-in.php?status=empty_fields&email=" . urlencode($email));
        exit;
    }

    // Fetch Admin details from database
    $adminUser = null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM admin_table WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $adminUser = $stmt->fetch();
    } catch (PDOException $e) {
        // Table might not exist, log error but continue with other checks
        error_log("Could not fetch admin user: " . $e->getMessage());
        $adminUser = null;
    }

    // Fetch Client details from database
    $clientUser = null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM client_table WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $clientUser = $stmt->fetch();
    } catch (PDOException $e) {
        // Table might not exist, log error but continue with other checks
        error_log("Could not fetch client user: " . $e->getMessage());
        $clientUser = null;
    }

    // Fetch Driver details from database
    $driverUser = null;
    $emailColumnExists = true;
    $driverExistsWithoutEmail = false;
    
    // First, check if email_add column exists in driver_table
    try {
        $checkColumn = $pdo->query("SHOW COLUMNS FROM driver_table LIKE 'email_add'");
        $emailColumnExists = $checkColumn->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Could not check for email_add column: " . $e->getMessage());
        $emailColumnExists = false;
    }
    
    if ($emailColumnExists) {
        // Email column exists, try to fetch driver by email
        try {
            $stmt = $pdo->prepare("SELECT * FROM driver_table WHERE email_add = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $driverUser = $stmt->fetch();
            
            // If driver not found by email, check if there are any drivers without email
            // This helps identify if the driver was registered before email column was added
            if (!$driverUser) {
                $checkDrivers = $pdo->query("SELECT COUNT(*) FROM driver_table WHERE (email_add IS NULL OR email_add = '')");
                $driversWithoutEmail = $checkDrivers->fetchColumn();
                if ($driversWithoutEmail > 0) {
                    $driverExistsWithoutEmail = true;
                    error_log("Found {$driversWithoutEmail} driver(s) without email addresses. They need to update their email.");
                }
            }
        } catch (PDOException $e) {
            error_log("Driver login query error: " . $e->getMessage());
        }
    } else {
        // Email column doesn't exist - drivers can't log in with email yet
        error_log("WARNING: email_add column does not exist in driver_table. Drivers cannot log in until the column is added.");
        error_log("Please run: ALTER TABLE driver_table ADD COLUMN email_add VARCHAR(255) UNIQUE AFTER last_name;");
        
        // Check if there are any drivers in the table
        try {
            $checkDrivers = $pdo->query("SELECT COUNT(*) FROM driver_table");
            $driverCount = $checkDrivers->fetchColumn();
            if ($driverCount > 0) {
                $driverExistsWithoutEmail = true;
                error_log("Found {$driverCount} driver(s) in database, but email column doesn't exist.");
            }
        } catch (PDOException $e) {
            // Ignore
        }
    }

    // Check if the user exists in any table
    if (!$adminUser && !$clientUser && !$driverUser) {
        // If driver exists but doesn't have email, provide specific error
        if ($driverExistsWithoutEmail) {
            $_SESSION['login_error'] = "driver_no_email";
            $_SESSION['login_error_message'] = "Driver account found but email is not set. Please contact administrator to update your email address.";
        } else {
            $_SESSION['login_error'] = "invalid_user";
        }
        header("Location: sign-in.php?status=invalid_user&email=" . urlencode($email));
        exit;
    }

    // Verify Admin Login
    if ($adminUser && password_verify($password, $adminUser['password'])) {
        // Clear any existing session data to prevent role mixing
        session_unset();
        session_start();
        
        // ✅ Store Admin Session - Only admin data
        $_SESSION['user_role'] = 'admin';
        $_SESSION['email'] = $adminUser['email'];
        $_SESSION['admin_id'] = $adminUser['admin_id'];
        $_SESSION['first_name'] = $adminUser['first_name'] ?? '';

        // ✅ Login Successful
        unset($_SESSION['login_error'], $_SESSION['login_attempt_email']);
        header("Location: ../dashboard_management/admin_dashboard.php");
        exit;
    }

    // Verify Client Login
    if ($clientUser && password_verify($password, $clientUser['password'])) {
        // Clear any existing session data to prevent role mixing
        session_unset();
        session_start();
        
        // ✅ Store Client Session - Only client data
        $_SESSION['user_role'] = 'client';
        $_SESSION['client_id'] = $clientUser['client_id'];
        $_SESSION['first_name'] = $clientUser['first_name'] ?? $clientUser['name'] ?? '';
        $_SESSION['email'] = $clientUser['email'];

        // ✅ Login Successful
        unset($_SESSION['login_error'], $_SESSION['login_attempt_email']);
        header('Location: ../dashboard_management/client_dashboard.php');
        exit;
    }

    // Verify Driver Login
    if ($driverUser && password_verify($password, $driverUser['password'])) {
        // Clear any existing session data to prevent role mixing
        session_unset();
        session_start();
        
        // ✅ Store Driver Session - Only driver data (NO client_id mixing)
        $_SESSION['user_role'] = 'driver';
        $_SESSION['driver_id'] = $driverUser['driver_id'];
        $_SESSION['first_name'] = $driverUser['first_name'] ?? '';
        $_SESSION['email'] = $driverUser['email_add'] ?? $email;

        // ✅ Login Successful - Redirect to driver dashboard
        unset($_SESSION['login_error'], $_SESSION['login_attempt_email']);
        header('Location: ../dashboard_management/driver_dashboard.php');
        exit;
    }

    // If no valid credentials were found
    // Example: Wrong password
    header("Location: sign-in.php?status=wrong_password&email=" . urlencode($_POST['email']));
    exit();

}
?>
