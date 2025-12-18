<?php
/**
 * Security Helper Functions
 * Common security functions for the application
 */

/**
 * Initialize secure session
 */
function initSecureSession() {
    // Set secure session parameters
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Strict');
    
    // Only set secure cookie if using HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', '1');
    }
    
    // Set session timeout (30 minutes)
    ini_set('session.gc_maxlifetime', '1800');
}

/**
 * Regenerate session ID after login (prevent session fixation)
 */
function regenerateSessionId() {
    session_regenerate_id(true);
}

/**
 * Sanitize input for display
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate integer input
 */
function validateInt($value, $min = null, $max = null) {
    $int = filter_var($value, FILTER_VALIDATE_INT);
    if ($int === false) {
        return false;
    }
    if ($min !== null && $int < $min) {
        return false;
    }
    if ($max !== null && $int > $max) {
        return false;
    }
    return $int;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate CSRF token as hidden input field (for forms)
 */
function generateCSRFTokenInput() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check rate limit for login attempts
 */
function checkLoginRateLimit($username, $max_attempts = 5, $time_window = 300) {
    $key = 'login_attempts_' . md5($username);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
        return true;
    }
    
    $attempts = $_SESSION[$key];
    
    // Reset if time window has passed
    if (time() - $attempts['time'] > $time_window) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
        return true;
    }
    
    // Check if max attempts exceeded
    if ($attempts['count'] >= $max_attempts) {
        return false;
    }
    
    return true;
}

/**
 * Record failed login attempt
 */
function recordFailedLogin($username) {
    $key = 'login_attempts_' . md5($username);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'time' => time()];
    } else {
        $_SESSION[$key]['count']++;
    }
}

/**
 * Clear login attempts (on successful login)
 */
function clearLoginAttempts($username) {
    $key = 'login_attempts_' . md5($username);
    unset($_SESSION[$key]);
}

/**
 * Get remaining login attempts
 */
function getRemainingLoginAttempts($username, $max_attempts = 5) {
    $key = 'login_attempts_' . md5($username);
    
    if (!isset($_SESSION[$key])) {
        return $max_attempts;
    }
    
    $attempts = $_SESSION[$key];
    return max(0, $max_attempts - $attempts['count']);
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowed_types = [], $max_size = 5242880) {
    $errors = [];
    
    // Check if file was uploaded
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['valid' => false, 'error' => 'No file uploaded'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload error: ' . $file['error']];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'File size exceeds maximum allowed size'];
    }
    
    // Check file type
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = array_map('strtolower', $allowed_types);
    
    if (!empty($allowed_extensions) && !in_array($file_ext, $allowed_extensions)) {
        return ['valid' => false, 'error' => 'File type not allowed'];
    }
    
    // Additional security: Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png'
    ];
    
    if (isset($allowed_mimes[$file_ext]) && $mime_type !== $allowed_mimes[$file_ext]) {
        return ['valid' => false, 'error' => 'File MIME type does not match extension'];
    }
    
    return ['valid' => true];
}

/**
 * Secure file name for storage
 */
function secureFileName($filename) {
    // Remove any path components
    $filename = basename($filename);
    
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // Limit length
    if (strlen($filename) > 255) {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $filename = substr($name, 0, 255 - strlen($ext) - 1) . '.' . $ext;
    }
    
    return $filename;
}

/**
 * Check if user is authenticated
 * Note: This function may be overridden by auth_guard.php
 */
if (!function_exists('isAuthenticated')) {
    function isAuthenticated() {
        return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
    }
}

/**
 * Require authentication (redirect if not logged in)
 * Note: This function may be overridden by auth_guard.php
 */
if (!function_exists('requireAuth')) {
    function requireAuth() {
        if (!isAuthenticated()) {
            header('Location: login.php');
            exit;
        }
    }
}

/**
 * Check if user has required role
 * Note: This function may be overridden by auth_guard.php
 */
if (!function_exists('requireRole')) {
    function requireRole($required_roles) {
        requireAuth();
        
        if (!is_array($required_roles)) {
            $required_roles = [$required_roles];
        }
        
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $required_roles)) {
            header('Location: ../index.html');
            exit;
        }
    }
}

/**
 * Log security event
 */
function logSecurityEvent($event_type, $details = '') {
    $log_file = __DIR__ . '/../../logs/security.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $user_id = $_SESSION['user_id'] ?? 'anonymous';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $log_entry = "[$timestamp] [$ip] [User: $user_id] [$event_type] $details\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

?>

