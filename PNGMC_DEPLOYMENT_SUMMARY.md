# PNGMC PHP System - Deployment Summary
## Quick Reference for Ubuntu 22.04 Deployment

---

## 🎯 **What You're Deploying**

**PNGMC PHP Application** - Plain PHP web application (not Laravel)
- Entry: `index.html` (landing page)
- Staff Login: `pages/login.php`
- Student Login: `student_login.php`
- Database: `sms2_db`
- Uses: MySQLi/PDO, HTML/CSS/JS

---

## 📦 **Files to Copy**

**Copy the entire `PNGMC/` folder** to Ubuntu server.

**Location on Windows:**
```
C:\xampp\htdocs\sms2\PNGMC\
```

**Location on Ubuntu:**
```
/var/www/pngmc/
```

---

## 🚀 **Quick Deployment Steps**

### 1. On Ubuntu Server - Run Quick Setup Script

```bash
# Download or copy PNGMC_QUICK_DEPLOY.sh to server
chmod +x PNGMC_QUICK_DEPLOY.sh
sudo ./PNGMC_QUICK_DEPLOY.sh
```

**This installs:**
- ✅ Apache2
- ✅ PHP 8.1 + extensions
- ✅ MySQL
- ✅ Required Apache modules
- ✅ Firewall configuration

### 2. Copy PNGMC Files

**Via RDP (Easiest):**
- Copy `PNGMC/` folder from Windows
- Paste to `/var/www/pngmc/` on Ubuntu

**Or use ZIP:**
- Run `prepare-for-deploy.ps1` on Windows (creates PNGMC.zip)
- Copy ZIP to Ubuntu
- Extract: `sudo unzip PNGMC.zip -d /var/www/pngmc`

### 3. Setup Database

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE sms2_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sms2_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON sms2_db.* TO 'sms2_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Import Database

```bash
cd /var/www/pngmc/database
sudo mysql -u root -p sms2_db < sms2_database.sql
sudo mysql -u root -p sms2_db < application_workflow_tables.sql
```

### 5. Configure Database Connection

**Create .env file:**
```bash
sudo nano /var/www/pngmc/.env
```

```env
DB_HOST=localhost
DB_PORT=3306
DB_USER=sms2_user
DB_PASS=your_password
DB_NAME=sms2_db
DB_CHARSET=utf8mb4
```

### 6. Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/pngmc
sudo chmod -R 755 /var/www/pngmc
sudo chmod -R 775 /var/www/pngmc/uploads
sudo chmod -R 775 /var/www/pngmc/logs
```

### 7. Update Passwords

**Via Browser:**
```
http://your-server-ip/database/update_passwords.php
```

### 8. Test Application

**Landing Page:**
```
http://your-server-ip/
```

**Staff Login:**
```
http://your-server-ip/pages/login.php
```

**Test Login:**
- Username: `admin01`
- Password: `adminpass1`
- User Type: `Administration`

---

## 📋 **Complete Checklist**

- [ ] Ubuntu 22.04 server ready
- [ ] RDP access configured
- [ ] Quick deploy script run (or manual install)
- [ ] PNGMC files copied to `/var/www/pngmc/`
- [ ] Database `sms2_db` created
- [ ] Database schema imported
- [ ] Database connection configured (.env or db_config.php)
- [ ] File permissions set
- [ ] Password hashes updated
- [ ] Application accessible via browser
- [ ] Login working

---

## 🔧 **Configuration Files**

### Apache Virtual Host
```
/etc/apache2/sites-available/pngmc.conf
```

### Database Config
```
/var/www/pngmc/pages/includes/db_config.php
OR
/var/www/pngmc/.env
```

### PHP Config
```
/etc/php/8.1/apache2/php.ini
```

---

## 🌐 **URLs**

**Application:**
- Landing: `http://your-server-ip/`
- Staff Login: `http://your-server-ip/pages/login.php`
- Student Login: `http://your-server-ip/student_login.php`

**Testing:**
- DB Test: `http://your-server-ip/database/test_connection.php`
- Update Passwords: `http://your-server-ip/database/update_passwords.php`

---

## 🐛 **Common Issues**

### 403 Forbidden
```bash
sudo chown -R www-data:www-data /var/www/pngmc
sudo chmod -R 755 /var/www/pngmc
```

### 500 Error
```bash
# Check logs
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/www/pngmc/logs/php_errors.log
```

### Database Connection Failed
```bash
# Test MySQL
sudo mysql -u sms2_user -p sms2_db
# Check credentials in .env or db_config.php
```

---

## 📚 **Full Documentation**

See `PNGMC_DEPLOYMENT_UBUNTU.md` for complete detailed instructions.

---

**Your PNGMC PHP system will be live and fully functional!** 🚀

