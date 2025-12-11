# Deployment Ready Checklist - DigitalOcean

**Status:** DigitalOcean Server Already Set Up ✅

---

## ✅ PRE-DEPLOYMENT CHECKLIST

### **1. Commit Current Security Fixes** ⚠️ REQUIRED

**Files to Commit:**
- ✅ `apply_continuing.php` - CSRF protection + SQL injection fixes
- ✅ `apply_school_leaver.php` - CSRF protection added
- ✅ `.github/workflows/deploy-digitalocean.yml` - Deployment workflow
- ✅ `LANDING_PAGE_FORMS_SECURITY.md` - Security documentation
- ✅ `SECRET_KEYS_DOCUMENTATION.md` - API keys documentation
- ✅ `DEPLOYMENT_STATUS_REPORT.md` - Status report
- ✅ `DIGITALOCEAN_DEPLOYMENT_GUIDE.md` - Deployment guide

**Note:** `logs/security.log` should NOT be committed (already in `.gitignore`)

---

### **2. Configure GitHub Secrets** ⚠️ REQUIRED

Go to: **GitHub Repository → Settings → Secrets and variables → Actions**

Add these secrets:

1. **`DIGITALOCEAN_HOST`**
   - Your droplet IP address (e.g., `123.45.67.89`)

2. **`DIGITALOCEAN_USER`**
   - SSH username (usually `root` or your custom user)

3. **`DIGITALOCEAN_SSH_KEY`**
   - Your private SSH key (entire content, including `-----BEGIN` and `-----END` lines)

4. **`DIGITALOCEAN_SSH_PORT`** (Optional)
   - SSH port (default: `22`)

**How to get SSH key:**
```bash
# On your local machine, if you have SSH access:
cat ~/.ssh/id_rsa

# Or generate a new one:
ssh-keygen -t rsa -b 4096 -C "github-actions-deploy"
# Then copy the public key to DigitalOcean:
ssh-copy-id -i ~/.ssh/id_rsa.pub root@YOUR_DROPLET_IP
```

---

### **3. Verify DigitalOcean Server Setup**

**On your DigitalOcean server, verify:**

- [ ] LAMP/LEMP stack installed (Apache/Nginx + MySQL + PHP 8.0+)
- [ ] Database created (`sms2_db`)
- [ ] Database user created with proper permissions
- [ ] Web directory exists (`/var/www/html`)
- [ ] `.env` file created with database credentials
- [ ] File permissions set correctly
- [ ] SSL certificate configured (if using HTTPS)
- [ ] Firewall configured (ports 22, 80, 443)

---

### **4. Prepare for First Deployment**

**Before first deployment, ensure:**

- [ ] `.env` file exists on server (with production credentials)
- [ ] Database is ready (run setup scripts if needed)
- [ ] Uploads directory exists: `mkdir -p /var/www/html/uploads`
- [ ] Logs directory exists: `mkdir -p /var/www/html/logs`

---

## 🚀 DEPLOYMENT STEPS

### **Step 1: Commit and Push Changes**

```bash
# Add all changes (except logs)
git add apply_continuing.php apply_school_leaver.php
git add .github/workflows/deploy-digitalocean.yml
git add LANDING_PAGE_FORMS_SECURITY.md SECRET_KEYS_DOCUMENTATION.md
git add DEPLOYMENT_STATUS_REPORT.md DIGITALOCEAN_DEPLOYMENT_GUIDE.md

# Commit
git commit -m "Add CSRF protection, fix SQL injection, and add DigitalOcean deployment workflow"

# Push to current branch
git push origin 2025-12-09-ec34-9b09b
```

### **Step 2: Merge to Main Branch**

```bash
# Switch to main branch
git checkout main

# Merge your changes
git merge 2025-12-09-ec34-9b09b

# Push to main (this will trigger deployment)
git push origin main
```

### **Step 3: Monitor Deployment**

1. Go to: **GitHub → Actions tab**
2. Watch the "Deploy to DigitalOcean" workflow
3. Check for any errors
4. Verify deployment completed successfully

### **Step 4: Verify Application**

After deployment:
- [ ] Access your website
- [ ] Test staff login
- [ ] Test student login
- [ ] Test form submissions
- [ ] Verify file uploads work
- [ ] Check error logs

---

## 🔧 POST-DEPLOYMENT TASKS

### **1. Run Database Setup (if first deployment)**

SSH into your server:

```bash
ssh root@YOUR_DROPLET_IP
cd /var/www/html

# Run database setup scripts
mysql -u sms2_user -p sms2_db < database/create_app_tables.sql
mysql -u sms2_user -p sms2_db < database/create_archive_tables.sql
```

### **2. Verify File Permissions**

```bash
cd /var/www/html
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 755 uploads/
chmod 600 .env
```

### **3. Test Application**

- [ ] Staff login works
- [ ] Student login works
- [ ] Application forms work
- [ ] File uploads work
- [ ] Database queries work
- [ ] No PHP errors in logs

---

## ⚠️ TROUBLESHOOTING

### **Deployment Fails - SSH Connection Error**

**Check:**
- SSH key is correct in GitHub secrets
- Droplet firewall allows SSH (port 22)
- SSH user has proper permissions
- Test SSH manually: `ssh root@YOUR_DROPLET_IP`

### **Deployment Fails - Permission Denied**

**Fix:**
```bash
# On server, ensure user has write access
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
```

### **Application Not Working After Deployment**

**Check:**
- `.env` file exists and has correct credentials
- Database connection works
- File permissions are correct
- Apache/Nginx is running
- PHP errors in logs: `tail -f /var/log/apache2/error.log`

---

## 📝 QUICK REFERENCE

### **GitHub Secrets Required:**
- `DIGITALOCEAN_HOST` - Server IP
- `DIGITALOCEAN_USER` - SSH username
- `DIGITALOCEAN_SSH_KEY` - Private SSH key
- `DIGITALOCEAN_SSH_PORT` - SSH port (optional, default: 22)

### **Deployment Triggers:**
- Push to `main` branch (automatic)
- Manual trigger via GitHub Actions UI

### **Deployment Target:**
- Server: DigitalOcean Droplet
- Directory: `/var/www/html`
- Excludes: `.git`, `.env`, `logs/`, `uploads/`

---

## ✅ READY TO DEPLOY?

**Checklist:**
- [x] DigitalOcean server is set up
- [ ] Security fixes committed
- [ ] GitHub secrets configured
- [ ] Ready to push to main branch

**Next Step:** Commit changes and configure GitHub secrets, then push to main!

