# Laravel 11 Migration Plan
## PNG Maritime College SMS - Complete System Rebuild

**Migration Target:** Laravel 11 + Eloquent ORM + Blade + Nginx + Laravel Auth

---

## 📋 MIGRATION CHECKLIST

### Phase 1: Laravel Setup ✅
- [x] Create migration plan
- [ ] Install Laravel 11 project structure
- [ ] Configure .env file
- [ ] Set up database migrations
- [ ] Create Eloquent models

### Phase 2: Authentication Migration
- [ ] Migrate custom auth to Laravel Auth
- [ ] Create User model with roles
- [ ] Set up middleware for role-based access
- [ ] Migrate login/logout functionality

### Phase 3: Views Migration
- [ ] Convert HTML/PHP to Blade templates
- [ ] Create layout files
- [ ] Migrate CSS/JS assets
- [ ] Set up responsive design in Blade

### Phase 4: Controllers & Routes
- [ ] Create controllers for all pages
- [ ] Set up routes (web.php)
- [ ] Migrate business logic
- [ ] Implement form validation

### Phase 5: Database & Models
- [ ] Create all Eloquent models
- [ ] Set up relationships
- [ ] Migrate queries to Eloquent
- [ ] Create seeders for initial data

### Phase 6: Security & Configuration
- [ ] Migrate CSRF (Laravel built-in)
- [ ] Set up environment-based config
- [ ] Configure session management
- [ ] Set up file uploads

### Phase 7: Nginx Configuration
- [ ] Create Nginx config file
- [ ] Set up PHP-FPM
- [ ] Configure SSL/HTTPS
- [ ] Set up UFW firewall rules

### Phase 8: Deployment
- [ ] Create deployment documentation
- [ ] Set up staging environment
- [ ] Production deployment guide

---

## 🗂️ PROJECT STRUCTURE

```
laravel-sms/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── Admin/
│   │   │   ├── Finance/
│   │   │   ├── StudentServices/
│   │   │   └── Student/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Student.php
│   │   ├── Application.php
│   │   ├── Program.php
│   │   ├── Invoice.php
│   │   └── ...
│   └── Services/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   ├── auth/
│   │   ├── admin/
│   │   ├── finance/
│   │   ├── student-services/
│   │   └── student/
│   ├── css/
│   └── js/
├── routes/
│   ├── web.php
│   └── api.php
├── config/
├── .env
└── nginx/
    └── sms.conf
```

---

## 🔄 MIGRATION MAPPING

### Database Tables → Eloquent Models

| Table | Model | Relationships |
|-------|-------|---------------|
| users | User | - |
| students | Student | Program, Enrollments |
| programs | Program | Students, Enrollments |
| applications | Application | Student, User (assessed_by) |
| invoices | Invoice | Student, User (created_by), InvoiceItems |
| invoice_items | InvoiceItem | Invoice |
| payments | Payment | Invoice, Student, User (processed_by) |
| enrollments | Enrollment | Student, Program |
| dormitories | Dormitory | DormitoryAssignments |
| dormitory_assignments | DormitoryAssignment | Student, Dormitory |
| support_tickets | SupportTicket | Student, User (submitted_by, assigned_to) |
| ticket_comments | TicketComment | SupportTicket, User |
| advising_appointments | AdvisingAppointment | Student, User (advisor_id) |
| system_settings | SystemSetting | User (updated_by) |
| activity_log | ActivityLog | User |

### Pages → Controllers

| Current Page | Controller | Route |
|--------------|------------|-------|
| pages/login.php | Auth/LoginController | /login |
| pages/admin_dashboard.php | Admin/DashboardController | /admin/dashboard |
| pages/finance_dashboard.php | Finance/DashboardController | /finance/dashboard |
| pages/student_service_dashboard.php | StudentServices/DashboardController | /student-services/dashboard |
| pages/applications.php | Admin/ApplicationController | /admin/applications |
| pages/students.php | Admin/StudentController | /admin/students |
| pages/invoices.php | Finance/InvoiceController | /finance/invoices |
| student_login.php | Student/Auth/LoginController | /student/login |
| student_dashboard.php | Student/DashboardController | /student/dashboard |

---

## 🔐 AUTHENTICATION MIGRATION

### Current System:
- Custom session-based auth
- Role-based access (admin, finance, studentservices)
- Manual password hashing with password_hash()

### Laravel System:
- Laravel Auth (built-in)
- Custom User model with roles
- Middleware for role checking
- Password hashing via Hash facade

### Migration Steps:
1. Extend Laravel's Authenticatable
2. Add role field to users table
3. Create RoleMiddleware
4. Update login to use Laravel Auth
5. Migrate password hashes (compatible with Laravel)

---

## 📝 VIEW MIGRATION

### Current: HTML with PHP
```php
<?php require_once 'includes/db_config.php'; ?>
<!DOCTYPE html>
<html>
  <body>
    <h1><?php echo $title; ?></h1>
  </body>
</html>
```

### Target: Blade Templates
```blade
@extends('layouts.app')
@section('content')
  <h1>{{ $title }}</h1>
@endsection
```

---

## ⚙️ CONFIGURATION MIGRATION

### Current: Ad-hoc config
```php
define('DB_HOST', getEnvVar('DB_HOST', 'localhost'));
```

### Target: Laravel .env
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sms2_db
DB_USERNAME=root
DB_PASSWORD=
```

---

## 🚀 NEXT STEPS

1. Create Laravel project structure
2. Generate migrations from existing schema
3. Create Eloquent models
4. Set up Laravel Auth
5. Convert first view to Blade (login page)
6. Create controllers
7. Set up routes
8. Configure Nginx

---

**Status:** Planning Complete - Ready to Begin Migration

