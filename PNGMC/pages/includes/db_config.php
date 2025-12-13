<?php
/**
 * Database Configuration File
 * PNG Maritime College - Student Management System
 * 
 * Database settings can be configured via environment variables (.env file)
 * or by modifying the default values below.
 */

// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Database connection settings
// Priority: 1. Environment variable (.env), 2. System environment, 3. Default value
// Auto-detect port: 3307 for Windows/XAMPP, 3306 for Linux
$default_port = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? '3307' : '3306';

define('DB_HOST', getEnvVar('DB_HOST', 'localhost'));
define('DB_PORT', (int)getEnvVar('DB_PORT', $default_port));  // Auto-detects: 3307 for Windows, 3306 for Linux
define('DB_USER', getEnvVar('DB_USER', 'root'));
define('DB_PASS', getEnvVar('DB_PASS', ''));
define('DB_NAME', getEnvVar('DB_NAME', 'sms2_db'));
define('DB_CHARSET', getEnvVar('DB_CHARSET', 'utf8mb4'));

/**
 * Create database connection
 * @return mysqli|false Returns mysqli connection object or false on failure
 */
function getDBConnection() {
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        return false;
    }
    
    // Set charset
    $conn->set_charset(DB_CHARSET);
    
    return $conn;
}

/**
 * Test database connection
 * @return bool Returns true if connection successful, false otherwise
 */
function testDBConnection() {
    $conn = getDBConnection();
    if ($conn) {
        $conn->close();
        return true;
    }
    return false;
}

?>

