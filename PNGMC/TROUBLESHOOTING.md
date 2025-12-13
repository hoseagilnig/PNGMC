# Troubleshooting Guide - MySQL Connection Issues

## Error: "No connection could be made because the target machine actively refused it"

This error means **MySQL server is not running** or not accessible.

## Solution 1: Start MySQL Server

### If using XAMPP:
1. Open **XAMPP Control Panel**
2. Find **MySQL** in the list
3. Click **Start** button next to MySQL
4. Wait until it shows "Running" (green)

### If using WAMP:
1. Open **WAMP Server**
2. Click the WAMP icon in system tray
3. Go to **MySQL** → **Service** → **Start/Resume Service**

### If using MAMP:
1. Open **MAMP**
2. Click **Start Servers**
3. Wait for MySQL to start

### If MySQL is installed separately:
1. Open **Services** (Windows Key + R, type `services.msc`)
2. Find **MySQL** or **MySQL80** service
3. Right-click → **Start**

### If using MySQL Command Line:
```bash
# Windows (if MySQL is in PATH)
net start MySQL80

# Or check MySQL service name first
sc query | findstr MySQL
```

## Solution 2: Check MySQL Port

If MySQL is running on a different port (like 3307 instead of 3306):

1. Edit `pages/includes/db_config.php`
2. Change the port:
   ```php
   define('DB_PORT', 3307);  // Change to your MySQL port
   ```

To find your MySQL port:
- Check XAMPP/WAMP/MAMP settings
- Or check MySQL configuration file (my.ini or my.cnf)
- Or check phpMyAdmin - it shows the port in the connection string

## Solution 3: Verify MySQL is Running

### Check via Command Line:
```bash
# Windows
netstat -an | findstr 3306

# If you see "LISTENING" on port 3306, MySQL is running
```

### Check via phpMyAdmin:
1. Try opening: `http://localhost/phpmyadmin`
2. If it connects, MySQL is running
3. If it doesn't connect, MySQL is not running

## Solution 4: Check Database Credentials

Edit `pages/includes/db_config.php` and verify:

```php
define('DB_HOST', 'localhost');  // Usually 'localhost' or '127.0.0.1'
define('DB_USER', 'root');        // Your MySQL username
define('DB_PASS', '');            // Your MySQL password (empty if no password)
define('DB_NAME', 'sms2_db');     // Make sure this database exists
```

**Common issues:**
- If you set a MySQL root password, update `DB_PASS`
- If database name is different, update `DB_NAME`
- If using a remote server, update `DB_HOST` to the server IP

## Solution 5: Test Connection

After starting MySQL, test the connection:

1. Open: `http://localhost/sms2/database/test_connection.php`
2. You should see: ✓ Database connection successful!

## Quick Checklist

- [ ] MySQL service is started (check XAMPP/WAMP/MAMP)
- [ ] MySQL port is correct (usually 3306)
- [ ] Database credentials are correct in `db_config.php`
- [ ] Database `sms2_db` has been imported
- [ ] No firewall blocking MySQL port

## Still Having Issues?

1. **Check MySQL Error Log:**
   - XAMPP: `C:\xampp\mysql\data\mysql_error.log`
   - WAMP: `C:\wamp\logs\mysql.log`
   - Look for any error messages

2. **Try connecting via command line:**
   ```bash
   mysql -u root -p
   ```
   If this works, MySQL is running but PHP might have connection issues.

3. **Check PHP MySQL Extension:**
   - Make sure `mysqli` extension is enabled in PHP
   - Check `phpinfo()` to verify

4. **Restart your web server:**
   - Sometimes restarting Apache/PHP helps

## Common MySQL Ports

- **3306** - Default MySQL port
- **3307** - Alternative port (some installations)
- **3308** - Another alternative

If you're not sure, check your MySQL configuration or phpMyAdmin connection string.

