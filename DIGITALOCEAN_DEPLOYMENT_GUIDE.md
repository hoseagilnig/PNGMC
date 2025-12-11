# DigitalOcean Deployment Guide

**System:** PNG Maritime College SMS2  
**Target:** DigitalOcean Droplet  
**Method:** Automated via GitHub Actions

---

## 📋 PREREQUISITES

### **1. DigitalOcean Droplet Setup**
- [ ] Create Ubuntu 20.04+ Droplet
- [ ] Configure SSH access
- [ ] Install LAMP/LEMP stack
- [ ] Set up firewall (ports 22, 80, 443)
- [ ] Configure SSL certificate (Let's Encrypt)

### **2. GitHub Repository**
- [ ] Repository is connected to GitHub ✅
- [ ] You have admin access to repository
- [ ] GitHub Actions are enabled

### **3. Required Information**
- DigitalOcean droplet IP address
- SSH username (usually `root` or custom user)
- SSH private key or password
- Web server root directory (usually `/var/www/html`)

---

## 🔧 SETUP STEPS

### **Step 1: Generate SSH Key Pair (if needed)**

If you don't have an SSH key:

```bash
# Generate SSH key
ssh-keygen -t rsa -b 4096 -C "github-actions-deploy"

# Copy public key to DigitalOcean droplet
ssh-copy-id -i ~/.ssh/id_rsa.pub root@YOUR_DROPLET_IP
```

### **Step 2: Add GitHub Secrets**

Go to your GitHub repository → Settings → Secrets and variables → Actions

Add these secrets:

1. **`DIGITALOCEAN_HOST`**
   - Value: Your droplet IP address (e.g., `123.45.67.89`)

2. **`DIGITALOCEAN_USER`**
   - Value: SSH username (usually `root`)

3. **`DIGITALOCEAN_SSH_KEY`**
   - Value: Your private SSH key (entire content of `~/.ssh/id_rsa`)

4. **`DIGITALOCEAN_SSH_PORT`** (Optional)
   - Value: SSH port (default: `22`)

### **Step 3: Configure Server**

SSH into your DigitalOcean droplet:

```bash
ssh root@YOUR_DROPLET_IP
```

#### **Install LAMP Stack:**

```bash
# Update system
apt update && apt upgrade -y

# Install Apache
apt install apache2 -y

# Install MySQL
apt install mysql-server -y

# Install PHP 8.0+
apt install php php-mysqli php-mbstring php-curl php-xml php-zip -y

# Enable Apache modules
a2enmod rewrite
a2enmod headers
systemctl restart apache2
```

#### **Create Database:**

```bash
mysql -u root -p

# In MySQL:
CREATE DATABASE sms2_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sms2_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON sms2_db.* TO 'sms2_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### **Set Up Web Directory:**

```bash
# Create web directory
mkdir -p /var/www/html
chown -R www-data:www-data /var/www/html

# Create uploads directory
mkdir -p /var/www/html/uploads
chmod -R 755 /var/www/html/uploads
```

### **Step 4: Configure Environment Variables**

Create `.env` file on server:

```bash
cd /var/www/html
nano .env
```

Add:

```env
DB_HOST=localhost
DB_PORT=3306
DB_USER=sms2_user
DB_PASS=strong_password_here
DB_NAME=sms2_db
DB_CHARSET=utf8mb4

GEMINI_API_KEY=your_api_key_here
CHATBOT_DEBUG=false
```

Set permissions:

```bash
chmod 600 .env
chown www-data:www-data .env
```

### **Step 5: Set Up SSL Certificate (Let's Encrypt)**

```bash
# Install Certbot
apt install certbot python3-certbot-apache -y

# Get certificate
certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal (already configured)
certbot renew --dry-run
```

### **Step 6: Configure Apache**

Create virtual host:

```bash
nano /etc/apache2/sites-available/sms2.conf
```

Add:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/html
    
    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/sms2_error.log
    CustomLog ${APACHE_LOG_DIR}/sms2_access.log combined
</VirtualHost>
```

Enable site:

```bash
a2ensite sms2.conf
a2dissite 000-default.conf
systemctl reload apache2
```

### **Step 7: Deploy Database Schema**

After first deployment, run database setup:

```bash
cd /var/www/html
mysql -u sms2_user -p sms2_db < database/create_app_tables.sql
mysql -u sms2_user -p sms2_db < database/create_archive_tables.sql
```

---

## 🚀 DEPLOYMENT

### **Automatic Deployment (GitHub Actions)**

1. **Push to main branch:**
   ```bash
   git checkout main
   git merge 2025-12-09-ec34-9b09b
   git push origin main
   ```

2. **GitHub Actions will automatically:**
   - Checkout code
   - Copy files to DigitalOcean via SCP
   - Set file permissions
   - Restart services (if configured)

3. **Monitor deployment:**
   - Go to GitHub → Actions tab
   - Watch deployment progress
   - Check for any errors

### **Manual Deployment (Alternative)**

If you prefer manual deployment:

```bash
# On your local machine
rsync -avz --exclude '.git' --exclude '.env' --exclude 'logs/' \
  --exclude 'uploads/' ./ root@YOUR_DROPLET_IP:/var/www/html/

# SSH into server
ssh root@YOUR_DROPLET_IP

# Set permissions
cd /var/www/html
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 755 uploads/
chmod 600 .env
```

---

## ✅ POST-DEPLOYMENT CHECKLIST

### **1. Verify Application**
- [ ] Access website: `https://yourdomain.com`
- [ ] Test staff login
- [ ] Test student login
- [ ] Verify database connection

### **2. Test Features**
- [ ] Submit school leaver application
- [ ] Submit returning student application
- [ ] Test file uploads
- [ ] Verify CSRF protection
- [ ] Check security headers

### **3. Verify Security**
- [ ] SSL certificate is active
- [ ] HTTPS redirects working
- [ ] Security headers present
- [ ] `.env` file is not accessible
- [ ] File permissions correct

### **4. Monitor Logs**
- [ ] Check Apache error logs
- [ ] Check PHP error logs
- [ ] Monitor security logs
- [ ] Review application logs

---

## 🔧 TROUBLESHOOTING

### **Issue: Deployment Fails - SSH Connection**

**Solution:**
- Verify SSH key is correct in GitHub secrets
- Check droplet firewall allows SSH (port 22)
- Test SSH connection manually: `ssh root@YOUR_DROPLET_IP`

### **Issue: Files Not Updating**

**Solution:**
- Check file permissions on server
- Verify deployment script is running
- Check GitHub Actions logs for errors

### **Issue: Database Connection Error**

**Solution:**
- Verify `.env` file exists and has correct credentials
- Check MySQL is running: `systemctl status mysql`
- Test connection: `mysql -u sms2_user -p sms2_db`

### **Issue: 500 Internal Server Error**

**Solution:**
- Check Apache error logs: `tail -f /var/log/apache2/error.log`
- Verify PHP errors: `tail -f /var/log/apache2/php_errors.log`
- Check file permissions
- Verify `.htaccess` is working

---

## 📊 MONITORING & MAINTENANCE

### **Regular Tasks:**
- Monitor server resources (CPU, RAM, Disk)
- Review error logs weekly
- Update system packages monthly
- Backup database daily
- Renew SSL certificate (auto-renewal configured)

### **Backup Strategy:**
```bash
# Database backup script
mysqldump -u sms2_user -p sms2_db > backup_$(date +%Y%m%d).sql

# Files backup
tar -czf backup_files_$(date +%Y%m%d).tar.gz /var/www/html/uploads/
```

---

## 🔐 SECURITY BEST PRACTICES

1. **Keep system updated:**
   ```bash
   apt update && apt upgrade -y
   ```

2. **Configure firewall:**
   ```bash
   ufw allow 22/tcp
   ufw allow 80/tcp
   ufw allow 443/tcp
   ufw enable
   ```

3. **Disable root login (optional):**
   - Create non-root user with sudo
   - Disable root SSH access

4. **Regular backups:**
   - Database: Daily
   - Files: Weekly
   - Store backups off-server

5. **Monitor logs:**
   - Security events
   - Failed login attempts
   - Error patterns

---

## 📝 SUMMARY

### **Deployment Method:**
✅ Automated via GitHub Actions (recommended)

### **Requirements:**
- DigitalOcean droplet with LAMP stack
- GitHub repository with Actions enabled
- SSH access configured
- Environment variables set

### **Process:**
1. Set up DigitalOcean droplet
2. Configure GitHub secrets
3. Push to main branch
4. Monitor deployment
5. Verify application

**Status:** Ready for deployment once DigitalOcean server is configured.

