# Update Linux Server - Quick Guide

## Steps to Update Your Linux Server

### 1. SSH into your Linux server
```bash
ssh username@your-server-ip
```

### 2. Navigate to your project directory
```bash
cd /var/www/html/pngmc
# OR wherever your project is located
```

### 3. Pull the latest changes from Git
```bash
# Make sure you're on the main branch
git checkout main

# Pull the latest changes
git pull origin main
```

### 4. If you get permission errors, use sudo:
```bash
sudo git pull origin main
```

### 5. Set proper permissions (if needed)
```bash
sudo chown -R www-data:www-data /var/www/html/pngmc
sudo chmod -R 755 /var/www/html/pngmc
```

### 6. Restart Apache (if needed)
```bash
sudo systemctl restart apache2
# OR
sudo service apache2 restart
```

### 7. Clear browser cache
- Press `Ctrl + Shift + R` (or `Cmd + Shift + R` on Mac) to hard refresh
- Or clear browser cache manually

## What's Been Updated

✅ **Dashboard Background** - Now matches landing page with deep blue (#1a3a5c) and glassmorphism cards
✅ **Header Layout** - Logo on left, user dropdown on right
✅ **Video Assets** - Storyboard and HTML animation prototype added

## Verify the Update

1. Check the dashboard background - should be deep blue with circular patterns
2. Check header layout - logo should be on left, login/user dropdown on right
3. Check that logout button is visible when dropdown is opened

## Troubleshooting

If you encounter issues:

1. **Git pull fails:**
   ```bash
   git fetch origin
   git reset --hard origin/main
   ```

2. **Permission denied:**
   ```bash
   sudo chown -R $USER:$USER /var/www/html/pngmc
   ```

3. **Apache not restarting:**
   ```bash
   sudo systemctl status apache2
   sudo journalctl -xe
   ```

## Quick One-Liner (if you're already in the project directory)
```bash
sudo git pull origin main && sudo systemctl restart apache2
```

