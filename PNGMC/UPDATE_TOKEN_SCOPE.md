# ⚠️ IMPORTANT: Update Your GitHub Token

**Your token was shared and needs the `workflow` scope to push workflow files.**

---

## 🔐 SECURITY WARNING

**Your token is now exposed!** You should:

1. **Revoke this token immediately**
2. **Create a new token with proper scopes**
3. **Never share tokens in chat/messages**

---

## ✅ FIX: Add Workflow Scope to Token

### **Step 1: Go to GitHub Token Settings**

1. Go to: `https://github.com/settings/tokens`
3. Click **"Edit"** or **"Regenerate token"**

### **Step 2: Add Required Scopes**

Check these boxes:
- ✅ **`repo`** (Full control of private repositories)
- ✅ **`workflow`** (Update GitHub Action workflows) ← **THIS IS REQUIRED!**

### **Step 3: Generate New Token**

1. Click **"Generate token"** or **"Update token"**
2. **Copy the new token** (you'll only see it once!)

### **Step 4: Update Git Credentials**

```bash
# Remove old credentials
git credential reject https://github.com

# Push again (it will ask for credentials)
git push origin main

# When prompted:
# Username: your-github-username
# Password: paste-your-new-token-here
```

---

## 🚀 ALTERNATIVE: Push Without Workflow File

If you can't update the token right now, we can push everything except the workflow file:

```bash
# Temporarily remove workflow file from staging
git reset HEAD .github/workflows/deploy-digitalocean.yml

# Commit other changes
git commit -m "Update deployment files (workflow excluded)"

# Push
git push origin main

# Then add workflow file via GitHub web interface
```

---

## 📝 RECOMMENDED: Revoke and Create New Token

**Since your token was exposed:**

1. **Revoke old token:**
   - Go to: `https://github.com/settings/tokens`
   - Find the token
   - Click **"Delete"** or **"Revoke"**

2. **Create new token:**
   - Click **"Generate new token (classic)"**
   - Name: `SMS2 Deployment Token`
   - Expiration: Choose appropriate (90 days recommended)
   - Scopes:
     - ✅ `repo` (all)
     - ✅ `workflow`
   - Click **"Generate token"**
   - **Copy it immediately** (you won't see it again!)

3. **Use new token:**
   ```bash
   git push origin main
   # When asked for password, paste new token
   ```

---

## ✅ QUICK FIX

**Right now, do this:**

1. Go to: `https://github.com/settings/tokens`
2. Find your token
3. Click **"Edit"**
4. Check **`workflow`** scope
5. Click **"Update token"**
6. Then run: `git push origin main`

---

**Your token needs the `workflow` scope to update GitHub Actions workflow files!**

