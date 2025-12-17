<?php
/**
 * Authentication Guard System
 * Standardized authentication and authorization for all pages
 * 
 * Usage:
 * require_once __DIR__ . '/auth_guard.php';
 * requireRole('admin'); // or ['admin', 'hod']
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize secure session on first load
if (!isset($_SESSION['_auth_initialized'])) {
    require_once __DIR__ . '/security_helper.php';
    initSecureSession();
    $_SESSION['_auth_initialized'] = true;
}

/**
 * Check if user is authenticated
 * @return bool
 */
function isAuthenticated() {
    return isset($_SESSION['loggedin']) && 
           $_SESSION['loggedin'] === true && 
           isset($_SESSION['user_id']) &&
           isset($_SESSION['role']);
}

/**
 * Get current user role (normalized to lowercase)
 * @return string|null
 */
function getCurrentRole() {
    if (!isAuthenticated()) {
        return null;
    }
    // Normalize role to lowercase
    return isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : null;
}

/**
 * Check if user has required role(s)
 * @param string|array $required_roles Role(s) to check
 * @return bool
 */
function hasRole($required_roles) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $user_role = getCurrentRole();
    if (!$user_role) {
        return false;
    }
    
    if (!is_array($required_roles)) {
        $required_roles = [$required_roles];
    }
    
    // Normalize all roles to lowercase
    $required_roles = array_map('strtolower', $required_roles);
    
    // Handle aliases
    $role_map = [
        'sas' => 'studentservices',
        'student_services' => 'studentservices',
        'student services' => 'studentservices'
    ];
    
    if (isset($role_map[$user_role])) {
        $user_role = $role_map[$user_role];
    }
    
    foreach ($required_roles as $role) {
        if (isset($role_map[$role])) {
            $role = $role_map[$role];
        }
        if ($user_role === strtolower($role)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Require authentication (redirect if not logged in)
 * @param string $redirect_url Optional redirect URL
 */
function requireAuth($redirect_url = 'login.php') {
    if (!isAuthenticated()) {
        // Store intended destination
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Require specific role(s) (redirect if not authorized)
 * @param string|array $required_roles Role(s) required
 * @param string $redirect_url Optional redirect URL
 */
function requireRole($required_roles, $redirect_url = 'login.php') {
    requireAuth($redirect_url);
    
    if (!hasRole($required_roles)) {
        // Log unauthorized access attempt
        if (function_exists('logSecurityEvent')) {
            logSecurityEvent('UNAUTHORIZED_ACCESS', 
                'User: ' . ($_SESSION['user_id'] ?? 'unknown') . 
                ' Role: ' . getCurrentRole() . 
                ' Required: ' . (is_array($required_roles) ? implode(',', $required_roles) : $required_roles));
        }
        
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current username
 * @return string|null
 */
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Get current user name
 * @return string|null
 */
function getCurrentUserName() {
    return $_SESSION['name'] ?? null;
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if user is HOD
 * @return bool
 */
function isHOD() {
    return hasRole('hod');
}

/**
 * Check if user is Finance
 * @return bool
 */
function isFinance() {
    return hasRole('finance');
}

/**
 * Check if user is Student Services
 * @return bool
 */
function isStudentServices() {
    return hasRole('studentservices');
}

/**
 * Check if user is Student
 * @return bool
 */
function isStudent() {
    return hasRole('student');
}

?>

