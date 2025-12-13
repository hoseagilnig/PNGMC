<?php
/**
 * Bootstrap File for Production
 * Include this at the top of all PHP files for production security
 * 
 * Usage: require_once __DIR__ . '/includes/bootstrap.php';
 */

// Start session with security settings
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters before starting
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Strict');
    
    // Only set secure cookie if using HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', '1');
    }
    
    // Set session timeout (30 minutes)
    ini_set('session.gc_maxlifetime', '1800');
    
    session_start();
}

// Production error handling (only if not in development mode)
if (!defined('DEVELOPMENT_MODE') || !DEVELOPMENT_MODE) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    
    // Create logs directory if it doesn't exist
    $log_dir = __DIR__ . '/../../logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    ini_set('error_log', $log_dir . '/php_errors.log');
    
    // Prevent information disclosure
    ini_set('expose_php', '0');
}

// Include security helper
require_once __DIR__ . '/security_helper.php';

// Include database config
require_once __DIR__ . '/db_config.php';

?>

