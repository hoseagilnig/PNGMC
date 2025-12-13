# Quick Fix Guide - Critical Security Issues

## 🔴 IMMEDIATE FIXES REQUIRED (Before Deployment)

### 1. Fix SQL Injection in `pages/applications.php`

**Lines to fix:**
- Line 37: Direct query with `$application_id`
- Line 52: Direct query with `$application_id`  
- Line 77: Direct query with `$application_id`
- Line 99: Direct query with `$application_id`
- Line 174-176: Direct queries with `$application_id`
- Line 190: Direct DELETE query
- Line 203: Direct query with `$application_id`
- Line 230: Direct query with `$application_id`
- Line 280: Direct query with `$application_id` and `$student_id`
- Line 300: Direct query with `$application_id`

**Fix Pattern:**
Replace:
```php
$conn->query("SELECT ... WHERE application_id = $application_id");
```

With:
```php
$stmt = $conn->prepare("SELECT ... WHERE application_id = ?");
$stmt->bind_param("i", $application_id);
$stmt->execute();
$result = $stmt->get_result();
```

### 2. Secure API Key

**File:** `pages/includes/chatbot_config.php`

**Change line 23:**
```php
// FROM:
define('GEMINI_API_KEY', 'AIzaSyD2DHnfmijjr2dn85B2S2VgBzLVUo8oPWY');

// TO:
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
```

**Then set environment variable on server:**
```bash
export GEMINI_API_KEY="your-actual-key-here"
```

### 3. Disable Debug Mode

**File:** `pages/includes/chatbot_config.php`

**Change line 48:**
```php
// FROM:
define('CHATBOT_DEBUG', true);

// TO:
define('CHATBOT_DEBUG', false);
```

### 4. Update Database Credentials

**File:** `pages/includes/db_config.php`

**Change:**
```php
// FROM:
define('DB_USER', 'root');
define('DB_PASS', '');

// TO: (Create dedicated user)
define('DB_USER', 'sms2_user');
define('DB_PASS', 'strong_random_password_here');
```

**Create database user:**
```sql
CREATE USER 'sms2_user'@'localhost' IDENTIFIED BY 'strong_random_password_here';
GRANT SELECT, INSERT, UPDATE, DELETE ON sms2_db.* TO 'sms2_user'@'localhost';
FLUSH PRIVILEGES;
```

### 5. Add Production Error Handling

**Add to top of all PHP files (or create bootstrap file):**
```php
// Production error handling
if (!defined('DEVELOPMENT_MODE')) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/logs/php_errors.log');
}
```

### 6. Add Session Security

**Add to `pages/login.php` after successful login:**
```php
// Regenerate session ID to prevent fixation
session_regenerate_id(true);

// Set secure session parameters
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1'); // Only if using HTTPS
ini_set('session.use_strict_mode', '1');
```

### 7. Update .htaccess

**Copy `.htaccess_production` to `.htaccess`** or merge security headers.

### 8. Remove Debug Files

**Delete or move to secure location:**
- `database/test_connection.php`
- `pages/test_*.php`
- `pages/debug_*.php`
- `test_student_login.php`

### 9. Change Default Passwords

**Run password update script and change all default passwords:**
```bash
php database/update_passwords.php
```

Then change passwords in database for all users.

### 10. File Permissions

**Set correct permissions:**
```bash
# Directories
find . -type d -exec chmod 755 {} \;

# Files
find . -type f -exec chmod 644 {} \;

# Upload directories (writable but not executable)
chmod 755 uploads/
chmod 755 uploads/school_leaver_documents/
```

## ⚠️ HIGH PRIORITY (Fix Soon)

1. Add CSRF tokens to all forms
2. Implement rate limiting for login
3. Add file content validation (not just MIME type)
4. Set up automated database backups
5. Remove console.log statements from JavaScript

## ✅ TESTING CHECKLIST

After fixes:
- [ ] Test all login flows
- [ ] Test application submission
- [ ] Test file uploads
- [ ] Test all dashboard pages
- [ ] Verify no errors displayed to users
- [ ] Check error logs are working
- [ ] Test on production-like environment

