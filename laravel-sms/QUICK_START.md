# Quick Start Guide - Laravel 11 SMS
## Get Up and Running in 10 Minutes

---

## 🚀 Quick Installation

### Step 1: Install Dependencies
```bash
cd laravel-sms
composer install
npm install
```

### Step 2: Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sms2_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Step 3: Run Migrations
```bash
php artisan migrate
```

### Step 4: Set Permissions
```bash
chmod -R 775 storage bootstrap/cache
```

### Step 5: Start Server
```bash
php artisan serve
```

Visit: `http://localhost:8000`

---

## 🔐 Default Login Credentials

After running migrations, use these test credentials:

**Admin:**
- Username: `admin01`
- Password: `adminpass1` (or check database seeders)
- Role: `admin`

**Finance:**
- Username: `finance01`
- Password: `financepass1`
- Role: `finance`

**Student Services:**
- Username: `service01`
- Password: `servicepass1`
- Role: `studentservices`

---

## 📁 Key Directories

- `app/Models/` - Eloquent models
- `app/Http/Controllers/` - Controllers
- `database/migrations/` - Database migrations
- `resources/views/` - Blade templates
- `routes/web.php` - Routes
- `nginx/` - Nginx configuration

---

## 🛠️ Common Commands

```bash
# Run migrations
php artisan migrate

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# List routes
php artisan route:list

# Create new controller
php artisan make:controller Admin/NewController

# Create new model
php artisan make:model NewModel

# Create new migration
php artisan make:migration create_new_table
```

---

## 🐛 Troubleshooting

### Issue: "Class not found"
**Solution:** Run `composer dump-autoload`

### Issue: "500 Error"
**Solution:** Check `storage/logs/laravel.log` and ensure file permissions are correct

### Issue: "Database connection failed"
**Solution:** Verify `.env` database credentials and ensure MySQL is running

### Issue: "Route not found"
**Solution:** Run `php artisan route:clear`

---

## 📚 Next Steps

1. Review `DEPLOYMENT_GUIDE.md` for production setup
2. Review `TESTING_GUIDE.md` for testing procedures
3. Review `COMPLETION_SUMMARY.md` for full migration details

---

**Ready to go!** 🎉

