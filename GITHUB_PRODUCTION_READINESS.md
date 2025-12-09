# GitHub Production Readiness Checklist

## ✅ Current Status

### Git & GitHub Connection
- ✅ **Repository Connected:** `https://github.com/hoseagilnig/PNGMC.git`
- ✅ **Branch:** `main`
- ✅ **.gitignore Created:** Protects sensitive files
- ✅ **Cursor Git Integration:** Ready to use

### Security Status

#### ⚠️ CRITICAL - Must Fix Before Pushing to Production:

1. **Hardcoded API Key in Source Code**
   - **File:** `pages/includes/chatbot_config.php` (line 35)
   - **Issue:** API key `AIzaSyD2DHnfmijjr2dn85B2S2VgBzLVUo8oPWY` is hardcoded
   - **Risk:** ⚠️ HIGH - Anyone with repo access can see your API key
   - **Action Required:** 
     - Remove the fallback API key from line 35
     - Ensure `.env` file exists with `GEMINI_API_KEY`
     - Verify `.env` is in `.gitignore` (✅ Already done)

2. **Database Credentials**
   - **File:** `pages/includes/db_config.php`
   - **Check:** Verify production credentials are NOT committed
   - **Action:** Use environment variables or ensure file is not tracked

3. **Verify Sensitive Files Are NOT Tracked**
   - `.env` files
   - `logs/` directory
   - `uploads/` directory
   - Database dumps

## 🔒 Pre-Push Security Verification

### Step 1: Check What Will Be Committed

**In Cursor:**
1. Open Source Control (`Ctrl+Shift+G`)
2. Review ALL files in "Changes" section
3. **DO NOT commit if you see:**
   - `.env` files
   - `logs/*.log` files
   - Files with passwords or API keys
   - `uploads/` directory contents

### Step 2: Remove Hardcoded API Key

**Before pushing, edit `pages/includes/chatbot_config.php`:**

```php
// REMOVE THIS ENTIRE BLOCK (lines 34-36):
if (empty($gemini_key)) {
    $gemini_key = 'AIzaSyD2DHnfmijjr2dn85B2S2VgBzLVUo8oPWY'; // DELETE THIS!
}
```

**Replace with:**
```php
if (empty($gemini_key)) {
    // No fallback - must be set in .env file
    error_log('GEMINI_API_KEY not set in environment variables');
}
```

### Step 3: Verify .gitignore is Working

**Check these files are NOT in Source Control:**
- ✅ `.env` - Should be ignored
- ✅ `logs/` - Should be ignored  
- ✅ `uploads/` - Should be ignored
- ✅ `*.log` - Should be ignored

## 📋 Production Deployment Checklist

### Before First Production Push:

- [ ] Remove hardcoded API key from `chatbot_config.php`
- [ ] Create `.env` file with production values (DO NOT commit)
- [ ] Verify `.env` is in `.gitignore`
- [ ] Update database credentials in `db_config.php` (or use env vars)
- [ ] Remove any test/debug files
- [ ] Review all files in Source Control before committing
- [ ] Test locally after removing hardcoded values
- [ ] Commit changes with meaningful message
- [ ] Push to GitHub

### After Pushing to GitHub:

- [ ] Verify on GitHub that sensitive files are NOT visible
- [ ] Check that `.env` is not in repository
- [ ] Verify API keys are not in source code
- [ ] Set up environment variables on production server
- [ ] Test production deployment

## 🚨 Security Best Practices

### ✅ DO:
- ✅ Use environment variables for secrets
- ✅ Keep `.env` file local only
- ✅ Review changes before committing
- ✅ Use meaningful commit messages
- ✅ Commit frequently (small changes)

### ❌ DON'T:
- ❌ Commit `.env` files
- ❌ Commit hardcoded API keys
- ❌ Commit database passwords
- ❌ Commit log files
- ❌ Commit user uploads
- ❌ Commit temporary files

## 🔧 Quick Commands (If Git is Installed)

```bash
# Check what will be committed
git status

# See what files are tracked
git ls-files

# Verify .env is ignored
git check-ignore .env

# Remove file from tracking (if accidentally added)
git rm --cached .env
```

## 📝 Recommended First Commit

**Safe files to commit:**
- ✅ Source code (`.php` files)
- ✅ CSS/JavaScript files
- ✅ Documentation (`.md` files)
- ✅ Database schema files (`database/*.sql`)
- ✅ `.gitignore` file
- ✅ Template files (`.htaccess_production`)

**DO NOT commit:**
- ❌ `.env` files
- ❌ `logs/` directory
- ❌ `uploads/` directory
- ❌ Files with hardcoded secrets

## ⚠️ Current Blockers for Production

1. **API Key in Source Code** - Must be removed before pushing
2. **Verify .env setup** - Ensure environment variables are configured
3. **Review all tracked files** - Make sure no secrets are committed

## ✅ Next Steps

1. **Remove hardcoded API key** from `chatbot_config.php`
2. **Create `.env` file** with your API keys (local only)
3. **Review Source Control** in Cursor before committing
4. **Test locally** to ensure everything works
5. **Commit and push** when ready

---

**Status:** ⚠️ **NOT READY** - Remove hardcoded API key before production push
**Repository:** `https://github.com/hoseagilnig/PNGMC.git`
**Branch:** `main`

