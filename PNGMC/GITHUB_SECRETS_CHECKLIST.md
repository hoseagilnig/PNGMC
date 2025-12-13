# GitHub Secrets Checklist

**Workflow File:** `.github/workflows/deploy-digitalocean.yml`

---

## ✅ VERIFIED: All Secrets Use `secrets.` Prefix

The workflow file correctly uses:
- ✅ `${{ secrets.DIGITALOCEAN_HOST }}`
- ✅ `${{ secrets.DIGITALOCEAN_USER }}`
- ✅ `${{ secrets.DIGITALOCEAN_SSH_KEY }}`
- ✅ `${{ secrets.DIGITALOCEAN_SSH_PORT }}`

---

## 🔐 REQUIRED GITHUB SECRETS

Go to: `https://github.com/hoseagilnig/PNGMC/settings/secrets/actions`

### **1. DIGITALOCEAN_HOST**
- **Value:** `134.199.174.78`
- **Required:** Yes

### **2. DIGITALOCEAN_USER**
- **Value:** `root` (or your SSH username)
- **Required:** Yes

### **3. DIGITALOCEAN_SSH_KEY**
- **Value:** Your private SSH key (entire content including BEGIN/END lines)
- **Required:** Yes
- **Format:**
  ```
  -----BEGIN RSA PRIVATE KEY-----
  MIIEpAIBAAKCAQEA...
  (many lines)
  ...
  -----END RSA PRIVATE KEY-----
  ```

### **4. DIGITALOCEAN_SSH_PORT**
- **Value:** `22` (default SSH port)
- **Required:** Optional (if not set, defaults to 22)
- **Note:** Only set this if using a non-standard port

---

## 📝 HOW TO ADD SECRETS

1. Go to: `https://github.com/hoseagilnig/PNGMC/settings/secrets/actions`
2. Click **"New repository secret"**
3. Enter the **Name** (e.g., `DIGITALOCEAN_HOST`)
4. Enter the **Value** (e.g., `134.199.174.78`)
5. Click **"Add secret"**
6. Repeat for all secrets

---

## ✅ VERIFY SECRETS ARE SET

After adding secrets, you can verify by:
1. Going to: `https://github.com/hoseagilnig/PNGMC/settings/secrets/actions`
2. You should see all 4 secrets listed

---

## 🚀 TEST DEPLOYMENT

Once secrets are set:

1. **Push to main branch:**
   ```bash
   git push origin main
   ```

2. **Check workflow:**
   - Go to: `https://github.com/hoseagilnig/PNGMC/actions`
   - You should see "Deploy to DigitalOcean" workflow running

3. **If it fails:**
   - Check the workflow logs
   - Verify all secrets are set correctly
   - Verify SSH key has access to the server

---

## 🔍 TROUBLESHOOTING

### **Error: "Secret not found"**
- Make sure the secret name matches exactly (case-sensitive)
- Make sure you added it to the correct repository

### **Error: "Permission denied"**
- Verify SSH key is correct
- Verify SSH key has access to the server
- Test SSH connection manually: `ssh root@134.199.174.78`

### **Error: "Connection refused"**
- Verify `DIGITALOCEAN_HOST` is correct: `134.199.174.78`
- Verify `DIGITALOCEAN_SSH_PORT` is correct (usually `22`)
- Check if server firewall allows SSH connections

---

**Repository:** `https://github.com/hoseagilnig/PNGMC`  
**Server IP:** `134.199.174.78`

