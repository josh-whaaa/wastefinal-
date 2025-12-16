<?php
// Standalone Email Sender - No complex paths needed
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Try different paths for autoload
$autoloadPaths = [
    '../vendor/autoload.php',
    'vendor/autoload.php',
    '../../vendor/autoload.php'
];

$autoloadLoaded = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloadLoaded = true;
            break;
    }
}

if (!$autoloadLoaded) {
    // Fallback: try to load PHPMailer directly
    $phpmailerPaths = [
        '../vendor/phpmailer/phpmailer/src/PHPMailer.php',
        'vendor/phpmailer/phpmailer/src/PHPMailer.php',
        '../vendor/phpmailer/phpmailer/src/Exception.php',
        'vendor/phpmailer/phpmailer/src/Exception.php',
        '../vendor/phpmailer/phpmailer/src/SMTP.php',
        'vendor/phpmailer/phpmailer/src/SMTP.php'
    ];
    
    foreach ($phpmailerPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
        }
    }
}

class StandaloneEmailSender {
    
    public static function sendWelcomeEmail($userEmail, $firstName, $lastName, $barangay) {
        try {
            $mail = new PHPMailer(true);
            
            // SMTP Configuration - Choose your provider
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            
            // ========== EMAIL CONFIGURATION ==========
            // Change these settings to your email provider
            
            // For Gmail (uncomment these lines):
            // $mail->Host = 'smtp.gmail.com';
            // $mail->Username = 'your-gmail@gmail.com'; // Replace with your Gmail
            // $mail->Password = 'your-app-password'; // Replace with your App Password
            
            // For Hostinger (current configuration):
            $mail->Host = 'smtp.hostinger.com';
            $mail->Username = 'waste_management_info@bccbsis.com'; // Your Hostinger email
            $mail->Password = '0?T]*8&Iw>h'; // Your Hostinger email password
            
            // For Yahoo (uncomment these and comment Gmail lines above):
            // $mail->Host = 'smtp.mail.yahoo.com';
            // $mail->Username = 'youremail@yahoo.com'; // Replace with your Yahoo
            // $mail->Password = 'your-yahoo-app-password'; // Replace with your App Password
            
            // Email content
            $mail->setFrom($mail->Username, 'WasteVision AI System');
            $mail->addAddress($userEmail);
            $mail->isHTML(true);
                $mail->Subject = 'Welcome to WasteVision AI - Registration Successful';
            
            // Beautiful HTML email template
            $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4; }
                    .content { background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                    .header { text-align: center; margin-bottom: 20px; }
                    .header h2 { color: #4CAF50; margin: 0; }
                    .info-box { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0; border-radius: 4px; }
                    .login-button { background-color: #4CAF50; color: white; padding: 14px 35px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold; }
                    .footer { color: #666; font-size: 12px; text-align: center; margin: 10px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='content'>
                        <div class='header'>
                            <h2>Welcome to WasteVision AI!</h2>
                        </div>
                        <p>Dear <strong>$firstName $lastName</strong>,</p>
                        <p>Congratulations! Your account has been successfully registered on <strong>CEMO (City Environment Management Office) of Bago City</strong>.</p>
                        <div class='info-box'>
                            <p style='margin: 0 0 10px 0;'><strong>Your Account Details:</strong></p>
                            <p style='margin: 5px 0;'>📧 <strong>Email:</strong> $userEmail</p>
                            <p style='margin: 5px 0;'>📍 <strong>Barangay:</strong> $barangay</p>
                        </div>
                        <p>You can now log in to your account and start using our waste management services.</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='http://localhost/online_file/login_page/sign-in.php' class='login-button'>Login to Your Account</a>
                        </div>
                        <div class='footer'>
                            This is an automated message from City Environment Management Office (CEMO).<br>
                            Please do not reply to this email.
                        </div>
                    </div>
                </div>
            </body>
            </html>";
            
            // Send email
            $result = $mail->send();
            
            if ($result) {
                error_log("Welcome email sent successfully to: $userEmail");
                return true;
            } else {
                error_log("Failed to send welcome email to: $userEmail");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function sendDriverWelcomeEmail($userEmail, $firstName, $lastName) {
        try {
            $mail = new PHPMailer(true);
            
            // SMTP Configuration - Choose your provider
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            
            // ========== EMAIL CONFIGURATION ==========
            // Change these settings to your email provider
            
            // For Gmail (uncomment these lines):
            // $mail->Host = 'smtp.gmail.com';
            // $mail->Username = 'your-gmail@gmail.com'; // Replace with your Gmail
            // $mail->Password = 'your-app-password'; // Replace with your App Password
            
            // For Hostinger (current configuration):
            $mail->Host = 'smtp.hostinger.com';
            $mail->Username = 'waste_management_info@bccbsis.com'; // Your Hostinger email
            $mail->Password = '0?T]*8&Iw>h'; // Your Hostinger email password
            
            // For Yahoo (uncomment these and comment Gmail lines above):
            // $mail->Host = 'smtp.mail.yahoo.com';
            // $mail->Username = 'youremail@yahoo.com'; // Replace with your Yahoo
            // $mail->Password = 'your-yahoo-app-password'; // Replace with your App Password
            
            // Email content
            $mail->setFrom($mail->Username, 'WasteVision AI System');
            $mail->addAddress($userEmail);
            $mail->isHTML(true);
            $mail->Subject = 'Welcome to WasteVision AI - Driver Registration Successful';
            
            // Beautiful HTML email template for drivers
            $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4; }
                    .content { background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                    .header { text-align: center; margin-bottom: 20px; }
                    .header h2 { color: #4CAF50; margin: 0; }
                    .info-box { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0; border-radius: 4px; }
                    .login-button { background-color: #4CAF50; color: white; padding: 14px 35px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold; }
                    .footer { color: #666; font-size: 12px; text-align: center; margin: 10px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='content'>
                        <div class='header'>
                            <h2>Welcome to WasteVision AI!</h2>
                        </div>
                        <p>Dear <strong>$firstName $lastName</strong>,</p>
                        <p>Congratulations! Your driver account has been successfully registered on <strong>CEMO (City Environment Management Office) of Bago City</strong>.</p>
                        <div class='info-box'>
                            <p style='margin: 0 0 10px 0;'><strong>Your Driver Account Details:</strong></p>
                            <p style='margin: 5px 0;'>📧 <strong>Email:</strong> $userEmail</p>
                            <p style='margin: 5px 0;'>👤 <strong>Name:</strong> $firstName $lastName</p>
                        </div>
                        <p>You can now log in to your driver account and start using our waste management services.</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='http://localhost/online_file/login_page/sign-in.php' class='login-button'>Login to Your Account</a>
                        </div>
                        <div class='footer'>
                            This is an automated message from City Environment Management Office (CEMO).<br>
                            Please do not reply to this email.
                        </div>
                    </div>
                </div>
            </body>
            </html>";
            
            // Send email
            $result = $mail->send();
        
        if ($result) {
                error_log("Welcome email sent successfully to driver: $userEmail");
            return true;
        } else {
                error_log("Failed to send welcome email to driver: $userEmail");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
}
?>
