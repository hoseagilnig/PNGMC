# PNG Maritime College - Student Management System
## Laravel 11 Implementation

This is the Laravel 11 rebuild of the PNG Maritime College Student Management System, migrated from vanilla PHP to Laravel 11 with Eloquent ORM, Blade templates, and Nginx.

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Node.js & NPM

### Installation

```bash
# Clone repository
git clone <repository-url> laravel-sms
cd laravel-sms

# Install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure .env file with your database credentials
nano .env

# Run migrations
php artisan migrate

# Compile assets
npm run build

# Start development server
php artisan serve
```

Visit `http://localhost:8000` to see the application.

---

## 📁 Project Structure

```
laravel-sms/
├── app/                    # Application core
│   ├── Http/
│   │   ├── Controllers/   # Controllers
│   │   └── Middleware/     # Custom middleware
│   └── Models/            # Eloquent models
├── database/
│   └── migrations/        # Database migrations
├── resources/
│   └── views/            # Blade templates
├── routes/
│   └── web.php           # Web routes
└── nginx/                # Nginx configuration
```

---

## 🔐 Authentication

The system uses Laravel's built-in authentication with custom role-based access control.

### User Roles
- `admin` - Full system access
- `finance` - Financial operations
- `studentservices` - Student services
- `hod` - Head of Department

### Login
- URL: `/login`
- Default credentials: See database seeders

---

## 🗄️ Database

### Migrations
```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Reset database
php artisan migrate:fresh
```

### Models
All models use Eloquent ORM with relationships defined:
- `User` - Staff/Admin users
- `Student` - Student records
- `Program` - Academic programs
- `Invoice` - Financial invoices
- `Application` - Student applications
- And more...

---

## 🎨 Views (Blade Templates)

### Layouts
- `resources/views/layouts/app.blade.php` - Main layout

### Pages
- `resources/views/auth/login.blade.php` - Login page
- `resources/views/admin/dashboard.blade.php` - Admin dashboard
- More views to be added...

---

## 🛣️ Routes

Routes are defined in `routes/web.php` with role-based middleware protection.

### Example Routes
- `/login` - Login page
- `/admin/dashboard` - Admin dashboard (requires `admin` role)
- `/finance/dashboard` - Finance dashboard (requires `finance` role)
- `/student-services/dashboard` - Student services dashboard

---

## 🔒 Security Features

- ✅ CSRF protection (Laravel built-in)
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ XSS protection (Blade escaping)
- ✅ Authentication & Authorization
- ✅ Role-based access control
- ✅ Password hashing (bcrypt)
- ✅ Session security

---

## 🌐 Nginx Configuration

Nginx configuration file is located at `nginx/sms.conf`.

### Features
- SSL/HTTPS support
- Security headers
- PHP-FPM configuration
- Static file caching
- Gzip compression

See `DEPLOYMENT_GUIDE.md` for full Nginx setup instructions.

---

## 📝 Environment Configuration

All configuration is done via `.env` file:

```env
APP_NAME="PNG Maritime College SMS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sms2_db
DB_USERNAME=root
DB_PASSWORD=
```

---

## 🚀 Deployment

See `DEPLOYMENT_GUIDE.md` for complete deployment instructions on Ubuntu 24.04 LTS with Nginx.

### Quick Deployment Checklist
- [ ] Install PHP 8.2+, MySQL, Nginx
- [ ] Configure `.env` file
- [ ] Run migrations
- [ ] Set file permissions
- [ ] Configure Nginx
- [ ] Set up SSL certificate
- [ ] Configure UFW firewall

---

## 📚 Documentation

- **Migration Plan:** `LARAVEL_MIGRATION_PLAN.md`
- **Migration Summary:** `MIGRATION_SUMMARY.md`
- **Deployment Guide:** `DEPLOYMENT_GUIDE.md`
- **Requirements Comparison:** `../REQUIREMENTS_COMPARISON.md`

---

## 🔄 Migration from Vanilla PHP

This system was migrated from vanilla PHP. Key changes:

1. **Backend:** Vanilla PHP → Laravel 11
2. **Database:** MySQLi → Eloquent ORM
3. **Views:** HTML/PHP → Blade Templates
4. **Auth:** Custom → Laravel Auth
5. **Server:** Apache → Nginx
6. **Config:** Ad-hoc → Environment-based

---

## 🛠️ Development

### Artisan Commands
```bash
# Create controller
php artisan make:controller Admin/StudentController

# Create model
php artisan make:model Student

# Create migration
php artisan make:migration create_students_table

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Testing
```bash
# Run tests
php artisan test
```

---

## 📞 Support

For issues or questions, please refer to the documentation files or contact the development team.

---

**Version:** 2.0 (Laravel 11)  
**Last Updated:** January 2025

