<?php
session_start();
include '../includes/conn.php';
include '../includes/request_threshold_validator.php';

if (!isset($_SESSION['client_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $client_id = $_POST['client_id'];
        $client_name = $_POST['client_name'];
        $client_email = $_POST['client_email'];
        $client_contact = $_POST['client_contact'];
        $client_barangay = $_POST['client_barangay'];
        $request_type = $_POST['request_type'];
        $other_request = isset($_POST['other_request']) ? $_POST['other_request'] : '';
        $request_description = $_POST['request_description'];
        $request_date = $_POST['request_date'];
        $request_time = $_POST['request_time'];
        
        // Determine the actual request details
        $request_details = ($request_type === 'Other') ? $other_request : $request_type;

        // Initialize threshold validator
        $validator = new RequestThresholdValidator($pdo);
        
        // Prepare request data for validation
        $requestData = [
            'client_id' => $client_id,
            'request_date' => $request_date,
            'request_time' => $request_time,
            'request_description' => $request_description,
            'request_details' => $request_details
        ];
        
        // Validate request against thresholds
        $validation = $validator->validateRequest($requestData);
        
        if (!$validation['valid']) {
            // Request failed validation
            $error_message = implode(' ', $validation['errors']);
            $_SESSION['client_alert'] = [
                'title' => 'Request Validation Failed',
                'text' => $error_message,
                'icon' => 'error'
            ];
            header("Location: client_request.php");
            exit();
        }
        
        // Show warnings if any (but still allow submission)
        if (!empty($validation['warnings'])) {
            $warning_message = implode(' ', $validation['warnings']);
            $_SESSION['client_alert'] = [
                'title' => 'Request Submitted with Warnings',
                'text' => $warning_message,
                'icon' => 'warning'
            ];
        }

        // Generate a custom unique request ID like REQ202508062105
        $request_id = 'REQ' . date('YmdHis'); // Format: REQ + YYYYMMDDHHMMSS

        
        // Insert into requests table
        $stmt = $pdo->prepare("INSERT INTO client_requests (
            request_id,
            client_id, 
            client_name, 
            client_email, 
            client_contact, 
            client_barangay, 
            request_type, 
            request_details, 
            request_description, 
            request_date, 
            request_time, 
            status, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");

    $stmt->execute([
        $request_id,
        $client_id,
        $client_name,
        $client_email,
        $client_contact,
        $client_barangay,
        $request_type,
        $request_details,
        $request_description,
        $request_date,
        $request_time
    ]);

        
        // use the custom request_id instead
        $id = $request_id;
        
        // Create notification for admin
        $notification_message = "New service request from {$client_name} for {$request_details} on {$request_date} at {$request_time}";
        
        $stmt = $pdo->prepare("INSERT INTO admin_notifications (
                title,
                message,
                notification_type,
                id,
                is_read,
                created_at
            ) VALUES (?, ?, 'new_request', ?, 0, NOW())
        ");

        $stmt->execute([
            "New Service Request",
            $notification_message,
            $id
        ]);

        
        // Set success message
            $_SESSION['client_alert'] = [
                'title' => 'Request Submitted!',
                'text' => 'Your request has been submitted successfully! You will be notified once it\'s approved.',
                'icon' => 'success'
            ];        
        // Redirect back to request form
        header("Location: client_request.php");
        exit();
        
    } catch (PDOException $e) {
    $_SESSION['client_alert'] = [
        'title' => 'Submission Failed',
        'text' => 'Error submitting request: ' . $e->getMessage(),
        'icon' => 'error'
    ];
        header("Location: client_request.php");
        exit();
    }
} else {
    header("Location: client_request.php");
    exit();
}
?>