<?php
/**
 * Email Helper for WasteVision AI
 * Sends welcome emails to newly registered users
 */

function sendWelcomeEmail($to, $firstName, $lastName, $barangay) {
    // Email configuration - UPDATE THESE WITH YOUR GMAIL CREDENTIALS
    $smtp_host = "smtp.gmail.com";  // Gmail SMTP server
    $smtp_port = 587;                // Port for TLS
    $smtp_username = "your-email@gmail.com";  // Your Gmail address
    $smtp_password = "your-app-password";      // Your Gmail App Password (NOT regular password)
    $from_email = "noreply@wastevision.ai";
    $from_name = "WasteVision AI";
    
    // Email subject
    $subject = "Welcome to WasteVision AI - Registration Successful";
    
    // Email HTML body
    $message = "
    <html>
    <head>
        <title>Registration Successful</title>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4;'>
            <div style='background-color: #ffffff; padding: 30px; border-radius: 10px;'>
                <h2 style='color: #4CAF50; text-align: center;'>Welcome to WasteVision AI!</h2>
                <p>Dear $firstName $lastName,</p>
                <p>Congratulations! Your account has been successfully registered on <strong>CEMO (City Environment Management Office) of Bago City.</strong>.</p>
                <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0;'>
                    <p style='margin: 0;'><strong>Your Account Details:</strong></p>
                    <p style='margin: 5px 0;'>Email: $to</p>
                    <p style='margin: 5px 0;'>Barangay: $barangay</p>
                </div>
            </div>
        </div>
    </body>
    </html>";
    
    // Plain text alternative
    $text_message = "Welcome to WasteVision AI!\n\n"
        . "Dear $firstName $lastName,\n\n"
        . "Congratulations! Your account has been successfully registered on CEMO (City Environment Management Office) of Bago City.\n\n"
        . "Your Account Details:\n"
        . "Email: $to\n"
        . "Barangay: $barangay\n\n"
        . "This is an automated message from City Environment Management Office (CEMO).";
    
    // Create email headers
    $boundary = md5(time());
    $headers = "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: $from_email\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    
    // Create email body with both text and HTML
    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $text_message . "\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $message . "\r\n";
    $body .= "--$boundary--";
    
    // Try using PHP mail() function first (if SMTP is configured)
    $mail_sent = @mail($to, $subject, $body, $headers);
    
    if ($mail_sent) {
        error_log("Email sent successfully to: $to");
        return true;
    }
    
    // If mail() fails, try using socket connection to Gmail SMTP
    try {
        return sendViaGmailSMTP($to, $subject, $message, $smtp_host, $smtp_port, $smtp_username, $smtp_password, $from_email, $from_name);
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email via Gmail SMTP using socket connection
 */
function sendViaGmailSMTP($to, $subject, $html_message, $host, $port, $username, $password, $from_email, $from_name) {
    // Check if credentials are configured
    if ($username === "your-email@gmail.com" || $password === "your-app-password") {
        error_log("Gmail SMTP credentials not configured in email_helper.php");
        return false;
    }
    
    // Create socket connection
    $socket = fsockopen($host, $port, $errno, $errstr, 30);
    
    if (!$socket) {
        error_log("Could not connect to SMTP server: $errstr ($errno)");
        return false;
    }
    
    // Read server response
    $response = fgets($socket, 515);
    
    // Send EHLO command
    fputs($socket, "EHLO localhost\r\n");
    $response = fgets($socket, 515);
    
    // Start TLS encryption
    fputs($socket, "STARTTLS\r\n");
    $response = fgets($socket, 515);
    
    // Enable crypto
    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    
    // Send EHLO again after STARTTLS
    fputs($socket, "EHLO localhost\r\n");
    $response = fgets($socket, 515);
    
    // Authenticate
    fputs($socket, "AUTH LOGIN\r\n");
    $response = fgets($socket, 515);
    
    fputs($socket, base64_encode($username) . "\r\n");
    $response = fgets($socket, 515);
    
    fputs($socket, base64_encode($password) . "\r\n");
    $response = fgets($socket, 515);
    
    if (strpos($response, '235') === false) {
        error_log("SMTP Authentication failed: $response");
        fclose($socket);
        return false;
    }
    
    // Send email
    fputs($socket, "MAIL FROM: <$from_email>\r\n");
    $response = fgets($socket, 515);
    
    fputs($socket, "RCPT TO: <$to>\r\n");
    $response = fgets($socket, 515);
    
    fputs($socket, "DATA\r\n");
    $response = fgets($socket, 515);
    
    // Send headers and body
    $email_content = "From: $from_name <$from_email>\r\n";
    $email_content .= "To: <$to>\r\n";
    $email_content .= "Subject: $subject\r\n";
    $email_content .= "MIME-Version: 1.0\r\n";
    $email_content .= "Content-Type: text/html; charset=UTF-8\r\n";
    $email_content .= "\r\n";
    $email_content .= $html_message . "\r\n";
    $email_content .= ".\r\n";
    
    fputs($socket, $email_content);
    $response = fgets($socket, 515);
    
    // Quit
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    error_log("Email sent successfully via SMTP to: $to");
    return true;
}

/**
 * Simple email function using mail() - for basic testing
 */
function sendSimpleEmail($to, $firstName, $lastName, $barangay) {
    $subject = "Welcome to WasteVision AI";
    $message = "Dear $firstName $lastName,\n\n";
    $message .= "Your account has been successfully registered!\n\n";
    $message .= "Email: $to\n";
    $message .= "Barangay: $barangay\n\n";
    $message .= "Login at: http://bagowastetracker.bccbsis.com\n";
    
    $headers = "From: WasteVision AI <noreply@wastevision.ai>";
    
    return @mail($to, $subject, $message, $headers);
}
?>