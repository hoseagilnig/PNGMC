<?php
/**
 * Production Configuration
 * Set production-ready PHP settings
 * Include this file at the very top of your entry points
 * 
 * Usage:
 * require_once __DIR__ . '/includes/production_config.php';
 */

// Only apply in production environment
$app_env = getenv('APP_ENV') ?: (defined('APP_ENV') ? APP_ENV : 'production');

if ($app_env === 'production' || $app_env === 'prod') {
    // Disable error display in production
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    
    // Enable error logging
    ini_set('log_errors', '1');
    $log_dir = __DIR__ . '/../../logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    ini_set('error_log', $log_dir . '/php_errors.log');
    
    // Set error reporting level (log all errors but don't display)
    error_reporting(E_ALL);
    
    // Hide PHP version
    header_remove('X-Powered-By');
    
    // Security headers
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
    }
} else {
    // Development mode - show errors
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// Set timezone
date_default_timezone_set('Pacific/Port_Moresby');

// Set session security (if not already set)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Strict');
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', '1');
    }
}

?>
