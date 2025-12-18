<?php
/**
 * PDO Database Connection Example
 * PNG Maritime College - Students Portal
 * 
 * This example shows how to use the PDO connection that works on both
 * local (XAMPP/Windows) and DigitalOcean (Linux) environments
 */

// Include the PDO configuration
require_once 'pages/includes/pdo_config.php';

try {
    // Get PDO connection (automatically uses environment variables)
    $pdo = getPDOConnection();
    
    // Example: Fetch data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = ? LIMIT 10");
    $stmt->execute(['admin']);
    $users = $stmt->fetchAll();
    
    // Example: Insert data
    // $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)");
    // $stmt->execute([$username, $password_hash, $full_name, $role]);
    
    // Example: Update data
    // $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    // $stmt->execute([$user_id]);
    
    echo "Connection successful!";
    echo "<pre>";
    print_r($users);
    echo "</pre>";
    
} catch (\PDOException $e) {
    // Handle error (don't expose sensitive info in production)
    error_log("Database error: " . $e->getMessage());
    echo "Database error occurred. Please try again later.";
}

?>

