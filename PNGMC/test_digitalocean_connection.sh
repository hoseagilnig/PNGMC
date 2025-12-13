#!/bin/bash
# DigitalOcean Connection Test Script
# This script helps verify if your DigitalOcean server is accessible

echo "=========================================="
echo "DigitalOcean Connection Test"
echo "=========================================="
echo ""

# Check if SSH is available
if ! command -v ssh &> /dev/null; then
    echo "❌ SSH command not found. Please install OpenSSH client."
    exit 1
fi

echo "This script will help you test your DigitalOcean connection."
echo ""
echo "To use this script, you need:"
echo "  1. Your DigitalOcean droplet IP address"
echo "  2. SSH username (usually 'root')"
echo "  3. SSH key or password"
echo ""
read -p "Enter your DigitalOcean droplet IP: " DROPLET_IP
read -p "Enter SSH username (default: root): " SSH_USER
SSH_USER=${SSH_USER:-root}

echo ""
echo "Testing connection to $SSH_USER@$DROPLET_IP..."
echo ""

# Test basic connectivity
echo "1. Testing ping..."
if ping -c 1 -W 2 $DROPLET_IP &> /dev/null; then
    echo "   ✅ Server is reachable"
else
    echo "   ❌ Server is not reachable (ping failed)"
    echo "   Check if the IP address is correct and server is running"
    exit 1
fi

# Test SSH connection
echo "2. Testing SSH connection..."
if ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no $SSH_USER@$DROPLET_IP "echo 'SSH connection successful'" &> /dev/null; then
    echo "   ✅ SSH connection successful"
    
    # Test if web directory exists
    echo "3. Checking web directory..."
    if ssh $SSH_USER@$DROPLET_IP "test -d /var/www/html" &> /dev/null; then
        echo "   ✅ Web directory exists: /var/www/html"
    else
        echo "   ⚠️  Web directory not found: /var/www/html"
        echo "   You may need to create it: mkdir -p /var/www/html"
    fi
    
    # Test if .env file exists
    echo "4. Checking .env file..."
    if ssh $SSH_USER@$DROPLET_IP "test -f /var/www/html/.env" &> /dev/null; then
        echo "   ✅ .env file exists"
    else
        echo "   ⚠️  .env file not found"
        echo "   You need to create it with database credentials"
    fi
    
    # Check PHP version
    echo "5. Checking PHP installation..."
    PHP_VERSION=$(ssh $SSH_USER@$DROPLET_IP "php -v 2>/dev/null | head -n 1" 2>/dev/null)
    if [ ! -z "$PHP_VERSION" ]; then
        echo "   ✅ PHP installed: $PHP_VERSION"
    else
        echo "   ❌ PHP not found"
    fi
    
    # Check MySQL
    echo "6. Checking MySQL..."
    if ssh $SSH_USER@$DROPLET_IP "mysql --version &>/dev/null" &> /dev/null; then
        echo "   ✅ MySQL is installed"
    else
        echo "   ⚠️  MySQL not found or not accessible"
    fi
    
    # Check Apache/Nginx
    echo "7. Checking web server..."
    if ssh $SSH_USER@$DROPLET_IP "systemctl is-active --quiet apache2" &> /dev/null; then
        echo "   ✅ Apache is running"
    elif ssh $SSH_USER@$DROPLET_IP "systemctl is-active --quiet nginx" &> /dev/null; then
        echo "   ✅ Nginx is running"
    else
        echo "   ⚠️  Web server may not be running"
    fi
    
    echo ""
    echo "=========================================="
    echo "✅ Connection test completed!"
    echo "=========================================="
    echo ""
    echo "Next steps:"
    echo "  1. Ensure .env file is configured on server"
    echo "  2. Add GitHub secrets for automated deployment"
    echo "  3. Push to main branch to trigger deployment"
    
else
    echo "   ❌ SSH connection failed"
    echo ""
    echo "Possible issues:"
    echo "  - SSH key not configured"
    echo "  - Firewall blocking port 22"
    echo "  - Wrong username or IP address"
    echo "  - Server is not running"
    echo ""
    echo "Try connecting manually:"
    echo "  ssh $SSH_USER@$DROPLET_IP"
    exit 1
fi

