# DigitalOcean Server Information

**Server IP:** `134.199.174.78`  
**Status:** ✅ Configured

---

## 🔧 QUICK CONNECTION COMMANDS

### **SSH Connection:**

```bash
ssh root@134.199.174.78
```

### **Using Host Alias (after configuring SSH config):**

```bash
ssh digitalocean
```

---

## 📋 SSH CONFIGURATION

Add this to your `~/.ssh/config` file (or `C:\Users\YourName\.ssh\config` on Windows):

```
Host digitalocean
    HostName 134.199.174.78
    User root
    IdentityFile ~/.ssh/id_rsa
    Port 22
    ServerAliveInterval 60
    ServerAliveCountMax 3
```

---

## 🌐 WEB ACCESS

**Your website should be accessible at:**
- `http://134.199.174.78` (if HTTP is enabled)
- `https://yourdomain.com` (if domain is configured)

---

## 🔐 GITHUB SECRETS CONFIGURATION

For automated deployment via GitHub Actions, add these secrets:

**Repository:** `https://github.com/hoseagilnig/PNGMC/settings/secrets/actions`

1. **`DIGITALOCEAN_HOST`**
   - Value: `134.199.174.78`

2. **`DIGITALOCEAN_USER`**
   - Value: `root` (or your SSH username)

3. **`DIGITALOCEAN_SSH_KEY`**
   - Value: Your private SSH key content

4. **`DIGITALOCEAN_SSH_PORT`** (Optional)
   - Value: `22`

---

## ✅ QUICK TEST

### **Test SSH Connection:**

```bash
ssh root@134.199.174.78 "echo 'Connection successful!'"
```

### **Test Ping:**

```bash
ping 134.199.174.78
```

### **Test Port 22 (SSH):**

```bash
# Windows PowerShell
Test-NetConnection -ComputerName 134.199.174.78 -Port 22

# Linux/Mac
nc -zv 134.199.174.78 22
```

---

## 📝 DEPLOYMENT CHECKLIST

- [ ] SSH connection tested: `ssh root@134.199.174.78`
- [ ] GitHub secrets configured with IP: `134.199.174.78`
- [ ] SSH config file updated with IP
- [ ] Cursor Remote SSH configured
- [ ] `.env` file created on server with database credentials
- [ ] Database connection tested
- [ ] Website accessible

---

## 🔗 USEFUL LINKS

- **DigitalOcean Dashboard:** https://cloud.digitalocean.com/
- **GitHub Repository:** https://github.com/hoseagilnig/PNGMC
- **Deployment Workflow:** `.github/workflows/deploy-digitalocean.yml`

---

**Server IP:** `134.199.174.78` ✅

