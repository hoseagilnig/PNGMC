# Fix SSH Key Secret - GitHub Actions

**Error:** `ssh: no key found` and `unable to authenticate`

**Cause:** The `DIGITALOCEAN_SSH_KEY` secret is not formatted correctly.

---

## 🔧 FIX: Update SSH Key Secret

### **Step 1: Get Your SSH Private Key**

**On your local machine, run:**

```bash
# Windows (PowerShell)
cat ~/.ssh/id_rsa

# Or if using Git Bash
cat ~/.ssh/id_rsa
```

**Copy the ENTIRE output**, including:
- `-----BEGIN RSA PRIVATE KEY-----` (first line)
- All the key data (middle lines)
- `-----END RSA PRIVATE KEY-----` (last line)

---

### **Step 2: Format the Key Correctly**

The key must be:
- ✅ **Complete** (BEGIN and END lines included)
- ✅ **No extra spaces** at the start/end
- ✅ **No line breaks** between BEGIN/END and key data
- ✅ **All lines included** (even if it's very long)

**Correct Format:**
```
-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA...
(many lines of key data)
...
-----END RSA PRIVATE KEY-----
```

---

### **Step 3: Update GitHub Secret**

1. Go to: `https://github.com/hoseagilnig/PNGMC/settings/secrets/actions`
2. Find **`DIGITALOCEAN_SSH_KEY`**
3. Click **"Update"** (pencil icon)
4. **Delete the old value completely**
5. **Paste the complete private key** (from Step 1)
6. **Make sure:**
   - Starts with `-----BEGIN`
   - Ends with `-----END`
   - No extra spaces before/after
7. Click **"Update secret"**

---

### **Step 4: Test Again**

After updating the secret:

1. Go to: `https://github.com/hoseagilnig/PNGMC/actions`
2. Click **"Deploy to DigitalOcean"** workflow
3. Click **"Run workflow"** (top right)
4. Select branch: `main`
5. Click **"Run workflow"**

---

## 🔍 VERIFY SSH KEY FORMAT

**Common Issues:**

❌ **Missing BEGIN/END lines:**
```
MIIEpAIBAAKCAQEA...
(no BEGIN/END)
```

✅ **Correct:**
```
-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA...
-----END RSA PRIVATE KEY-----
```

---

❌ **Extra spaces:**
```
  -----BEGIN RSA PRIVATE KEY-----
  (spaces before)
```

✅ **Correct:**
```
-----BEGIN RSA PRIVATE KEY-----
(no leading spaces)
```

---

❌ **Wrong key type:**
- Using public key (`id_rsa.pub`) instead of private key (`id_rsa`)
- Using OpenSSH format instead of RSA format

✅ **Use private key:**
- File: `~/.ssh/id_rsa` (private key)
- NOT: `~/.ssh/id_rsa.pub` (public key)

---

## 🧪 TEST SSH KEY MANUALLY

**Test if your SSH key works:**

```bash
ssh -i ~/.ssh/id_rsa root@134.199.174.78
```

If this works, the key is correct. Copy it to GitHub secrets.

---

## 📝 QUICK FIX CHECKLIST

- [ ] Get private key: `cat ~/.ssh/id_rsa`
- [ ] Copy ENTIRE output (BEGIN to END)
- [ ] Go to GitHub secrets
- [ ] Update `DIGITALOCEAN_SSH_KEY`
- [ ] Paste complete key (no extra spaces)
- [ ] Save secret
- [ ] Test workflow again

---

## 🚨 IF YOU DON'T HAVE SSH KEY

**Generate a new one:**

```bash
# Generate SSH key
ssh-keygen -t rsa -b 4096 -C "github-actions-deploy"

# Save as: ~/.ssh/github_deploy_key
# (Press Enter for no passphrase, or set one)

# Copy private key
cat ~/.ssh/github_deploy_key

# Copy public key to server
ssh-copy-id -i ~/.ssh/github_deploy_key.pub root@134.199.174.78
```

Then use the **private key** (`github_deploy_key`) in GitHub secrets.

---

**The SSH key must be the complete private key with BEGIN/END lines!**

