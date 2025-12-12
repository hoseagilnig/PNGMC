# Final SSH Key Fix - Step by Step

**The key works manually, so this is 100% a GitHub secrets formatting issue.**

---

## 🔍 VERIFY CURRENT SETUP

✅ **SSH key works:** Tested manually - connection successful  
✅ **Public key on server:** Verified in `~/.ssh/authorized_keys`  
✅ **Server accessible:** Can connect via SSH  
❌ **GitHub Actions:** Authentication failing

**Conclusion:** The key format in GitHub secrets is wrong.

---

## 📋 EXACT STEPS TO FIX

### **Step 1: Open Key File in Text Editor**

**DO NOT use regular Notepad!** Use:
- **Notepad++** (recommended)
- **VS Code**
- **Sublime Text**

**File location:** `C:\Users\ecdis\.ssh\github_deploy_rsa`

---

### **Step 2: Select and Copy**

1. **Open the file** in your text editor
2. **Press Ctrl+A** (select all)
3. **Press Ctrl+C** (copy)
4. **DO NOT** add any spaces or modify anything

---

### **Step 3: Update GitHub Secret**

1. Go to: `https://github.com/hoseagilnig/PNGMC/settings/secrets/actions`
2. Find **`DIGITALOCEAN_SSH_KEY`**
3. Click **"Update"** (pencil icon on the right)
4. **IMPORTANT:** Click inside the text box and:
   - Press **Ctrl+A** (select all)
   - Press **Delete** (remove everything)
5. **Paste** the key (Ctrl+V)
6. **DO NOT** click anywhere else - just paste and save
7. Click **"Update secret"** button

---

### **Step 4: Verify the Secret**

After updating, the secret should show:
- **Name:** `DIGITALOCEAN_SSH_KEY`
- **Last updated:** Just now
- **Value:** (hidden, but should be there)

---

## ⚠️ CRITICAL: Common Mistakes

### **Mistake 1: Extra Spaces**
❌ **Wrong:**
```
  -----BEGIN OPENSSH PRIVATE KEY-----
```

✅ **Correct:**
```
-----BEGIN OPENSSH PRIVATE KEY-----
```

---

### **Mistake 2: Missing Lines**
❌ **Wrong:** Copying only part of the key

✅ **Correct:** Copy ALL lines from BEGIN to END

---

### **Mistake 3: Line Break Issues**
❌ **Wrong:** Copying from email/chat (adds extra characters)

✅ **Correct:** Copy directly from the file

---

### **Mistake 4: Using Wrong Key**
❌ **Wrong:** Using `id_ed25519` (old key)

✅ **Correct:** Using `github_deploy_rsa` (new RSA key)

---

## 🧪 VERIFY KEY FORMAT

**The key should:**
- Start with: `-----BEGIN OPENSSH PRIVATE KEY-----`
- End with: `-----END OPENSSH PRIVATE KEY-----`
- Have approximately **27 lines** total
- No extra spaces before first line
- No extra spaces after last line
- All lines between BEGIN and END included

---

## 📝 QUICK CHECKLIST

Before testing:
- [ ] Opened `C:\Users\ecdis\.ssh\github_deploy_rsa` in Notepad++
- [ ] Selected ALL (Ctrl+A)
- [ ] Copied ALL (Ctrl+C)
- [ ] Went to GitHub secrets page
- [ ] Updated `DIGITALOCEAN_SSH_KEY`
- [ ] Deleted old value completely
- [ ] Pasted new value (Ctrl+V)
- [ ] Saved secret
- [ ] Verified no extra spaces

---

## 🚀 TEST WORKFLOW

After updating:

1. **Go to:** `https://github.com/hoseagilnig/PNGMC/actions`
2. **Click:** "Deploy to DigitalOcean" workflow
3. **Click:** "Run workflow" (top right)
4. **Select branch:** `main`
5. **Click:** "Run workflow" (green button)

**Expected result:** ✅ Green checkmark (success)

---

## 🔧 IF STILL FAILING

**Try this alternative approach:**

1. **Delete the secret completely**
2. **Wait 30 seconds**
3. **Create new secret:**
   - Name: `DIGITALOCEAN_SSH_KEY`
   - Value: Paste the key
4. **Save**

Sometimes GitHub caches the old (wrong) format.

---

## 📞 VERIFICATION COMMANDS

**Test key locally (should work):**
```powershell
ssh -i C:\Users\ecdis\.ssh\github_deploy_rsa root@134.199.174.78 "echo 'Success'"
```

**Check public key on server:**
```powershell
ssh root@134.199.174.78 "grep 'github-actions-deploy' ~/.ssh/authorized_keys"
```

Both should work. If they do, the issue is 100% GitHub secrets formatting.

---

**The key file is at: `C:\Users\ecdis\.ssh\github_deploy_rsa`**

**Copy it EXACTLY as-is into GitHub secrets!**

