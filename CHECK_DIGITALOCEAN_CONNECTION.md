# DigitalOcean Connection Check Guide

**Purpose:** Verify if your DigitalOcean server is properly connected and ready for deployment

---

## 🔍 HOW TO CHECK CONNECTION

### **Method 1: Test SSH Connection (Manual)**

Open terminal/command prompt and test SSH:

```bash
ssh root@YOUR_DROPLET_IP
```

**If successful:**
- ✅ You can connect to the server
- ✅ SSH is working
- ✅ Server is accessible

**If failed:**
- ❌ Check IP address
- ❌ Check SSH key/password
- ❌ Check firewall settings
- ❌ Verify server is running

---

### **Method 2: Check GitHub Secrets**

Go to: `https://github.com/hoseagilnig/PNGMC/settings/secrets/actions`

**Required Secrets:**
- [ ] `DIGITALOCEAN_HOST` - Your droplet IP
- [ ] `DIGITALOCEAN_USER` - SSH username
- [ ] `DIGITALOCEAN_SSH_KEY` - Private SSH key

**If all 3 secrets exist:**
- ✅ GitHub Actions can connect
- ✅ Automated deployment is configured

**If missing:**
- ❌ Add the secrets (see `GITHUB_SECRETS_SETUP.md`)

---

### **Method 3: Test Deployment Workflow**

1. Go to: `https://github.com/hoseagilnig/PNGMC/actions`
2. Click **Deploy to DigitalOcean** workflow
3. Click **Run workflow** (manual trigger)
4. Watch the logs

**If successful:**
- ✅ Connection works
- ✅ Files are deployed
- ✅ Deployment completed

**If failed:**
- Check error messages in logs
- Verify GitHub secrets are correct
- Test SSH connection manually

---

### **Method 4: Check Server Status (On Server)**

SSH into your server and check:

```bash
# Check if web server is running
systemctl status apache2
# or
systemctl status nginx

# Check if PHP is installed
php -v

# Check if MySQL is running
systemctl status mysql

# Check web directory
ls -la /var/www/html

# Check .env file
ls -la /var/www/html/.env
```

---

## ✅ CONNECTION CHECKLIST

### **Basic Connectivity:**
- [ ] Can ping the server IP
- [ ] Can SSH into the server
- [ ] Server is running and accessible

### **GitHub Configuration:**
- [ ] GitHub secrets are configured
- [ ] SSH key is added to GitHub secrets
- [ ] Deployment workflow exists (`.github/workflows/deploy-digitalocean.yml`)

### **Server Configuration:**
- [ ] LAMP/LEMP stack installed
- [ ] Web directory exists (`/var/www/html`)
- [ ] `.env` file exists with database credentials
- [ ] File permissions are set correctly
- [ ] Database is created and accessible

---

## 🔧 TROUBLESHOOTING

### **Issue: Cannot SSH into server**

**Check:**
1. IP address is correct
2. Firewall allows SSH (port 22)
3. SSH key is correct
4. Server is running

**Test:**
```bash
ping YOUR_DROPLET_IP
ssh -v root@YOUR_DROPLET_IP
```

---

### **Issue: GitHub Actions deployment fails**

**Check:**
1. GitHub secrets are correct
2. SSH key format is correct (include BEGIN/END lines)
3. Server is accessible from internet
4. Firewall allows SSH

**Debug:**
- Check GitHub Actions logs
- Look for specific error messages
- Test SSH connection manually

---

### **Issue: "Permission denied" error**

**Fix:**
```bash
# On server, set proper permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod 600 /var/www/html/.env
```

---

## 📊 CONNECTION STATUS INDICATORS

### **✅ FULLY CONNECTED:**
- SSH works manually
- GitHub secrets configured
- Deployment workflow can connect
- Server is ready

### **⚠️ PARTIALLY CONNECTED:**
- SSH works manually
- GitHub secrets missing or incorrect
- Deployment workflow fails

### **❌ NOT CONNECTED:**
- Cannot SSH into server
- Server not accessible
- GitHub secrets not configured

---

## 🚀 QUICK TEST

**Fastest way to check:**

1. **Test SSH:**
   ```bash
   ssh root@YOUR_DROPLET_IP "echo 'Connection successful'"
   ```

2. **Check GitHub Secrets:**
   - Go to GitHub → Settings → Secrets
   - Verify all 3 secrets exist

3. **Test Deployment:**
   - Go to GitHub → Actions
   - Manually trigger "Deploy to DigitalOcean"
   - Check if it succeeds

---

## 📝 CURRENT STATUS

**To check your current status, I need:**

1. **Can you SSH into your DigitalOcean server?**
   - Yes → Connection is working
   - No → Need to fix SSH access

2. **Are GitHub secrets configured?**
   - Yes → Ready for automated deployment
   - No → Need to add secrets

3. **Has deployment been tested?**
   - Yes → Check GitHub Actions logs
   - No → Test deployment workflow

---

**Next Step:** Let me know which method you'd like to use, or I can help you check the GitHub secrets configuration!

