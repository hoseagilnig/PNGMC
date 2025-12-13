# Troubleshoot GitHub Secrets Not Detected

**Issue:** Secrets are added but GitHub Actions says they're not found.

---

## ✅ VERIFY SECRET NAMES (Case-Sensitive!)

GitHub secrets are **case-sensitive**. Check exact spelling:

**Required Secrets:**
- ✅ `DIGITALOCEAN_HOST` (all caps)
- ✅ `DIGITALOCEAN_USER` (all caps)
- ✅ `DIGITALOCEAN_SSH_KEY` (all caps)
- ✅ `DIGITALOCEAN_SSH_PORT` (all caps)

**Common Mistakes:**
- ❌ `digitalocean_host` (lowercase)
- ❌ `DIGITALOCEAN-HOST` (hyphen instead of underscore)
- ❌ `DigitalOcean_Host` (mixed case)

---

## 🔍 CHECK SECRET VALUES

### **1. DIGITALOCEAN_HOST**
- **Should be:** `134.199.174.78`
- **No spaces, no quotes**

### **2. DIGITALOCEAN_USER**
- **Should be:** `root`
- **No spaces, no quotes**

### **3. DIGITALOCEAN_SSH_KEY**
- **Must include:** BEGIN and END lines
- **Format:**
  ```
  -----BEGIN RSA PRIVATE KEY-----
  MIIEpAIBAAKCAQEA...
  (many lines)
  ...
  -----END RSA PRIVATE KEY-----
  ```
- **No extra spaces at start/end**

### **4. DIGITALOCEAN_SSH_PORT**
- **Should be:** `22`
- **Just the number, no quotes**

---

## 🔧 TROUBLESHOOTING STEPS

### **Step 1: Verify Secret Names Match**

Go to: `https://github.com/hoseagilnig/PNGMC/settings/secrets/actions`

Check that secret names are **exactly**:
- `DIGITALOCEAN_HOST`
- `DIGITALOCEAN_USER`
- `DIGITALOCEAN_SSH_KEY`
- `DIGITALOCEAN_SSH_PORT`

---

### **Step 2: Check Workflow Branch**

The workflow triggers on `main` branch. Make sure:

1. **You're pushing to `main`:**
   ```bash
   git branch --show-current
   # Should show: main
   ```

2. **If on different branch, merge to main:**
   ```bash
   git checkout main
   git merge your-branch-name
   git push origin main
   ```

---

### **Step 3: Test Workflow Manually**

1. Go to: `https://github.com/hoseagilnig/PNGMC/actions`
2. Click **"Deploy to DigitalOcean"** workflow
3. Click **"Run workflow"** button (top right)
4. Select branch: `main`
5. Click **"Run workflow"**

This will test if secrets are accessible.

---

### **Step 4: Check Workflow Logs**

If workflow fails:

1. Go to: `https://github.com/hoseagilnig/PNGMC/actions`
2. Click on the failed workflow run
3. Click on **"Deploy to DigitalOcean via SSH"** step
4. Look for error messages

**Common Errors:**
- `Secret DIGITALOCEAN_HOST not found` → Secret name typo
- `Permission denied` → SSH key issue
- `Connection refused` → Host/port issue

---

### **Step 5: Re-add Secrets (If Needed)**

If secrets still don't work:

1. **Delete existing secrets:**
   - Go to: `https://github.com/hoseagilnig/PNGMC/settings/secrets/actions`
   - Click on each secret
   - Click **"Delete"**

2. **Re-add them one by one:**
   - Click **"New repository secret"**
   - **Name:** `DIGITALOCEAN_HOST` (exact, case-sensitive)
   - **Value:** `134.199.174.78`
   - Click **"Add secret"**
   - Repeat for all 4 secrets

---

## 🧪 TEST SECRETS LOCALLY

You can't test secrets locally, but you can verify the workflow syntax:

```bash
# Check workflow file syntax
cat .github/workflows/deploy-digitalocean.yml

# Verify all secrets are referenced correctly
grep -n "secrets\." .github/workflows/deploy-digitalocean.yml
```

---

## ✅ QUICK CHECKLIST

- [ ] All 4 secrets added to GitHub
- [ ] Secret names are **exactly** as shown (case-sensitive)
- [ ] Workflow file uses `secrets.` prefix
- [ ] Pushing to `main` branch
- [ ] SSH key includes BEGIN/END lines
- [ ] No extra spaces in secret values

---

## 🚨 STILL NOT WORKING?

**Try this:**

1. **Delete all 4 secrets**
2. **Wait 1 minute**
3. **Re-add them one by one** (exact names, no typos)
4. **Test workflow manually** (Run workflow button)

**Or contact GitHub support** if issue persists.

---

**Repository:** `https://github.com/hoseagilnig/PNGMC`  
**Secrets Page:** `https://github.com/hoseagilnig/PNGMC/settings/secrets/actions`

