<?php
/**
 * Password Update Script
 * This script updates the default password hashes in the database
 * with properly hashed versions of the temporary passwords
 * 
 * Usage: Run this script once after creating the database
 * php update_passwords.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

// Map of usernames to their temporary passwords (from temp_users.php)
$user_passwords = [
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

$conn = getDBConnection();

if (!$conn) {
    die("Error: Could not connect to database. Please check your database configuration.\n");
}

echo "Updating password hashes...\n\n";

$updated = 0;
$errors = 0;

foreach ($user_passwords as $username => $password) {
    // Generate password hash
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Update database
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
    $stmt->bind_param("ss", $password_hash, $username);
    
    if ($stmt->execute()) {
        echo "✓ Updated password for: $username\n";
        $updated++;
    } else {
        echo "✗ Error updating password for: $username - " . $stmt->error . "\n";
        $errors++;
    }
    
    $stmt->close();
}

$conn->close();

echo "\n";
echo "Update complete!\n";
echo "Updated: $updated users\n";
if ($errors > 0) {
    echo "Errors: $errors users\n";
}
echo "\nYou can now use the login system with the database.\n";

?>

