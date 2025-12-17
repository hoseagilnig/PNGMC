# What to Copy for Deployment? 📦

## 🎯 **ONLY COPY: `laravel-sms/` folder**

For your Laravel deployment, you **ONLY need** the `laravel-sms/` folder.

---

## 📁 Folder Structure Explanation

### Your Current Structure:

```
C:\xampp\htdocs\sms2\
├── laravel-sms/          ✅ COPY THIS (New Laravel 11 system)
├── PNGMC/                ❌ DON'T COPY (Old PHP system - duplicate)
├── pages/                ❌ DON'T COPY (Old PHP system)
├── database/             ❌ DON'T COPY (Old PHP system)
├── apply.php             ❌ DON'T COPY (Old PHP system)
├── student_login.php     ❌ DON'T COPY (Old PHP system)
└── ... (other old PHP files)
```

---

## ✅ **What is `laravel-sms/`?**

This is your **NEW Laravel 11 system** that we built:
- ✅ Laravel 11 framework
- ✅ Eloquent ORM
- ✅ Blade templates
- ✅ All migrations
- ✅ All models and controllers
- ✅ Authentication system
- ✅ Nginx configuration

**This is what you deploy to Ubuntu server.**

---

## ❌ **What is `PNGMC/` folder?**

The `PNGMC/` folder appears to be a **duplicate/backup** of the old PHP system:
- Contains same old PHP files as root
- Same structure (pages/, database/, etc.)
- **NOT needed** for Laravel deployment

**You can ignore this folder for deployment.**

---

## ❌ **What are the root PHP files?**

Files like:
- `apply.php`
- `student_login.php`
- `pages/` folder
- `database/` folder (old PHP database scripts)

These are the **OLD vanilla PHP system** that we're replacing with Laravel.

**You don't need these for deployment.**

---

## 🚀 **Deployment Summary**

### **Copy ONLY:**
```
C:\xampp\htdocs\sms2\laravel-sms\
```

### **To Ubuntu:**
```
/var/www/laravel-sms/
```

---

## 📋 **Quick Copy Steps**

### Option 1: Use the ZIP (Already Created)
1. ✅ `laravel-sms.zip` is already created
2. Copy `laravel-sms.zip` to Ubuntu
3. Extract to `/var/www/laravel-sms/`

### Option 2: Manual Copy
1. Navigate to: `C:\xampp\htdocs\sms2\laravel-sms\`
2. Copy entire folder to Ubuntu
3. Place in: `/var/www/laravel-sms/`

---

## ⚠️ **Important Notes**

1. **Don't copy `PNGMC/` folder** - It's the old PHP system
2. **Don't copy root PHP files** - They're the old system
3. **Only copy `laravel-sms/`** - This is your new Laravel system
4. **Skip `vendor/` folder** - Install via `composer install` on server
5. **Skip `node_modules/`** - Install via `npm install` if needed

---

## ✅ **What's Already Done**

- ✅ `laravel-sms.zip` created (without vendor, node_modules)
- ✅ Ready to copy to Ubuntu
- ✅ All Laravel code is in `laravel-sms/` folder

---

## 🎯 **Bottom Line**

**For deployment, you ONLY need:**
```
laravel-sms/ folder
```

**Everything else (PNGMC/, root PHP files) can be ignored for deployment.**

The `laravel-sms.zip` file is ready - just copy it to Ubuntu! 🚀

