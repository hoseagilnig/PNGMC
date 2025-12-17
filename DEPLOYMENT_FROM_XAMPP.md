# Deploying from XAMPP to Ubuntu Server
## Step-by-Step Guide

---

## ✅ YES, You Can Copy Files, BUT...

You **CAN** copy files from XAMPP, but you **CANNOT** just paste and run. Here's what you need to do:

---

## 📋 WHAT TO COPY

### Option 1: Copy Laravel System Only (Recommended)

Copy only the **Laravel 11** files:

**From:** `C:\xampp\htdocs\sms2\laravel-sms\`  
**To:** `/var/www/laravel-sms/` (on Ubuntu server)

**What to copy:**
- ✅ `app/` directory
- ✅ `bootstrap/` directory
- ✅ `config/` directory (if exists)
- ✅ `database/` directory
- ✅ `resources/` directory
- ✅ `routes/` directory
- ✅ `storage/` directory (empty structure)
- ✅ `artisan` file
- ✅ `composer.json`
- ✅ `.env.example` (rename to `.env` later)
- ✅ `nginx/` directory (for Nginx config)
- ✅ Documentation files

**What NOT to copy:**
- ❌ `vendor/` directory (install via Composer)
- ❌ `node_modules/` directory (install via NPM)
- ❌ `.env` file (create new one)
- ❌ `storage/logs/*` (log files)
- ❌ `storage/framework/cache/*` (cache files)

---

## 📤 METHOD 1: Copy via RDP (Easiest)

### Step 1: Copy Files from Windows

1. **On Windows (XAMPP machine):**
   - Navigate to: `C:\xampp\htdocs\sms2\laravel-sms\`
   - Select all files/folders EXCEPT:
     - `vendor/` folder
     - `node_modules/` folder
     - `.env` file (if exists)
   - Copy (Ctrl+C)

### Step 2: Paste on Ubuntu Server

1. **On Ubuntu Server (via RDP):**
   - Open file manager
   - Navigate to: `/var/www/`
   - Create folder: `laravel-sms`
   - Paste files (Ctrl+V)

### Step 3: Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/laravel-sms
sudo chmod -R 755 /var/www/laravel-sms
sudo chmod -R 775 /var/www/laravel-sms/storage
sudo chmod -R 775 /var/www/laravel-sms/bootstrap/cache
```

---

## 📤 METHOD 2: Copy via SCP (More Reliable)

### From Windows PowerShell:

```powershell
# Navigate to XAMPP directory
cd C:\xampp\htdocs\sms2

# Copy Laravel files to server
scp -r laravel-sms ict@your-server-ip:/home/ict/

# Then on server, move to web directory
# SSH into server and run:
sudo mv /home/ict/laravel-sms /var/www/
```

---

## 📤 METHOD 3: Use Git (Best Practice)

### On Ubuntu Server:

```bash
cd /var/www
sudo git clone https://github.com/hoseagilnig/PNGMC.git
cd PNGMC/laravel-sms
```

**This is the BEST method** because:
- ✅ Gets latest code from GitHub
- ✅ No file transfer needed
- ✅ Easy to update later
- ✅ Version control maintained

---

## ⚠️ WHAT YOU MUST DO AFTER COPYING

### 1. Install Dependencies

```bash
cd /var/www/laravel-sms

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies (if using frontend assets)
npm install
npm run build
```

### 2. Configure Environment

```bash
# Copy example file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file
nano .env
```

**Update these in `.env`:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-server-ip

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pngmc
DB_USERNAME=pngmc_user
DB_PASSWORD=your_password_here
```

### 3. Set Up Database

```bash
# Create database
sudo mysql -u root -p

# In MySQL:
CREATE DATABASE pngmc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pngmc_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON pngmc.* TO 'pngmc_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Run Migrations

```bash
php artisan migrate --force
```

### 5. Set File Permissions

```bash
sudo chown -R www-data:www-data /var/www/laravel-sms
sudo chmod -R 755 /var/www/laravel-sms
sudo chmod -R 775 /var/www/laravel-sms/storage
sudo chmod -R 775 /var/www/laravel-sms/bootstrap/cache
sudo chmod 600 /var/www/laravel-sms/.env
```

### 6. Configure Nginx

```bash
# Copy Nginx config
sudo cp /var/www/laravel-sms/nginx/sms.conf /etc/nginx/sites-available/sms

# Edit for IP address (if no domain yet)
sudo nano /etc/nginx/sites-available/sms
# Change: server_name _; (or your IP)

# Enable site
sudo ln -s /etc/nginx/sites-available/sms /etc/nginx/sites-enabled/

# Test and reload
sudo nginx -t
sudo systemctl reload nginx
```

### 7. Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🚫 WHY YOU CAN'T JUST COPY AND RUN

### Missing Dependencies:
- ❌ `vendor/` folder (Composer packages) - **MUST install**
- ❌ `node_modules/` folder (NPM packages) - **MUST install**

### Configuration Needed:
- ❌ `.env` file - **MUST configure**
- ❌ Database - **MUST create**
- ❌ Application key - **MUST generate**

### Server Setup:
- ❌ Nginx configuration - **MUST configure**
- ❌ File permissions - **MUST set**
- ❌ PHP-FPM - **MUST install**

---

## ✅ QUICK COPY CHECKLIST

After copying files:

- [ ] Install Composer dependencies: `composer install`
- [ ] Create `.env` file from `.env.example`
- [ ] Generate app key: `php artisan key:generate`
- [ ] Configure database in `.env`
- [ ] Create database and user
- [ ] Run migrations: `php artisan migrate`
- [ ] Set file permissions
- [ ] Configure Nginx
- [ ] Test Nginx config
- [ ] Reload Nginx
- [ ] Cache config: `php artisan config:cache`

---

## 🎯 RECOMMENDED APPROACH

**Best Method:** Use Git (already on GitHub)

```bash
# On Ubuntu server
cd /var/www
sudo git clone https://github.com/hoseagilnig/PNGMC.git
cd PNGMC/laravel-sms
```

**Then follow the setup steps above.**

---

## 📝 SUMMARY

**Can you copy files?** ✅ **YES**  
**Can you just paste and run?** ❌ **NO**

**You MUST:**
1. Copy files (or use Git)
2. Install dependencies (`composer install`)
3. Configure `.env`
4. Set up database
5. Run migrations
6. Configure Nginx
7. Set permissions

**The files alone won't work - you need to complete the setup steps!**

