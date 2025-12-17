<?php
/**
 * Secure Logout
 * Properly destroys session and clears all session data
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log logout event if user was logged in
if (isset($_SESSION['user_id']) && function_exists('logSecurityEvent')) {
    require_once __DIR__ . '/includes/security_helper.php';
    logSecurityEvent('LOGOUT', 'User: ' . ($_SESSION['username'] ?? 'unknown'));
}

// Unset all session variables
$_SESSION = [];

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params['path'], 
        $params['domain'], 
        $params['secure'], 
        $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Start a new session to prevent session fixation
session_start();
session_regenerate_id(true);

// Redirect to login page
header('Location: login.php');
exit;
?>
