# Secret Keys Documentation

## Overview

This document lists all secret keys and sensitive information that were previously hardcoded in the code and have been moved to secure environment variables (`.env` file) that only the server can read.

---

## 🔑 Secret Keys Moved to Environment Variables

### 1. **AI Chatbot API Keys**

These keys were previously hardcoded in `pages/includes/chatbot_config.php` and could be exposed if the code was publicly accessible.

#### **GEMINI_API_KEY** (Google Gemini)
- **Previous Location:** Hardcoded in `chatbot_config.php`
- **Current Location:** `.env` file (environment variable)
- **Purpose:** Used for Google Gemini AI chatbot functionality
- **Security Risk:** If exposed, attackers could use your API key and incur charges
- **Status:** ✅ Moved to environment variables

#### **OPENAI_API_KEY** (OpenAI GPT)
- **Previous Location:** Hardcoded in `chatbot_config.php`
- **Current Location:** `.env` file (environment variable)
- **Purpose:** Used for OpenAI GPT chatbot functionality
- **Format:** Starts with `sk-`
- **Security Risk:** If exposed, attackers could use your API key and incur charges
- **Status:** ✅ Moved to environment variables

#### **ANTHROPIC_API_KEY** (Anthropic Claude)
- **Previous Location:** Hardcoded in `chatbot_config.php`
- **Current Location:** `.env` file (environment variable)
- **Purpose:** Used for Anthropic Claude AI chatbot functionality
- **Security Risk:** If exposed, attackers could use your API key and incur charges
- **Status:** ✅ Moved to environment variables

#### **CUSTOM_API_KEY** (Custom API)
- **Previous Location:** Hardcoded in `chatbot_config.php`
- **Current Location:** `.env` file (environment variable)
- **Purpose:** Used for custom AI API integration
- **Security Risk:** If exposed, attackers could access your custom API
- **Status:** ✅ Moved to environment variables

#### **CUSTOM_API_URL** (Custom API Endpoint)
- **Previous Location:** Hardcoded in `chatbot_config.php`
- **Current Location:** `.env` file (environment variable)
- **Purpose:** URL endpoint for custom AI API
- **Security Risk:** If exposed, reveals your API infrastructure
- **Status:** ✅ Moved to environment variables

---

### 2. **Database Credentials**

These credentials were previously hardcoded in `pages/includes/db_config.php` and could allow unauthorized database access if exposed.

#### **DB_HOST** (Database Host)
- **Previous Location:** Hardcoded in `db_config.php`
- **Current Location:** `.env` file (environment variable)
- **Default Value:** `localhost`
- **Security Risk:** Reveals database server location
- **Status:** ✅ Moved to environment variables

#### **DB_PORT** (Database Port)
- **Previous Location:** Hardcoded in `db_config.php`
- **Current Location:** `.env` file (environment variable)
- **Default Value:** `3307` (Windows/XAMPP) or `3306` (Linux)
- **Security Risk:** Reveals database port configuration
- **Status:** ✅ Moved to environment variables

#### **DB_USER** (Database Username)
- **Previous Location:** Hardcoded in `db_config.php` (was `root`)
- **Current Location:** `.env` file (environment variable)
- **Security Risk:** If exposed with password, allows full database access
- **Status:** ✅ Moved to environment variables
- **Recommendation:** Use dedicated database user (not `root`)

#### **DB_PASS** (Database Password)
- **Previous Location:** Hardcoded in `db_config.php` (was empty `''`)
- **Current Location:** `.env` file (environment variable)
- **Security Risk:** If exposed, allows unauthorized database access
- **Status:** ✅ Moved to environment variables
- **Recommendation:** Use strong, unique password

#### **DB_NAME** (Database Name)
- **Previous Location:** Hardcoded in `db_config.php`
- **Current Location:** `.env` file (environment variable)
- **Default Value:** `sms2_db`
- **Security Risk:** Reveals database structure
- **Status:** ✅ Moved to environment variables

#### **DB_CHARSET** (Database Character Set)
- **Previous Location:** Hardcoded in `db_config.php`
- **Current Location:** `.env` file (environment variable)
- **Default Value:** `utf8mb4`
- **Security Risk:** Low (configuration detail)
- **Status:** ✅ Moved to environment variables

---

### 3. **Configuration Settings**

#### **CHATBOT_DEBUG** (Debug Mode)
- **Previous Location:** Hardcoded in `chatbot_config.php`
- **Current Location:** `.env` file (environment variable)
- **Purpose:** Controls whether debug information is shown
- **Security Risk:** If enabled in production, exposes system errors and internal details
- **Status:** ✅ Moved to environment variables
- **Recommendation:** Set to `false` in production

---

## 📁 Where These Keys Are Now Stored

### Environment File (`.env`)
All secret keys are now stored in a `.env` file in the project root directory. This file:
- ✅ Is **NOT** committed to version control (Git)
- ✅ Is **NOT** accessible via web browser
- ✅ Is **ONLY** readable by the server
- ✅ Contains all sensitive configuration

### Example `.env` File Structure:
```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3307
DB_USER=sms_user
DB_PASS=your_secure_password_here
DB_NAME=sms2_db
DB_CHARSET=utf8mb4

# AI Chatbot API Keys
GEMINI_API_KEY=your_gemini_api_key_here
OPENAI_API_KEY=sk-your_openai_api_key_here
ANTHROPIC_API_KEY=your_anthropic_api_key_here
CUSTOM_API_URL=https://your-custom-api.com
CUSTOM_API_KEY=your_custom_api_key_here

# Debug Settings
CHATBOT_DEBUG=false
```

---

## 🔒 Security Improvements

### Before (Insecure):
```php
// ❌ BAD: Hardcoded in code
define('GEMINI_API_KEY', 'AIzaSyBxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('DB_USER', 'root');
define('DB_PASS', 'password123');
```
- **Risk:** Anyone with access to code can see these keys
- **Risk:** Keys are committed to version control
- **Risk:** Keys are visible in error messages
- **Risk:** Keys can be stolen if code is leaked

### After (Secure):
```php
// ✅ GOOD: Loaded from environment variables
define('GEMINI_API_KEY', getEnvVar('GEMINI_API_KEY', ''));
define('DB_USER', getEnvVar('DB_USER', 'root'));
define('DB_PASS', getEnvVar('DB_PASS', ''));
```
- **Safe:** Keys are stored in `.env` file (not in code)
- **Safe:** `.env` file is excluded from version control
- **Safe:** Keys are only accessible to the server
- **Safe:** Keys cannot be accidentally exposed

---

## 🛡️ Protection Measures

### 1. **`.gitignore` Protection**
The `.env` file is automatically excluded from Git:
```
.env
.env.local
.env.production
```

### 2. **Web Server Protection**
The `.env` file is protected from web access via `.htaccess`:
```apache
# Prevent access to .env files
<FilesMatch "^\.env">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 3. **Environment Variable Loader**
Created `pages/includes/env_loader.php` that:
- Safely loads variables from `.env` file
- Provides fallback values
- Logs warnings (not errors) if keys are missing
- Never exposes keys in error messages

---

## 📋 Checklist: Verify Keys Are Secure

- [x] All API keys moved to `.env` file
- [x] Database credentials moved to `.env` file
- [x] `.env` file added to `.gitignore`
- [x] `.htaccess` protects `.env` from web access
- [x] Code uses `getEnvVar()` function instead of hardcoded values
- [x] No API keys visible in source code
- [x] No database passwords in source code
- [x] Debug mode set to `false` in production

---

## ⚠️ Important Security Notes

1. **Never commit `.env` to Git**
   - The `.env` file contains all your secrets
   - It's already in `.gitignore`, but double-check before committing

2. **Use Strong Passwords**
   - Database passwords should be at least 16 characters
   - Use a mix of letters, numbers, and symbols
   - Don't reuse passwords from other systems

3. **Rotate Keys Regularly**
   - Change API keys every 3-6 months
   - Change database passwords if compromised
   - Revoke old keys when creating new ones

4. **Limit API Key Permissions**
   - Use API keys with minimal required permissions
   - Don't use admin/root keys for application access
   - Create dedicated keys for each service

5. **Monitor API Usage**
   - Check API usage logs regularly
   - Set up alerts for unusual activity
   - Monitor for unexpected charges

6. **Backup `.env` Securely**
   - Keep encrypted backups of `.env` file
   - Store backups in secure location
   - Don't share `.env` files via email or chat

---

## 🔍 How to Verify Keys Are Not Exposed

### Check Source Code:
```bash
# Search for hardcoded API keys (should return no results)
grep -r "AIzaSy" pages/
grep -r "sk-" pages/
grep -r "DB_PASS.*=" pages/includes/db_config.php
```

### Check Git History:
```bash
# Check if .env was ever committed (should not be)
git log --all --full-history -- .env
```

### Check Web Access:
```bash
# Try to access .env via browser (should be blocked)
# Visit: http://localhost/sms2/.env
# Should return: 403 Forbidden or 404 Not Found
```

---

## 📞 Support

If you suspect a key has been compromised:
1. **Immediately revoke the compromised key**
2. **Generate a new key** from the provider's dashboard
3. **Update the `.env` file** with the new key
4. **Review access logs** for unauthorized usage
5. **Change all related passwords** as a precaution

---

## Summary

**Total Secret Keys Secured:** 11
- 5 AI API Keys (GEMINI, OPENAI, ANTHROPIC, CUSTOM_API_KEY, CUSTOM_API_URL)
- 5 Database Credentials (HOST, PORT, USER, PASS, NAME, CHARSET)
- 1 Configuration Setting (CHATBOT_DEBUG)

**Security Status:** ✅ All keys moved from code to secure environment variables

**Protection Level:** High - Keys are now only accessible to the server, not exposed in code or version control.

