# Deploy CSS Changes to DigitalOcean

**Changes Made:** Landing page CSS fixes (removed top spacing, stats visible immediately)

---

## 🚀 AUTOMATED DEPLOYMENT (Recommended)

### **Option 1: Merge to Main Branch (Triggers Auto-Deploy)**

If your GitHub Actions workflow is set to deploy from `main` branch:

```bash
# Switch to main branch
git checkout main

# Merge your changes
git merge 2025-12-09-ec34-9b09b

# Push to main (this will trigger GitHub Actions)
git push origin main
```

**Check deployment status:**
- Go to: `https://github.com/hoseagilnig/PNGMC/actions`
- Look for "Deploy to DigitalOcean" workflow

---

### **Option 2: Manual Deployment via SSH**

If you want to deploy immediately without waiting for GitHub Actions:

```bash
# 1. Connect to DigitalOcean server
ssh root@134.199.174.78

# 2. Navigate to web root
cd /var/www/html

# 3. Pull latest changes (if using git on server)
git pull origin main
# OR manually copy the file:
```

**Or copy file directly:**

```bash
# From your local machine
scp css/sms_styles.css root@134.199.174.78:/var/www/html/css/sms_styles.css
```

---

### **Option 3: Update GitHub Actions Workflow**

If you want to deploy from your current branch, update `.github/workflows/deploy-digitalocean.yml`:

Change:
```yaml
branches:
  - main
```

To:
```yaml
branches:
  - main
  - 2025-12-09-ec34-9b09b
```

Then push your branch again.

---

## ✅ VERIFY DEPLOYMENT

After deployment, check your DigitalOcean site:

1. **Visit:** `http://134.199.174.78` (or your domain)
2. **Check:**
   - ✅ No space above "Welcome to PNG Maritime College"
   - ✅ Statistics (1000+, 50+, 24/7) visible immediately
   - ✅ Labels (Students, Programs, Support) visible immediately

---

## 🔧 TROUBLESHOOTING

**If changes don't appear:**

1. **Clear browser cache:**
   - Press `Ctrl + Shift + R` (Windows) or `Cmd + Shift + R` (Mac)

2. **Check file permissions on server:**
   ```bash
   ssh root@134.199.174.78
   ls -la /var/www/html/css/sms_styles.css
   chmod 644 /var/www/html/css/sms_styles.css
   ```

3. **Check if file was deployed:**
   ```bash
   ssh root@134.199.174.78
   grep -n "padding: 20px 20px 120px" /var/www/html/css/sms_styles.css
   # Should show line 106
   ```

---

## 📝 QUICK DEPLOY COMMAND

**Fastest way (if you have SSH access):**

```bash
scp css/sms_styles.css root@134.199.174.78:/var/www/html/css/sms_styles.css
```

**Then verify:**
```bash
ssh root@134.199.174.78 "grep 'padding: 20px' /var/www/html/css/sms_styles.css"
```

---

**Your DigitalOcean Server IP:** `134.199.174.78` ✅

