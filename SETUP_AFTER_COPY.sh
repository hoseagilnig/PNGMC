#!/bin/bash
# Setup Script - Run AFTER copying files to Ubuntu server
# Run this from: /var/www/laravel-sms/

set -e

echo "=========================================="
echo "Laravel Setup - After File Copy"
echo "=========================================="
echo ""

# Check if in correct directory
if [ ! -f "artisan" ]; then
    echo "Error: artisan file not found!"
    echo "Please run this script from /var/www/laravel-sms/"
    exit 1
fi

echo "Step 1: Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

echo ""
echo "Step 2: Creating .env file..."
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
    echo "✓ .env file created"
else
    echo "✓ .env file already exists"
fi

echo ""
echo "Step 3: Setting file permissions..."
sudo chown -R www-data:www-data /var/www/laravel-sms
sudo chmod -R 755 /var/www/laravel-sms
sudo chmod -R 775 /var/www/laravel-sms/storage
sudo chmod -R 775 /var/www/laravel-sms/bootstrap/cache
if [ -f .env ]; then
    sudo chmod 600 /var/www/laravel-sms/.env
fi
echo "✓ Permissions set"

echo ""
echo "Step 4: Database setup required..."
echo "Please create the database manually:"
echo ""
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

echo ""
echo "Step 5: Updating .env file..."
echo "Please edit .env file with your database credentials:"
echo "nano .env"
echo ""
read -p "Press Enter after you've updated .env file..."

echo ""
echo "Step 6: Running migrations..."
php artisan migrate --force
echo "✓ Migrations completed"

echo ""
echo "Step 7: Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✓ Configuration cached"

echo ""
echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "Next: Configure Nginx (see DEPLOYMENT_GUIDE.md)"
echo ""

