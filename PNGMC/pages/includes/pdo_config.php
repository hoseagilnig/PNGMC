<?php
/**
 * PDO Database Configuration File
 * PNG Maritime College - Student Management System
 * 
 * PDO database connection using environment variables (.env file)
 * Works on both local (XAMPP/Windows) and DigitalOcean (Linux) environments
 */

// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Database connection settings
// Priority: 1. Environment variable (.env), 2. System environment, 3. Default value
// Auto-detect port: 3307 for Windows/XAMPP, 3306 for Linux
$default_port = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? '3307' : '3306';

$host = getEnvVar('DB_HOST', 'localhost');
$port = (int)getEnvVar('DB_PORT', $default_port);  // Auto-detects: 3307 for Windows, 3306 for Linux
$db   = getEnvVar('DB_NAME', 'sms2_db');
$user = getEnvVar('DB_USER', 'root');
$pass = getEnvVar('DB_PASS', '');
$charset = getEnvVar('DB_CHARSET', 'utf8mb4');

// Build DSN (Data Source Name)
// Include port in DSN for proper connection
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Show errors as exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                   // Use native prepared statements
    PDO::ATTR_PERSISTENT         => false,                   // Don't use persistent connections
];

/**
 * Get PDO database connection
 * @return PDO Returns PDO connection object
 * @throws PDOException If connection fails
 */
function getPDOConnection() {
    global $dsn, $user, $pass, $options;
    
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (\PDOException $e) {
        // Log error for debugging (don't expose to users)
        error_log("PDO Connection failed: " . $e->getMessage());
        
        // Re-throw with sanitized message for production
        throw new \PDOException(
            "Database connection failed. Please contact administrator.",
            (int)$e->getCode()
        );
    }
}

/**
 * Test PDO database connection
 * @return bool Returns true if connection successful, false otherwise
 */
function testPDOConnection() {
    try {
        $pdo = getPDOConnection();
        // Test query
        $pdo->query("SELECT 1");
        return true;
    } catch (\PDOException $e) {
        error_log("PDO Connection test failed: " . $e->getMessage());
        return false;
    }
}

?>

