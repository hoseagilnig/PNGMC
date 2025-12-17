# PNGMC PHP System - Ubuntu 22.04 Deployment Guide
## Complete Setup for Apache2 + PHP 8.1 + MySQL

---

## 🎯 **System Overview**

**Application:** PNG Maritime College Student Management System  
**Type:** PHP Web Application (Plain PHP, not Laravel)  
**Database:** MySQL/MariaDB  
**Web Server:** Apache2  
**PHP Version:** 8.1  
**OS:** Ubuntu 22.04 LTS  

---

## 📋 **Prerequisites**

- Ubuntu 22.04 LTS server
- RDP access configured
- Root or sudo access
- Internet connection

---

## 🚀 **STEP 1: Install Required Software**

### 1.1 Update System

```bash
sudo apt update
sudo apt upgrade -y
```

### 1.2 Install Apache2

```bash
sudo apt install -y apache2
sudo systemctl enable apache2
sudo systemctl start apache2
```

**Verify Apache is running:**
```bash
sudo systemctl status apache2
```

### 1.3 Install PHP 8.1 and Extensions

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.1 and required extensions
sudo apt install -y php8.1 php8.1-cli php8.1-fpm php8.1-mysql php8.1-mbstring \
    php8.1-xml php8.1-curl php8.1-zip php8.1-gd php8.1-bcmath php8.1-intl \
    php8.1-opcache php8.1-readline

# Enable Apache PHP module
sudo a2enmod php8.1
```

**Verify PHP installation:**
```bash
php -v
# Should show: PHP 8.1.x
```

### 1.4 Install MySQL/MariaDB

```bash
sudo apt install -y mysql-server
sudo systemctl enable mysql
sudo systemctl start mysql
```

**Secure MySQL installation:**
```bash
sudo mysql_secure_installation
```

**Follow prompts:**
- Set root password? **Yes** (choose a strong password)
- Remove anonymous users? **Yes**
- Disallow root login remotely? **Yes**
- Remove test database? **Yes**
- Reload privilege tables? **Yes**

### 1.5 Enable Required Apache Modules

```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod ssl
sudo systemctl restart apache2
```

---

## 📁 **STEP 2: Copy PNGMC Files to Server**

### Option A: Via RDP (Easiest)

1. **On Windows:**
   - Navigate to: `C:\xampp\htdocs\sms2\PNGMC\`
   - Select all files and folders
   - Copy (Ctrl+C)

2. **On Ubuntu (via RDP):**
   - Open file manager
   - Navigate to: `/var/www/`
   - Create folder: `pngmc` (or `sms2`)
   - Paste files (Ctrl+V)

### Option B: Via SCP (From Windows PowerShell)

```powershell
# From Windows PowerShell
cd C:\xampp\htdocs\sms2
scp -r PNGMC ict@your-server-ip:/home/ict/
```

**Then on Ubuntu:**
```bash
sudo mv /home/ict/PNGMC /var/www/pngmc
```

### Option C: Create ZIP and Transfer

**On Windows:**
```powershell
# Create ZIP of PNGMC folder
Compress-Archive -Path "C:\xampp\htdocs\sms2\PNGMC\*" -DestinationPath "PNGMC.zip"
```

**Copy ZIP to Ubuntu, then:**
```bash
cd /var/www
sudo unzip PNGMC.zip -d pngmc
```

---

## ⚙️ **STEP 3: Configure Apache Virtual Host**

### 3.1 Create Apache Configuration

```bash
sudo nano /etc/apache2/sites-available/pngmc.conf
```

**Paste this configuration:**

```apache
<VirtualHost *:80>
    ServerName your-server-ip
    # Or use: ServerName your-domain.com (when you have domain)
    
    DocumentRoot /var/www/pngmc
    
    <Directory /var/www/pngmc>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Error and access logs
    ErrorLog ${APACHE_LOG_DIR}/pngmc_error.log
    CustomLog ${APACHE_LOG_DIR}/pngmc_access.log combined
    
    # PHP configuration
    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>
</VirtualHost>
```

**Save and exit:** `Ctrl+X`, then `Y`, then `Enter`

### 3.2 Enable Site and Disable Default

```bash
# Enable PNGMC site
sudo a2ensite pngmc.conf

# Disable default Apache site
sudo a2dissite 000-default.conf

# Test Apache configuration
sudo apache2ctl configtest

# If OK, reload Apache
sudo systemctl reload apache2
```

---

## 🗄️ **STEP 4: Setup Database**

### 4.1 Create Database

```bash
sudo mysql -u root -p
```

**In MySQL, run:**

```sql
-- Create database
CREATE DATABASE sms2_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create database user (optional, or use root)
CREATE USER 'sms2_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';

-- Grant privileges
GRANT ALL PRIVILEGES ON sms2_db.* TO 'sms2_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

### 4.2 Import Database Schema

**Option A: Via Command Line**

```bash
cd /var/www/pngmc/database
sudo mysql -u root -p sms2_db < sms2_database.sql
sudo mysql -u root -p sms2_db < application_workflow_tables.sql
```

**Option B: Via phpMyAdmin (if installed)**

1. Install phpMyAdmin:
```bash
sudo apt install -y phpmyadmin
```

2. Access: `http://your-server-ip/phpmyadmin`
3. Select `sms2_db` database
4. Click "Import" tab
5. Upload `sms2_database.sql`, then `application_workflow_tables.sql`

---

## 🔧 **STEP 5: Configure Application**

### 5.1 Set File Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/pngmc

# Set directory permissions
sudo find /var/www/pngmc -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/pngmc -type f -exec chmod 644 {} \;

# Make uploads directory writable
sudo chmod -R 775 /var/www/pngmc/uploads
sudo chown -R www-data:www-data /var/www/pngmc/uploads

# Make logs directory writable
sudo chmod -R 775 /var/www/pngmc/logs
sudo chown -R www-data:www-data /var/www/pngmc/logs
```

### 5.2 Configure Database Connection

**Option A: Using .env file (Recommended)**

```bash
cd /var/www/pngmc
sudo nano .env
```

**Create .env file:**

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_USER=sms2_user
DB_PASS=your_secure_password_here
DB_NAME=sms2_db
DB_CHARSET=utf8mb4

# Application Settings
APP_ENV=production
APP_DEBUG=false
```

**Set permissions:**
```bash
sudo chmod 600 /var/www/pngmc/.env
sudo chown www-data:www-data /var/www/pngmc/.env
```

**Option B: Edit db_config.php directly**

```bash
sudo nano /var/www/pngmc/pages/includes/db_config.php
```

**Update these lines:**

```php
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);  // Linux uses 3306
define('DB_USER', 'sms2_user');
define('DB_PASS', 'your_secure_password_here');
define('DB_NAME', 'sms2_db');
```

---

## ✅ **STEP 6: Test and Verify**

### 6.1 Test Database Connection

**Via Browser:**
```
http://your-server-ip/database/test_connection.php
```

**Should show:**
- ✓ Database connection successful!
- List of tables
- User count

### 6.2 Update Password Hashes

**Via Browser:**
```
http://your-server-ip/database/update_passwords.php
```

**Should show:**
- ✓ Updated password for: admin01
- ✓ Updated password for: admin02
- Update complete!

### 6.3 Test Application

**Landing Page:**
```
http://your-server-ip/
```

**Staff Login:**
```
http://your-server-ip/pages/login.php
```

**Student Login:**
```
http://your-server-ip/student_login.php
```

**Test Credentials (after password update):**
- Username: `admin01`
- Password: `adminpass1`
- User Type: `Administration`

---

## 🔒 **STEP 7: Security Configuration**

### 7.1 Configure Firewall (UFW)

```bash
# Allow SSH (important!)
sudo ufw allow 22/tcp

# Allow HTTP
sudo ufw allow 80/tcp

# Allow HTTPS (for future SSL)
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

### 7.2 Secure PHP Configuration

```bash
sudo nano /etc/php/8.1/apache2/php.ini
```

**Update these settings:**

```ini
# Security settings
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

# File uploads
upload_max_filesize = 10M
post_max_size = 10M

# Session security
session.cookie_httponly = 1
session.cookie_secure = 0  # Set to 1 when using HTTPS
session.use_strict_mode = 1
```

**Restart Apache:**
```bash
sudo systemctl restart apache2
```

### 7.3 Secure .htaccess (if needed)

The PNGMC folder should already have `.htaccess`. Verify:

```bash
cat /var/www/pngmc/.htaccess
```

**Should contain security rules like:**
- Directory protection
- File access restrictions
- Security headers

---

## 🌐 **STEP 8: Configure for IP Address (Initial)**

Since you're using IP address initially:

### 8.1 Update Apache Config

```bash
sudo nano /etc/apache2/sites-available/pngmc.conf
```

**Ensure:**
```apache
ServerName your-server-ip
# Or use: ServerName _
```

### 8.2 Test Configuration

```bash
sudo apache2ctl configtest
sudo systemctl reload apache2
```

---

## 📝 **STEP 9: Final Checklist**

- [ ] Apache2 installed and running
- [ ] PHP 8.1 installed with all extensions
- [ ] MySQL installed and secured
- [ ] PNGMC files copied to `/var/www/pngmc`
- [ ] Apache virtual host configured
- [ ] Database `sms2_db` created
- [ ] Database schema imported
- [ ] Database connection configured
- [ ] File permissions set correctly
- [ ] Password hashes updated
- [ ] Firewall configured
- [ ] Application accessible via browser
- [ ] Login working (staff and student)

---

## 🐛 **TROUBLESHOOTING**

### Issue: "403 Forbidden"

**Solution:**
```bash
# Check permissions
sudo chown -R www-data:www-data /var/www/pngmc
sudo chmod -R 755 /var/www/pngmc

# Check Apache config
sudo apache2ctl configtest
```

### Issue: "500 Internal Server Error"

**Solution:**
```bash
# Check Apache error log
sudo tail -f /var/log/apache2/error.log

# Check PHP errors
sudo tail -f /var/log/apache2/pngmc_error.log

# Check file permissions
sudo chmod -R 755 /var/www/pngmc
```

### Issue: "Database connection failed"

**Solution:**
```bash
# Test MySQL connection
sudo mysql -u sms2_user -p sms2_db

# Check MySQL is running
sudo systemctl status mysql

# Verify credentials in db_config.php or .env
```

### Issue: "Page not found" or "404"

**Solution:**
```bash
# Enable mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2

# Check .htaccess exists
ls -la /var/www/pngmc/.htaccess
```

---

## 🚀 **NEXT STEPS (After Basic Setup)**

1. **Configure Domain** (when you purchase domain):
   - Update Apache `ServerName` to your domain
   - Point DNS to your server IP

2. **Setup SSL/HTTPS** (when ready):
   ```bash
   sudo apt install -y certbot python3-certbot-apache
   sudo certbot --apache -d your-domain.com
   ```

3. **Configure Email** (if needed):
   - Install and configure Postfix or Sendmail
   - Update email settings in application

4. **Setup Backups**:
   - Database backups (cron job)
   - File backups

---

## 📞 **QUICK REFERENCE**

**Application URL:**
```
http://your-server-ip/
```

**Admin Login:**
```
http://your-server-ip/pages/login.php
```

**Student Login:**
```
http://your-server-ip/student_login.php
```

**Database Test:**
```
http://your-server-ip/database/test_connection.php
```

**Apache Status:**
```bash
sudo systemctl status apache2
```

**MySQL Status:**
```bash
sudo systemctl status mysql
```

**View Logs:**
```bash
# Apache error log
sudo tail -f /var/log/apache2/error.log

# Application error log
sudo tail -f /var/www/pngmc/logs/php_errors.log
```

---

**Your PNGMC PHP system should now be live and fully functional!** 🎉

