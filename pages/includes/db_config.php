<?php
/**
 * Database Configuration File
 * PNG Maritime College - Student Management System
 * 
 * Update these values according to your MySQL server configuration
 */

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_PORT', 3307);  // Change this if your MySQL server uses a different port
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sms2_db');
define('DB_CHARSET', 'utf8mb4');

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

