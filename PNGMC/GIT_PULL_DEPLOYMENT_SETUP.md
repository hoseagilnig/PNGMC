# Git Pull Deployment Setup

**Updated Workflow:** `.github/workflows/deploy-digitalocean.yml` now uses `git pull` instead of file copying.

---

## ⚠️ PREREQUISITE: Server Must Have Git Repository

For the new workflow to work, your DigitalOcean server needs to have the repository cloned.

---

## 🔧 SETUP INSTRUCTIONS

### **Step 1: Clone Repository on DigitalOcean Server**

SSH into your server and clone the repository:

```bash
ssh root@134.199.174.78

# Navigate to web root
cd /var/www/html

# Backup existing files (if any)
mkdir -p /root/backup
cp -r * /root/backup/ 2>/dev/null || true

# Clone the repository
git clone https://github.com/hoseagilnig/PNGMC.git temp_repo

# Move files from temp_repo to current directory
mv temp_repo/* temp_repo/.* . 2>/dev/null || true
rmdir temp_repo

# Set proper permissions
chmod 644 .env 2>/dev/null || true
chmod -R 755 uploads/ 2>/dev/null || true
```

---

### **Step 2: Configure Git on Server (if needed)**

```bash
cd /var/www/html

# Set git config (if not already set)
git config user.name "Deployment Bot"
git config user.email "deploy@pngmc.ac.pg"

# Verify remote is set
git remote -v
# Should show: origin  https://github.com/hoseagilnig/PNGMC.git
```

---

### **Step 3: Set Up SSH Key for Git (Recommended)**

**Option A: Use HTTPS with Personal Access Token (Easier)**

```bash
cd /var/www/html

# Update remote to use HTTPS
git remote set-url origin https://github.com/hoseagilnig/PNGMC.git

# When git pull asks for credentials, use:
# Username: your-github-username
# Password: your-personal-access-token (not your GitHub password)
```

**Option B: Use SSH Key (More Secure)**

1. Generate SSH key on server:
```bash
ssh-keygen -t rsa -b 4096 -C "deploy@pngmc"
# Save as: /root/.ssh/github_deploy_key
```

2. Add public key to GitHub:
```bash
cat /root/.ssh/github_deploy_key.pub
# Copy the output and add it to GitHub: Settings → SSH and GPG keys
```

3. Update remote URL:
```bash
cd /var/www/html
git remote set-url origin git@github.com:hoseagilnig/PNGMC.git
```

---

### **Step 4: Test Git Pull Manually**

```bash
cd /var/www/html
git pull origin main
```

If successful, you're ready! If you get authentication errors, see troubleshooting below.

---

## ✅ WORKFLOW BENEFITS

**New Workflow Advantages:**
- ✅ Faster deployments (only changed files)
- ✅ Better version control
- ✅ Easier rollback (`git reset --hard HEAD~1`)
- ✅ Cleaner deployment process

---

## 🔍 TROUBLESHOOTING

### **Error: "Permission denied (publickey)"**

**Solution:** Set up SSH key (see Step 3, Option B) or use HTTPS with token.

---

### **Error: "Authentication failed"**

**Solution:** 
1. Use Personal Access Token instead of password
2. GitHub → Settings → Developer settings → Personal access tokens → Generate new token
3. Give it `repo` scope
4. Use token as password when git pull asks

---

### **Error: "Not a git repository"**

**Solution:** The directory isn't a git repo. Run Step 1 to clone it.

---

### **Error: "Updates were rejected"**

**Solution:** 
```bash
cd /var/www/html
git fetch origin
git reset --hard origin/main
```

---

## 📝 QUICK SETUP COMMAND

**One-liner setup (if server is empty):**

```bash
ssh root@134.199.174.78 "cd /var/www/html && rm -rf * .* 2>/dev/null; git clone https://github.com/hoseagilnig/PNGMC.git . && chmod 600 .env 2>/dev/null; chmod -R 755 uploads/ 2>/dev/null; echo 'Repository cloned successfully!'"
```

---

## 🚀 AFTER SETUP

Once the repository is cloned on the server:

1. **Push to main branch:**
   ```bash
   git push origin main
   ```

2. **GitHub Actions will automatically:**
   - Detect the push
   - SSH into your server
   - Run `git pull origin main`
   - Set file permissions
   - Reload Apache

3. **Check deployment:**
   - Go to: `https://github.com/hoseagilnig/PNGMC/actions`
   - See "Deploy to DigitalOcean" workflow running

---

**Server IP:** `134.199.174.78`  
**Repository:** `https://github.com/hoseagilnig/PNGMC`

