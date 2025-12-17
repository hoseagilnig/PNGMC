#!/bin/bash
# PNGMC PHP System - Quick Deployment Script
# Ubuntu 22.04 LTS - Apache2 + PHP 8.1 + MySQL
# Run this script as root or with sudo

set -e

echo "=========================================="
echo "PNGMC PHP System - Quick Deployment"
echo "Ubuntu 22.04 LTS Setup"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root or with sudo"
    exit 1
fi

# Update system
echo "Step 1: Updating system..."
apt update
apt upgrade -y

# Install Apache2
echo ""
echo "Step 2: Installing Apache2..."
apt install -y apache2
systemctl enable apache2
systemctl start apache2

# Install PHP 8.1
echo ""
echo "Step 3: Installing PHP 8.1..."
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt update

apt install -y php8.1 php8.1-cli php8.1-fpm php8.1-mysql php8.1-mbstring \
    php8.1-xml php8.1-curl php8.1-zip php8.1-gd php8.1-bcmath php8.1-intl \
    php8.1-opcache php8.1-readline

a2enmod php8.1

# Install MySQL
echo ""
echo "Step 4: Installing MySQL..."
apt install -y mysql-server
systemctl enable mysql
systemctl start mysql

# Enable Apache modules
echo ""
echo "Step 5: Enabling Apache modules..."
a2enmod rewrite
a2enmod headers
a2enmod ssl
systemctl restart apache2

# Get server IP
SERVER_IP=$(hostname -I | awk '{print $1}')

# Create Apache virtual host
echo ""
echo "Step 6: Creating Apache virtual host..."
cat > /etc/apache2/sites-available/pngmc.conf <<EOF
<VirtualHost *:80>
    ServerName $SERVER_IP
    
    DocumentRoot /var/www/pngmc
    
    <Directory /var/www/pngmc>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/pngmc_error.log
    CustomLog \${APACHE_LOG_DIR}/pngmc_access.log combined
    
    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>
</VirtualHost>
EOF

# Enable site
a2ensite pngmc.conf
a2dissite 000-default.conf

# Test Apache config
echo ""
echo "Step 7: Testing Apache configuration..."
apache2ctl configtest

# Reload Apache
systemctl reload apache2

# Configure firewall
echo ""
echo "Step 8: Configuring firewall..."
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# Create application directory
echo ""
echo "Step 9: Creating application directory..."
mkdir -p /var/www/pngmc
chown -R www-data:www-data /var/www/pngmc

echo ""
echo "=========================================="
echo "Installation Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Copy PNGMC files to /var/www/pngmc/"
echo "2. Create database: sudo mysql -u root -p"
echo "3. Import database schema"
echo "4. Configure database connection"
echo "5. Set file permissions"
echo ""
echo "Server IP: $SERVER_IP"
echo "Application will be at: http://$SERVER_IP/"
echo ""
echo "See PNGMC_DEPLOYMENT_UBUNTU.md for detailed instructions"
echo ""

