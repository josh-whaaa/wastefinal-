<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/conn.php'; // Ensure this file properly initializes $pdo

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $account_type = trim($_POST['account_type'] ?? 'client'); // Default to client if not set
    
    // Log registration attempt for debugging
    error_log("Registration attempt - Account Type: $account_type, Email: $email");
    
    // Get optional fields (only for client)
    $contact = trim($_POST['contact'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $_SESSION['signup_error'] = "empty_fields";
        header("Location: sign-up.php");
        exit;
    }
    
    // For client, also require contact and barangay
    if ($account_type === 'client' && (empty($contact) || empty($barangay))) {
        $_SESSION['signup_error'] = "empty_fields";
        header("Location: sign-up.php");
        exit;
    }

    // Check if email already exists in any table
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_table WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $email_exists = $stmt->fetchColumn() > 0;

    if (!$email_exists) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM client_table WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $email_exists = $stmt->fetchColumn() > 0;
    }
    
    // Check driver_table for email_add (only if email_add column exists)
    if (!$email_exists) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM driver_table WHERE email_add = :email");
            $stmt->execute([':email' => $email]);
            $email_exists = $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            // email_add column doesn't exist yet - this is OK, just skip the check
            // User needs to run: ALTER TABLE driver_table ADD COLUMN email_add VARCHAR(255) UNIQUE;
        }
    }
    
    if ($email_exists) {
        $_SESSION['signup_error'] = "email_exists";
        header("Location: sign-up.php");
        exit;
    }

    // Check if contact already exists (only for client)
    if ($account_type === 'client' && !empty($contact)) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_table WHERE contact = :contact");
    $stmt->execute([':contact' => $contact]);
    $contact_exists = $stmt->fetchColumn() > 0;

    if (!$contact_exists) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM client_table WHERE contact = :contact");
        $stmt->execute([':contact' => $contact]);
        $contact_exists = $stmt->fetchColumn() > 0;
    }
    if (!$contact_exists) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM driver_table WHERE contact = :contact");
        $stmt->execute([':contact' => $contact]);
        $contact_exists = $stmt->fetchColumn() > 0;
    }
    if ($contact_exists) {
        $_SESSION['signup_error'] = "contact_exists";
        header("Location: sign-up.php");
        exit;
        }
    }


    // ✅ Secure password hashing
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Handle registration based on account type
    // Ensure account_type is valid
    if (!in_array($account_type, ['client', 'driver'])) {
        $account_type = 'client'; // Default to client if invalid
        error_log("Invalid account_type received, defaulting to client");
    }
    
    if ($account_type === 'driver') {
        // Driver registration - insert into driver_table
        // Set default values for required fields that aren't collected during signup
        $address = ''; // Will be updated later
        $age = 0; // Will be updated later
        $gender = ''; // Will be updated later
        $location_id = 1; // Default location
        $license_no = ''; // Will be updated later
        $driver_contact = ''; // Empty for now, can be updated later
        
        // Check if email_add column exists, if not, add it automatically
        try {
            $checkColumn = $pdo->query("SHOW COLUMNS FROM driver_table LIKE 'email_add'");
            if ($checkColumn->rowCount() === 0) {
                // Add email_add column if it doesn't exist
                $pdo->exec("ALTER TABLE driver_table ADD COLUMN email_add VARCHAR(255) NULL AFTER last_name");
                error_log("Added email_add column to driver_table");
            }
        } catch (PDOException $e) {
            error_log("Error checking/adding email_add column: " . $e->getMessage());
            // Continue anyway - will try without email if column doesn't exist
        }
        
        $insertSuccess = false;
        $driver_id = null;
        
        // Try to insert with email_add column first
        try {
            $stmt = $pdo->prepare("INSERT INTO driver_table (first_name, last_name, email_add, password, address, contact, age, gender, location_id, license_no) VALUES (:first_name, :last_name, :email, :password, :address, :contact, :age, :gender, :location_id, :license_no)");
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':contact', $driver_contact);
            $stmt->bindParam(':age', $age);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':location_id', $location_id);
            $stmt->bindParam(':license_no', $license_no);
            
            if ($stmt->execute()) {
                $insertSuccess = true;
                $driver_id = $pdo->lastInsertId();
                error_log("Driver registered successfully with email_add column. Driver ID: $driver_id");
            }
        } catch (PDOException $e) {
            // If email_add column still doesn't exist or insertion failed, try without email
            error_log("First insert attempt failed: " . $e->getMessage() . ". Trying without email_add...");
            
            try {
                $stmt = $pdo->prepare("INSERT INTO driver_table (first_name, last_name, password, address, contact, age, gender, location_id, license_no) VALUES (:first_name, :last_name, :password, :address, :contact, :age, :gender, :location_id, :license_no)");
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':address', $address);
                $stmt->bindParam(':contact', $driver_contact);
                $stmt->bindParam(':age', $age);
                $stmt->bindParam(':gender', $gender);
                $stmt->bindParam(':location_id', $location_id);
                $stmt->bindParam(':license_no', $license_no);
                
                if ($stmt->execute()) {
                    $insertSuccess = true;
                    $driver_id = $pdo->lastInsertId();
                    error_log("Driver registered successfully without email_add column. Driver ID: $driver_id");
                    
                    // Try to update email_add if column exists now
                    try {
                        $updateStmt = $pdo->prepare("UPDATE driver_table SET email_add = :email WHERE driver_id = :driver_id");
                        $updateStmt->execute([':email' => $email, ':driver_id' => $driver_id]);
                        error_log("Updated email_add for driver ID: $driver_id");
                    } catch (PDOException $updateError) {
                        error_log("Could not update email_add: " . $updateError->getMessage());
                        // Continue anyway - registration was successful
                    }
                }
            } catch (PDOException $e2) {
                // Both attempts failed - log detailed error
                $errorMsg = "Driver registration failed: " . $e2->getMessage();
                error_log($errorMsg);
                error_log("SQL Error Code: " . $e2->getCode());
                error_log("Failed SQL: INSERT INTO driver_table (first_name, last_name, password, address, contact, age, gender, location_id, license_no)");
                
                $_SESSION['signup_error'] = "signup_failed";
                $_SESSION['signup_error_details'] = "Database error: " . $e2->getMessage();
                header("Location: sign-up.php");
                exit;
            }
        }
        
        if ($insertSuccess) {
            // Send welcome email for driver
            try {
                require_once 'standalone_email_sender.php';
                $emailSent = StandaloneEmailSender::sendDriverWelcomeEmail($email, $first_name, $last_name);
                
                if ($emailSent) {
                    error_log("Welcome email sent successfully to driver: $email");
                } else {
                    error_log("Failed to send welcome email to driver: $email (but registration was successful)");
                }
            } catch (Exception $e) {
                error_log("Email notification error (registration still successful): " . $e->getMessage());
            }
            
            $_SESSION['signup_success'] = "Driver account created successfully!";
            header("Location: sign-in.php?status=success");
            exit;
        } else {
            $_SESSION['signup_error'] = "signup_failed";
            $_SESSION['signup_error_details'] = "Unable to create driver account. Please contact support.";
            header("Location: sign-up.php");
            exit;
        }
    } else {
        // Client registration - existing logic
        $stmt = $pdo->prepare("INSERT INTO client_table (first_name, last_name, contact, barangay, email, password) VALUES (:first_name, :last_name, :contact, :barangay, :email, :password)");
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':contact', $contact);
        $stmt->bindParam(':barangay', $barangay);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        
        if ($stmt->execute()) {
            // Send welcome email for client
            try {
                require_once 'standalone_email_sender.php';
                $emailSent = StandaloneEmailSender::sendWelcomeEmail($email, $first_name, $last_name, $barangay);
                
                if ($emailSent) {
                    error_log("Welcome email sent successfully to: $email");
                } else {
                    error_log("Failed to send welcome email to: $email");
                }
            } catch (Exception $e) {
                error_log("Email notification error: " . $e->getMessage());
            }
            
            $_SESSION['signup_success'] = "Account created successfully!";
            header("Location: sign-in.php?status=success");
            exit;
        } else {
            $_SESSION['signup_error'] = "signup_failed";
            header("Location: sign-up.php");
            exit;
        }
    }
}

// Error Messages
$messages = [
    "empty_fields" => "Please fill in all required fields.",
    "email_exists" => "This email is already registered.",
    "contact_exists" => "The contact number is already registered.",
    "signup_failed" => "Registration failed. Please try again or contact support."
];

// Display detailed error if available (for debugging - remove in production)
if (isset($_SESSION['signup_error_details'])) {
    error_log("Signup error details: " . $_SESSION['signup_error_details']);
    // Uncomment below for debugging:
    // $messages["signup_failed"] = $_SESSION['signup_error_details'];
    unset($_SESSION['signup_error_details']);
}

$status = $_GET['status'] ?? null;
?>

<!-- Toast Notifications -->
<?php if ($status && isset($messages[$status])): ?>
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
        <div id="toastMessage" class="toast align-items-center text-white <?= ($status === 'success') ? 'bg-success' : 'bg-danger'; ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
            <div class="d-flex">
                <div class="toast-body">
                    <?= htmlspecialchars($messages[$status]) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>
