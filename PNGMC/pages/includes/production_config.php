<?php
/**
 * Production Configuration
 * Include this file in production to override development settings
 * 
 * Usage: Add this at the top of your main entry points:
 * require_once __DIR__ . '/includes/production_config.php';
 */

// Disable error display in production
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Security: Prevent information disclosure
ini_set('expose_php', '0');

// Session security settings
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1'); // Enable only if using HTTPS
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Strict');

// Set session timeout (30 minutes)
ini_set('session.gc_maxlifetime', '1800');

// Disable chatbot debug mode
if (file_exists(__DIR__ . '/chatbot_config.php')) {
    // This will be overridden when chatbot_config.php is included
    // But we set it here as a reminder
    if (!defined('CHATBOT_DEBUG')) {
        define('CHATBOT_DEBUG', false);
    }
}

?>

