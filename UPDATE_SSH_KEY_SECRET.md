# Update SSH Key Secret in GitHub

**Found your SSH key:** `id_ed25519` (Ed25519 format)

---

## 📋 STEPS TO UPDATE GITHUB SECRET

### **Step 1: Copy Your Private Key**

I'll read your key file and show you what to copy.

---

### **Step 2: Update GitHub Secret**

1. Go to: `https://github.com/hoseagilnig/PNGMC/settings/secrets/actions`
2. Find **`DIGITALOCEAN_SSH_KEY`**
3. Click **"Update"** (pencil icon)
4. **Delete the old value completely**
5. **Paste the complete private key** (from below)
6. **Make sure:**
   - Starts with `-----BEGIN OPENSSH PRIVATE KEY-----` or `-----BEGIN PRIVATE KEY-----`
   - Ends with `-----END OPENSSH PRIVATE KEY-----` or `-----END PRIVATE KEY-----`
   - No extra spaces before/after
7. Click **"Update secret"**

---

### **Step 3: Verify Key Works on Server**

Make sure your public key is on the DigitalOcean server:

```bash
# Check if public key is on server
ssh root@134.199.174.78 "cat ~/.ssh/authorized_keys | grep -i ed25519"
```

If not found, copy it:
```bash
# Copy public key to server
type C:\Users\ecdis\.ssh\id_ed25519.pub | ssh root@134.199.174.78 "cat >> ~/.ssh/authorized_keys"
```

---

### **Step 4: Test Workflow**

After updating the secret:

1. Go to: `https://github.com/hoseagilnig/PNGMC/actions`
2. Click **"Deploy to DigitalOcean"**
3. Click **"Run workflow"**
4. Select branch: `main`
5. Click **"Run workflow"**

---

**The key will be shown below - copy it completely!**

