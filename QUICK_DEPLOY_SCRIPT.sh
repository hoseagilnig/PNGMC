#!/bin/bash
# Quick Deployment Script for Ubuntu 22.04
# PNG Maritime College SMS - Laravel 11
# Run this script on your Ubuntu server

set -e  # Exit on error

echo "=========================================="
echo "PNG Maritime College SMS - Deployment"
echo "Ubuntu 22.04 LTS"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -eq 0 ]; then 
   echo -e "${RED}Please do not run as root. Run as your user (ict) and use sudo when needed.${NC}"
   exit 1
fi

echo -e "${GREEN}Step 1: Installing required packages...${NC}"
sudo apt update
sudo apt install -y nginx mysql-server php8.2-fpm php8.2-cli php8.2-mysql \
    php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath \
    git unzip curl

echo -e "${GREEN}Step 2: Installing Composer...${NC}"
if ! command -v composer &> /dev/null; then
    cd /tmp
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    sudo chmod +x /usr/local/bin/composer
fi

echo -e "${GREEN}Step 3: Cloning repository...${NC}"
cd /var/www
if [ -d "PNGMC" ]; then
    echo -e "${YELLOW}PNGMC directory exists. Updating...${NC}"
    cd PNGMC
    sudo git pull
else
    sudo git clone https://github.com/hoseagilnig/PNGMC.git
    cd PNGMC
fi

echo -e "${GREEN}Step 4: Setting up Laravel application...${NC}"
cd laravel-sms

# Install dependencies
echo -e "${YELLOW}Installing Composer dependencies...${NC}"
composer install --no-dev --optimize-autoloader

# Create .env file
if [ ! -f .env ]; then
    echo -e "${YELLOW}Creating .env file...${NC}"
    cp .env.example .env
    php artisan key:generate
    echo -e "${YELLOW}Please edit .env file with your database credentials${NC}"
    echo -e "${YELLOW}Run: nano .env${NC}"
fi

# Set permissions
echo -e "${GREEN}Step 5: Setting file permissions...${NC}"
sudo chown -R www-data:www-data /var/www/PNGMC/laravel-sms
sudo chmod -R 755 /var/www/PNGMC/laravel-sms
sudo chmod -R 775 /var/www/PNGMC/laravel-sms/storage
sudo chmod -R 775 /var/www/PNGMC/laravel-sms/bootstrap/cache
if [ -f .env ]; then
    sudo chmod 600 /var/www/PNGMC/laravel-sms/.env
fi

echo -e "${GREEN}Step 6: Database setup...${NC}"
echo -e "${YELLOW}You need to create the database manually:${NC}"
echo "sudo mysql -u root -p"
echo ""
echo "Then run:"
echo "CREATE DATABASE pngmc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo "CREATE USER 'pngmc_user'@'localhost' IDENTIFIED BY 'your_password';"
echo "GRANT ALL PRIVILEGES ON pngmc.* TO 'pngmc_user'@'localhost';"
echo "FLUSH PRIVILEGES;"
echo "EXIT;"
echo ""
read -p "Press Enter after you've created the database..."

# Run migrations
echo -e "${GREEN}Step 7: Running database migrations...${NC}"
php artisan migrate --force

# Configure Nginx
echo -e "${GREEN}Step 8: Configuring Nginx...${NC}"
sudo cp nginx/sms.conf /etc/nginx/sites-available/sms

# Update server_name for IP address
SERVER_IP=$(hostname -I | awk '{print $1}')
sudo sed -i "s/server_name sms.pngmc.ac.pg www.sms.pngmc.ac.pg;/server_name _;/g" /etc/nginx/sites-available/sms
sudo sed -i "s/listen 443 ssl http2;/listen 80;/g" /etc/nginx/sites-available/sms
sudo sed -i "/ssl_certificate/d" /etc/nginx/sites-available/sms
sudo sed -i "/ssl_certificate_key/d" /etc/nginx/sites-available/sms
sudo sed -i "/ssl_protocols/d" /etc/nginx/sites-available/sms
sudo sed -i "/ssl_ciphers/d" /etc/nginx/sites-available/sms
sudo sed -i "/ssl_prefer_server_ciphers/d" /etc/nginx/sites-available/sms

# Remove HTTPS redirect block
sudo sed -i '/server {/,/return 301/d' /etc/nginx/sites-available/sms

# Enable site
sudo ln -sf /etc/nginx/sites-available/sms /etc/nginx/sites-enabled/

# Remove default site
sudo rm -f /etc/nginx/sites-enabled/default

# Test Nginx config
echo -e "${GREEN}Testing Nginx configuration...${NC}"
sudo nginx -t

# Reload Nginx
echo -e "${GREEN}Reloading Nginx...${NC}"
sudo systemctl reload nginx

# Cache configuration
echo -e "${GREEN}Step 9: Caching configuration...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Configure firewall
echo -e "${GREEN}Step 10: Configuring firewall...${NC}"
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable

echo ""
echo -e "${GREEN}=========================================="
echo "Deployment Complete!"
echo "=========================================="
echo ""
echo "Your application should be accessible at:"
echo "http://$SERVER_IP"
echo ""
echo "Next steps:"
echo "1. Edit .env file: nano /var/www/PNGMC/laravel-sms/.env"
echo "2. Update APP_URL with your server IP"
echo "3. Configure database credentials"
echo "4. Test the application"
echo ""
echo -e "${YELLOW}Default login credentials (if database seeded):${NC}"
echo "Admin: admin01 / adminpass1"
echo "Finance: finance01 / financepass1"
echo "Student Services: service01 / servicepass1"
echo ""

