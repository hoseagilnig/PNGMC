# Deploying Without Git - Direct File Copy
## Copy Files from XAMPP to Ubuntu Server

---

## 📋 METHOD 1: Copy via RDP (Easiest)

### Step 1: Prepare Files on Windows

1. **Navigate to XAMPP folder:**
   ```
   C:\xampp\htdocs\sms2\laravel-sms\
   ```

2. **Create a ZIP file (recommended):**
   - Select all files/folders in `laravel-sms\`
   - **EXCLUDE these:**
     - `vendor\` folder (will install via Composer)
     - `node_modules\` folder (will install via NPM)
     - `.env` file (create new one on server)
     - `storage\logs\*` (log files)
     - `storage\framework\cache\*` (cache files)
   - Right-click → Send to → Compressed (zipped) folder
   - Name it: `laravel-sms.zip`

### Step 2: Transfer to Ubuntu Server

**Via RDP:**
1. Connect to Ubuntu server via RDP
2. Open file manager
3. Copy `laravel-sms.zip` to Ubuntu (via RDP clipboard or network share)
4. Extract to `/var/www/laravel-sms/`

**Or via Network Share:**
1. Share the folder from Windows
2. Access from Ubuntu via Samba/NFS
3. Copy files directly

---

## 📋 METHOD 2: Copy via SCP (From Windows)

### Using PowerShell on Windows:

```powershell
# Navigate to XAMPP directory
cd C:\xampp\htdocs\sms2

# Copy Laravel folder to server
scp -r laravel-sms ict@your-server-ip:/home/ict/

# Exclude vendor and node_modules (if they exist)
# You may need to create a temporary folder without these
```

### Then on Ubuntu Server:

```bash
# Move to web directory
sudo mv /home/ict/laravel-sms /var/www/
```

---

## 📋 METHOD 3: Manual File Transfer Checklist

### Files/Folders to Copy:

✅ **Copy These:**
- `app/` - Application code
- `bootstrap/` - Bootstrap files
- `config/` - Configuration (if exists)
- `database/` - Migrations and seeders
- `resources/` - Views and assets
- `routes/` - Route definitions
- `storage/` - Storage directory (empty structure)
- `artisan` - Artisan CLI
- `composer.json` - Dependencies
- `composer.lock` - Lock file (if exists)
- `.env.example` - Environment template
- `nginx/` - Nginx configuration
- Documentation files (README.md, etc.)

❌ **Do NOT Copy:**
- `vendor/` - Install via Composer
- `node_modules/` - Install via NPM
- `.env` - Create new one
- `storage/logs/*.log` - Log files
- `storage/framework/cache/*` - Cache files
- `.git/` - Git repository (if exists)

---

## 🚀 COMPLETE SETUP AFTER COPYING

### Step 1: Install Required Software

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP and extensions
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath

# Install Nginx
sudo apt install -y nginx

# Install MySQL
sudo apt install -y mysql-server

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

### Step 2: Set Up Application

```bash
# Navigate to application
cd /var/www/laravel-sms

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Create .env file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 3: Configure .env File

```bash
nano .env
```

**Update these values:**
```env
APP_NAME="PNG Maritime College SMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-server-ip

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pngmc
DB_USERNAME=pngmc_user
DB_PASSWORD=your_secure_password_here

SESSION_DRIVER=database
SESSION_ENCRYPT=false
SESSION_DOMAIN=null
```

### Step 4: Create Database

```bash
sudo mysql -u root -p
```

**In MySQL:**
```sql
CREATE DATABASE pngmc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pngmc_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';
GRANT ALL PRIVILEGES ON pngmc.* TO 'pngmc_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 5: Run Migrations

```bash
php artisan migrate --force
```

### Step 6: Set File Permissions

```bash
sudo chown -R www-data:www-data /var/www/laravel-sms
sudo chmod -R 755 /var/www/laravel-sms
sudo chmod -R 775 /var/www/laravel-sms/storage
sudo chmod -R 775 /var/www/laravel-sms/bootstrap/cache
sudo chmod 600 /var/www/laravel-sms/.env
```

### Step 7: Configure Nginx

```bash
# Copy Nginx config
sudo cp /var/www/laravel-sms/nginx/sms.conf /etc/nginx/sites-available/sms

# Edit for IP address
sudo nano /etc/nginx/sites-available/sms
```

**Change these lines:**
```nginx
# Remove HTTPS redirect block (lines 1-8)
# Change server_name to:
server_name _;
# Change listen to:
listen 80;
# Remove SSL certificate lines
```

**Or use this simplified config:**
```nginx
server {
    listen 80;
    server_name _;
    
    root /var/www/laravel-sms/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

**Enable site:**
```bash
sudo ln -s /etc/nginx/sites-available/sms /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

### Step 8: Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 9: Configure Firewall

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

---

## 📦 QUICK COPY SCRIPT (Windows PowerShell)

Create this script on Windows to prepare files for copying:

```powershell
# prepare-for-copy.ps1
# Run this in PowerShell from C:\xampp\htdocs\sms2\

$source = "laravel-sms"
$destination = "laravel-sms-clean"

# Create clean copy
Copy-Item -Path $source -Destination $destination -Recurse

# Remove folders to exclude
Remove-Item -Path "$destination\vendor" -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item -Path "$destination\node_modules" -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item -Path "$destination\.env" -Force -ErrorAction SilentlyContinue
Remove-Item -Path "$destination\.git" -Recurse -Force -ErrorAction SilentlyContinue

# Remove log files
Get-ChildItem -Path "$destination\storage\logs" -Filter "*.log" | Remove-Item -Force -ErrorAction SilentlyContinue

# Create ZIP
Compress-Archive -Path $destination -DestinationPath "laravel-sms.zip" -Force

Write-Host "Created laravel-sms.zip - Ready to copy to server!"
```

---

## ✅ VERIFICATION CHECKLIST

After copying and setup:

- [ ] Files copied to `/var/www/laravel-sms/`
- [ ] Composer dependencies installed (`vendor/` folder exists)
- [ ] `.env` file created and configured
- [ ] Application key generated
- [ ] Database created
- [ ] Migrations run successfully
- [ ] File permissions set correctly
- [ ] Nginx configured and running
- [ ] Application accessible at `http://your-server-ip`
- [ ] Can login with test credentials

---

## 🐛 TROUBLESHOOTING

### Issue: "Class not found"
**Solution:** Run `composer install`

### Issue: "500 Error"
**Solution:** 
- Check file permissions
- Check `storage/logs/laravel.log`
- Ensure `.env` exists and is configured

### Issue: "Database connection failed"
**Solution:**
- Verify database credentials in `.env`
- Check MySQL is running: `sudo systemctl status mysql`
- Test connection: `mysql -u pngmc_user -p pngmc`

### Issue: "Permission denied"
**Solution:**
```bash
sudo chown -R www-data:www-data /var/www/laravel-sms
sudo chmod -R 775 storage bootstrap/cache
```

---

**You can deploy without Git by copying files directly!** Just follow the setup steps after copying. 🚀

