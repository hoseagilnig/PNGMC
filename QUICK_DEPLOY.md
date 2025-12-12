# Quick Deploy CSS Changes to DigitalOcean

**Server IP:** `134.199.174.78`

---

## 🚀 FASTEST METHOD: Direct File Copy

Run this command from your local machine (in the project directory):

```bash
scp css/sms_styles.css root@134.199.174.78:/var/www/html/css/sms_styles.css
```

**That's it!** The changes will be live immediately.

---

## ✅ VERIFY IT WORKED

1. **Visit your site:** `http://134.199.174.78`
2. **Hard refresh:** Press `Ctrl + Shift + R` (or `Cmd + Shift + R` on Mac)
3. **Check:**
   - ✅ No space above "Welcome to PNG Maritime College"
   - ✅ Statistics visible immediately
   - ✅ Labels (Students, Programs, Support) visible

---

## 🔧 IF YOU GET SSH ERRORS

**First time connecting?** You may need to accept the server fingerprint.

**Need to set up SSH key?** See `CURSOR_SSH_SETUP.md`

**Using password instead?** You'll be prompted for the password.

---

## 📝 ALTERNATIVE: Manual Upload

If `scp` doesn't work:

1. **Open FileZilla or WinSCP**
2. **Connect to:** `134.199.174.78`
3. **Upload:** `css/sms_styles.css` to `/var/www/html/css/`
4. **Done!**

---

**Your changes are ready to deploy!** 🎉

