# GitHub Secrets Setup Guide - DigitalOcean Deployment

**Quick Reference:** How to add GitHub secrets for automated deployment

---

## 📍 WHERE TO ADD SECRETS

1. Go to your GitHub repository: `https://github.com/hoseagilnig/PNGMC`
2. Click **Settings** (top menu)
3. Click **Secrets and variables** → **Actions** (left sidebar)
4. Click **New repository secret** button

---

## 🔑 REQUIRED SECRETS

### **1. DIGITALOCEAN_HOST**

**Name:** `DIGITALOCEAN_HOST`

**Value:** Your DigitalOcean droplet IP address

**Example:**
```
123.45.67.89
```

**How to find:**
- Go to DigitalOcean dashboard
- Click on your droplet
- Copy the IP address shown

---

### **2. DIGITALOCEAN_USER**

**Name:** `DIGITALOCEAN_USER`

**Value:** SSH username for your droplet

**Common values:**
- `root` (default for most droplets)
- Or your custom username if you created one

**Example:**
```
root
```

---

### **3. DIGITALOCEAN_SSH_KEY**

**Name:** `DIGITALOCEAN_SSH_KEY`

**Value:** Your private SSH key (entire content)

**Important:** Include the BEGIN and END lines!

**Example format:**
```
-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA...
(many lines of key data)
...
-----END RSA PRIVATE KEY-----
```

**How to get your SSH key:**

**Option A: If you already have SSH access:**
```bash
# On your local machine
cat ~/.ssh/id_rsa
# Copy the entire output (including BEGIN/END lines)
```

**Option B: Generate a new key for GitHub Actions:**
```bash
# Generate new key
ssh-keygen -t rsa -b 4096 -C "github-actions-deploy"
# Save as: ~/.ssh/github_deploy_key

# Copy private key (for GitHub secret)
cat ~/.ssh/github_deploy_key

# Copy public key to DigitalOcean server
ssh-copy-id -i ~/.ssh/github_deploy_key.pub root@134.199.174.78
```

---

### **4. DIGITALOCEAN_SSH_PORT** (Optional)

**Name:** `DIGITALOCEAN_SSH_PORT`

**Value:** SSH port number

**Default:** `22` (if not set, workflow uses port 22)

**Example:**
```
22
```

**Note:** Only set this if your SSH port is different from 22.

---

## ✅ VERIFICATION

After adding all secrets:

1. Go to **Settings → Secrets and variables → Actions**
2. Verify you see all 3-4 secrets listed:
   - ✅ `DIGITALOCEAN_HOST`
   - ✅ `DIGITALOCEAN_USER`
   - ✅ `DIGITALOCEAN_SSH_KEY`
   - ✅ `DIGITALOCEAN_SSH_PORT` (optional)

---

## 🧪 TEST DEPLOYMENT

### **Option 1: Manual Trigger (Recommended for first test)**

1. Go to **Actions** tab in GitHub
2. Click **Deploy to DigitalOcean** workflow
3. Click **Run workflow** button (right side)
4. Select branch: `main`
5. Click **Run workflow**
6. Watch the deployment progress

### **Option 2: Push to Main Branch**

```bash
git checkout main
git merge 2025-12-09-ec34-9b09b
git push origin main
```

This will automatically trigger deployment.

---

## 🔍 TROUBLESHOOTING

### **Error: "Host key verification failed"**

**Solution:** Add server to known hosts first:
```bash
ssh-keyscan -H YOUR_DROPLET_IP >> ~/.ssh/known_hosts
```

### **Error: "Permission denied (publickey)"**

**Solution:** 
- Verify SSH key is correct in GitHub secrets
- Ensure public key is added to DigitalOcean server
- Test SSH manually: `ssh root@YOUR_DROPLET_IP`

### **Error: "Connection refused"**

**Solution:**
- Check droplet firewall allows SSH (port 22)
- Verify droplet is running
- Check IP address is correct

---

## 📝 QUICK CHECKLIST

- [ ] Added `DIGITALOCEAN_HOST` secret
- [ ] Added `DIGITALOCEAN_USER` secret
- [ ] Added `DIGITALOCEAN_SSH_KEY` secret
- [ ] Added `DIGITALOCEAN_SSH_PORT` secret (if needed)
- [ ] Verified all secrets are saved
- [ ] Ready to test deployment

---

**Once secrets are configured, you're ready to deploy!** 🚀

