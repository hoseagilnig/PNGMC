# Linux Deployment Fix - Database Port Configuration

## ✅ Fixed: Database Port Configuration

**Issue:** Database port was hardcoded to `3307` (XAMPP/Windows default), which doesn't work on Linux (standard MySQL port is `3306`).

**Solution:** Made database configuration environment-based with Linux-friendly defaults.

---

## Changes Made

### 1. Updated `pages/includes/db_config.php`

**Before:**
```php
define('DB_PORT', 3307);  // Hardcoded Windows/XAMPP port
```

**After:**
```php
// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Default: 3306 (standard MySQL port for Linux)
define('DB_PORT', (int)getEnvVar('DB_PORT', '3306'));
```

**Benefits:**
- ✅ Default port is now `3306` (Linux standard)
- ✅ Can be overridden via `.env` file
- ✅ Works on both Linux and Windows without code changes
- ✅ All database settings now use environment variables

### 2. Created `.env.example` File

Created comprehensive environment variable template with:
- Database configuration (host, port, user, password, name)
- AI chatbot API keys
- Chatbot settings
- Clear documentation

### 3. Updated Documentation

- Updated `PRODUCTION_SETUP_COMPLETE.md`
- Updated `TECHNICAL_SPECIFICATIONS.md`
- Added database port configuration instructions

---

## How to Use

### For Linux Deployment (Default)

The system now defaults to port `3306` (Linux standard). No changes needed if using standard MySQL setup.

**In `.env` file (optional):**
```env
DB_HOST=localhost
DB_PORT=3306
DB_USER=your_db_user
DB_PASS=your_password
DB_NAME=sms2_db
```

### For Windows/XAMPP

If deploying on Windows with XAMPP, simply set the port in `.env`:

**In `.env` file:**
```env
DB_PORT=3307
```

---

## Configuration Priority

The system now uses this priority order:

1. **Environment variable from `.env` file** (highest priority)
2. **System environment variable**
3. **Default value** (fallback)

**Example:**
```php
// Checks .env file first, then system env, then uses default
define('DB_PORT', (int)getEnvVar('DB_PORT', '3306'));
```

---

## Testing

### Test Database Connection

1. Create `.env` file from `.env.example`:
   ```bash
   cp .env.example .env
   ```

2. Update `.env` with your database settings:
   ```env
   DB_HOST=localhost
   DB_PORT=3306  # or 3307 for XAMPP
   DB_USER=root
   DB_PASS=your_password
   DB_NAME=sms2_db
   ```

3. Test the connection by accessing any page that uses the database.

---

## Migration Notes

### For Existing Installations

If you have an existing installation:

1. **Create `.env` file** from `.env.example`
2. **Add your current database settings** to `.env`
3. **Remove hardcoded values** from `db_config.php` (already done)
4. **Test** the connection

The system will automatically use values from `.env` if present, or fall back to defaults.

---

## Benefits

✅ **Cross-Platform:** Works on Linux, Windows, and macOS  
✅ **Flexible:** Easy to change without editing code  
✅ **Secure:** Database credentials in `.env` (not in code)  
✅ **Production-Ready:** Standard Linux port (3306) as default  
✅ **Backward Compatible:** Still works with existing setups  

---

## Status

✅ **Fixed and Ready for Linux Deployment**

The database port configuration is now flexible and Linux-ready. The system will work on Linux without any code changes - just configure the `.env` file.

---

**Date:** January 2025  
**Status:** ✅ Complete

