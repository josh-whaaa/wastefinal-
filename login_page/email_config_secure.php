<?php
/**
 * Secure Email Configuration for WasteVision AI
 * Reads credentials from .env file (NOT stored in code)
 */

function loadEnvFile($filePath) {
    if (!file_exists($filePath)) {
        return [];
    }
    
    $env = [];
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse key=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $env[$key] = $value;
        }
    }
    
    return $env;
}

// Load environment variables from .env file
$envFile = _DIR_ . '/../.env';
$env = loadEnvFile($envFile);

// Helper function to get environment variable
function env($key, $default = null) {
    global $env;
    return isset($env[$key]) ? $env[$key] : $default;
}

// Email Configuration (reads from .env)
return [
    // SMTP Server Settings
    'smtp_host' => env('SMTP_HOST', 'smtp.gmail.com'),
    'smtp_port' => env('SMTP_PORT', 587),
    'smtp_encryption' => env('SMTP_ENCRYPTION', 'tls'),
    
    // System Email Credentials (from .env file)
    'smtp_username' => env('SMTP_USERNAME'),
    'smtp_password' => env('SMTP_PASSWORD'),
    
    // Sender Information
    'from_email' => env('EMAIL_FROM_ADDRESS', 'noreply@wastevision.ai'),
    'from_name' => env('EMAIL_FROM_NAME', 'WasteVision AI - CEMO'),
    
    // Application Settings
    'app_url' => env('APP_URL', 'http://bagowastetracker.bccbsis.com'),
    'enable_email' => env('EMAIL_ENABLED', 'true') === 'true',
];

/*
 * SECURITY BENEFITS:
 * =================
 * 
 * 1. Credentials are in .env file (NOT in code)
 * 2. .env is in .gitignore (NOT committed to GitHub)
 * 3. Each environment has its own .env file
 * 4. Easy to change credentials without touching code
 * 5. No credentials exposed in version control
 * 
 * SETUP INSTRUCTIONS:
 * ==================
 * 
 * 1. Copy .env.example to .env
 * 2. Edit .env with your actual credentials
 * 3. NEVER commit .env to git
 * 4. Each server/developer has their own .env
 * 
 * IMPORTANT:
 * ==========
 * You only need ONE email account for the SYSTEM!
 * This account sends emails TO all users who register.
 * Users NEVER provide their email passwords!
 */
?>
