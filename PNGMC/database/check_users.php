<?php
/**
 * Check Users Script
 * This script checks if users exist and verifies their password hashes
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$conn = getDBConnection();

if (!$conn) {
    die("Error: Could not connect to database. Please check your database configuration.\n");
}

echo "Checking users in database...\n\n";

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows === 0) {
    die("Error: Users table does not exist. Please import the database schema first.\n");
}

// Get all users
$result = $conn->query("SELECT user_id, username, full_name, role, status, password_hash FROM users ORDER BY username");

if ($result->num_rows === 0) {
    echo "⚠ No users found in database!\n";
    echo "You need to import the database schema from sms2_database.sql\n";
} else {
    echo "Found " . $result->num_rows . " user(s):\n\n";
    
    $test_passwords = [
        'admin01' => 'adminpass1',
        'admin02' => 'adminpass2',
        'admin03' => 'adminpass3',
        'finance01' => 'financepass1',
        'finance02' => 'financepass2',
        'finance03' => 'financepass3',
        'service01' => 'servicepass1',
        'service02' => 'servicepass2',
        'service03' => 'servicepass3',
        'hod01' => 'hodpass1',
        'hod02' => 'hodpass2',
        'hod03' => 'hodpass3',
    ];
    
    while ($user = $result->fetch_assoc()) {
        echo "Username: " . $user['username'] . "\n";
        echo "  Full Name: " . $user['full_name'] . "\n";
        echo "  Role: " . $user['role'] . "\n";
        echo "  Status: " . $user['status'] . "\n";
        
        // Check if password hash is valid
        if (isset($test_passwords[$user['username']])) {
            $test_password = $test_passwords[$user['username']];
            $password_valid = password_verify($test_password, $user['password_hash']);
            
            if ($password_valid) {
                echo "  ✓ Password hash is VALID\n";
            } else {
                echo "  ✗ Password hash is INVALID (needs update)\n";
                echo "    Expected password: " . $test_password . "\n";
            }
        } else {
            echo "  ? Password hash status: Unknown (not in test list)\n";
        }
        
        // Check if hash looks like a placeholder
        if ($user['password_hash'] === '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi') {
            echo "  ⚠ WARNING: Using placeholder password hash!\n";
        }
        
        echo "\n";
    }
}

$conn->close();

echo "\n";
echo "If passwords are invalid, run: php update_passwords.php\n";
echo "Or visit: http://localhost/sms2/database/update_passwords.php\n";

?>

