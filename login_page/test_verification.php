<?php
/**
 * Test Email Verification System
 * This page helps debug the verification process
 */

require_once '../includes/conn.php';

echo "<h2>Email Verification System Test</h2>";

// Check if pending_registrations table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'pending_registrations'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<p style='color: green;'>✅ pending_registrations table exists</p>";
        
        // Show pending registrations
        $stmt = $pdo->query("SELECT * FROM pending_registrations ORDER BY created_at DESC LIMIT 5");
        $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($pending) {
            echo "<h3>Recent Pending Registrations:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Email</th><th>Name</th><th>Status</th><th>Created</th><th>Expires</th><th>Token (first 10 chars)</th></tr>";
            
            foreach ($pending as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                echo "<td>" . htmlspecialchars($row['expires_at']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($row['verification_token'], 0, 10)) . "...</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ No pending registrations found</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ pending_registrations table does not exist</p>";
        echo "<p>Creating table...</p>";
        
        $createTableSQL = "
            CREATE TABLE pending_registrations (
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
        echo "<p style='color: green;'>✅ Table created successfully</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test verification link generation
echo "<h3>Test Verification Link</h3>";
$testEmail = "test@example.com";
$testToken = bin2hex(random_bytes(32));
$baseUrl = 'http://localhost/cemo/login_page/verify_email.php';
$testLink = $baseUrl . '?token=' . $testToken . '&email=' . urlencode($testEmail);

echo "<p><strong>Sample verification link format:</strong></p>";
echo "<p><code>" . htmlspecialchars($testLink) . "</code></p>";

// Check if verification script is accessible
echo "<h3>Verification Script Test</h3>";
$verifyUrl = 'http://localhost/cemo/login_page/verify_email.php';
$response = @file_get_contents($verifyUrl . '?token=test&email=test@example.com');

if ($response !== false) {
    echo "<p style='color: green;'>✅ verify_email.php is accessible</p>";
} else {
    echo "<p style='color: red;'>❌ verify_email.php is not accessible</p>";
    echo "<p>Check if the file exists and has proper permissions</p>";
}

// Show recent client registrations
echo "<h3>Recent Client Registrations (from client_table):</h3>";
try {
    $stmt = $pdo->query("SELECT first_name, last_name, email, barangay, created_at FROM client_table ORDER BY id DESC LIMIT 5");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($clients) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Name</th><th>Email</th><th>Barangay</th><th>Created</th></tr>";
        
        foreach ($clients as $client) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($client['email']) . "</td>";
            echo "<td>" . htmlspecialchars($client['barangay']) . "</td>";
            echo "<td>" . htmlspecialchars($client['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No clients found in client_table</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking client_table: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h3>How to Test Verification:</h3>";
echo "<ol>";
echo "<li>Register a new account using the sign-up form</li>";
echo "<li>Check your email for the verification link</li>";
echo "<li>Click the verification link</li>";
echo "<li>You should be redirected to sign-in.php with a success message</li>";
echo "<li>Check this test page to see if the account was moved to client_table</li>";
echo "</ol>";

echo "<p><a href='sign-up.php'>Go to Sign Up</a> | <a href='sign-in.php'>Go to Sign In</a></p>";
?>
