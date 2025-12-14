# Laravel 11 + Nginx Deployment Guide
## PNG Maritime College SMS

---

## 📋 PREREQUISITES

- Ubuntu 24.04 LTS
- PHP 8.2+
- MySQL 5.7+ or MariaDB 10.3+
- Nginx
- Composer
- Node.js & NPM (for asset compilation)

---

## 🚀 INSTALLATION STEPS

### 1. Install Required Software

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2 and extensions
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath

# Install Nginx
sudo apt install -y nginx

# Install MySQL
sudo apt install -y mysql-server

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js (for asset compilation)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2. Configure MySQL

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE sms2_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sms_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON sms2_db.* TO 'sms_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Deploy Laravel Application

```bash
# Clone or upload application
cd /var/www
sudo git clone https://github.com/your-repo/laravel-sms.git
# OR upload via SFTP/SCP

# Set permissions
cd laravel-sms
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 storage bootstrap/cache

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure .env file
nano .env
```

Update `.env`:
```env
APP_NAME="PNG Maritime College SMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sms.pngmc.ac.pg

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sms2_db
DB_USERNAME=sms_user
DB_PASSWORD=strong_password_here
```

### 4. Run Migrations

```bash
# Run database migrations
php artisan migrate --force

# Seed initial data (if needed)
php artisan db:seed --force
```

### 5. Configure Nginx

```bash
# Copy Nginx configuration
sudo cp nginx/sms.conf /etc/nginx/sites-available/sms
sudo ln -s /etc/nginx/sites-available/sms /etc/nginx/sites-enabled/

# Test Nginx configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### 6. Set Up SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d sms.pngmc.ac.pg -d www.sms.pngmc.ac.pg

# Auto-renewal is set up automatically
```

### 7. Configure UFW Firewall

```bash
# Enable UFW
sudo ufw enable

# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP
sudo ufw allow 80/tcp

# Allow HTTPS
sudo ufw allow 443/tcp

# Check status
sudo ufw status
```

### 8. Set Up Queue Worker (if using queues)

```bash
# Create systemd service
sudo nano /etc/systemd/system/laravel-worker.service
```

```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/laravel-sms/artisan queue:work --sleep=3 --tries=3

[Install]
WantedBy=multi-user.target
```

```bash
# Enable and start service
sudo systemctl enable laravel-worker
sudo systemctl start laravel-worker
```

### 9. Set Up Scheduled Tasks (Cron)

```bash
# Edit crontab
sudo crontab -e -u www-data
```

Add:
```
* * * * * cd /var/www/laravel-sms && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔒 SECURITY CONFIGURATION

### 1. File Permissions

```bash
# Set correct permissions
sudo chown -R www-data:www-data /var/www/laravel-sms
sudo find /var/www/laravel-sms -type f -exec chmod 644 {} \;
sudo find /var/www/laravel-sms -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/laravel-sms/storage
sudo chmod -R 775 /var/www/laravel-sms/bootstrap/cache
```

### 2. PHP-FPM Security

Edit `/etc/php/8.2/fpm/php.ini`:
```ini
expose_php = Off
display_errors = Off
log_errors = On
```

### 3. Nginx Security

Already configured in `nginx/sms.conf`:
- Security headers
- Hidden file protection
- Sensitive file protection

---

## 📊 MONITORING

### Check Application Status

```bash
# Check Nginx
sudo systemctl status nginx

# Check PHP-FPM
sudo systemctl status php8.2-fpm

# Check MySQL
sudo systemctl status mysql

# Check logs
sudo tail -f /var/log/nginx/sms-error.log
sudo tail -f /var/www/laravel-sms/storage/logs/laravel.log
```

---

## 🔄 DEPLOYMENT WORKFLOW

### Clean → Demo → Production

1. **Clean Environment (Development)**
   - Local development
   - Testing
   - Code review

2. **Demo Environment (Staging)**
   - `APP_ENV=staging`
   - `APP_DEBUG=true`
   - Test database
   - Client review

3. **Production Environment**
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - Production database
   - SSL enabled
   - UFW configured

---

## 🐛 TROUBLESHOOTING

### Permission Issues
```bash
sudo chown -R www-data:www-data /var/www/laravel-sms
sudo chmod -R 775 storage bootstrap/cache
```

### Nginx 502 Bad Gateway
```bash
# Check PHP-FPM
sudo systemctl status php8.2-fpm
sudo systemctl restart php8.2-fpm
```

### Database Connection Issues
- Verify `.env` database credentials
- Check MySQL is running: `sudo systemctl status mysql`
- Test connection: `mysql -u sms_user -p sms2_db`

---

## ✅ DEPLOYMENT CHECKLIST

- [ ] Ubuntu 24.04 LTS installed
- [ ] PHP 8.2+ installed with required extensions
- [ ] MySQL/MariaDB installed and configured
- [ ] Nginx installed and configured
- [ ] Laravel application deployed
- [ ] `.env` file configured
- [ ] Database migrations run
- [ ] SSL certificate installed
- [ ] UFW firewall configured
- [ ] File permissions set correctly
- [ ] Queue worker configured (if needed)
- [ ] Scheduled tasks configured
- [ ] Application tested and working

---

**Deployment Complete!** 🎉

