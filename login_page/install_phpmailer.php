<?php
/**
 * PHPMailer Installation Script
 * Run this once to install PHPMailer via Composer
 */

echo "<h2>PHPMailer Installation Script</h2>";

// Check if composer is available
$composerPath = '';
$possiblePaths = [
    'composer',
    'composer.phar',
    '../composer.phar',
    '../../composer.phar'
];

foreach ($possiblePaths as $path) {
    if (is_executable($path) || file_exists($path)) {
        $composerPath = $path;
        break;
    }
}

if (!$composerPath) {
    echo "<p style='color: red;'>❌ Composer not found. Please install Composer first.</p>";
    echo "<p>Download from: <a href='https://getcomposer.org/download/' target='_blank'>https://getcomposer.org/download/</a></p>";
    exit;
}

echo "<p style='color: green;'>✅ Composer found: $composerPath</p>";

// Check if vendor directory exists
if (file_exists('../vendor/autoload.php')) {
    echo "<p style='color: green;'>✅ PHPMailer already installed!</p>";
    echo "<p>You can now use the email system.</p>";
    exit;
}

// Create composer.json if it doesn't exist
$composerJsonPath = '../composer.json';
if (!file_exists($composerJsonPath)) {
    $composerJson = [
        "require" => [
            "phpmailer/phpmailer" => "^6.8"
        ]
    ];
    
    file_put_contents($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT));
    echo "<p style='color: blue;'>📝 Created composer.json</p>";
}

// Run composer install
echo "<p>🔄 Installing PHPMailer...</p>";
echo "<pre>";

$output = [];
$returnCode = 0;

// Change to parent directory and run composer install
chdir('..');
exec("$composerPath install 2>&1", $output, $returnCode);

foreach ($output as $line) {
    echo htmlspecialchars($line) . "\n";
}

echo "</pre>";

if ($returnCode === 0) {
    echo "<p style='color: green;'>✅ PHPMailer installed successfully!</p>";
    echo "<p>You can now use the full email system with SMTP support.</p>";
    echo "<p><a href='sign-up.php'>Test the registration system</a></p>";
} else {
    echo "<p style='color: red;'>❌ Installation failed. Please check the output above.</p>";
    echo "<p>You can still use the simple email fallback system.</p>";
}

echo "<hr>";
echo "<h3>Alternative: Manual Installation</h3>";
echo "<p>If Composer doesn't work, you can manually download PHPMailer:</p>";
echo "<ol>";
echo "<li>Download PHPMailer from: <a href='https://github.com/PHPMailer/PHPMailer' target='_blank'>GitHub</a></li>";
echo "<li>Extract to: <code>../vendor/phpmailer/phpmailer/</code></li>";
echo "<li>The system will automatically detect it</li>";
echo "</ol>";
?>
