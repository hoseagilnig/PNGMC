# Technical Specifications - PNG Maritime College SMS
## Deployment Environment Requirements

**Document Date:** January 2025  
**System:** Student Management System (SMS2)  
**Purpose:** Deployment Environment Planning

---

## 1. BACKEND TECHNOLOGIES

### 1.1 Server-Side Language
- **Language:** PHP (Hypertext Preprocessor)
- **Minimum Version:** PHP 7.4 or higher (recommended: PHP 8.0+)
- **Required PHP Extensions:**
  - `mysqli` - MySQL database connectivity
  - `session` - Session management
  - `mbstring` - Multi-byte string support (for utf8mb4)
  - `fileinfo` - File upload validation
  - `curl` - HTTP requests (for AI API integration)
  - `json` - JSON encoding/decoding
  - `openssl` - Encryption and secure connections

### 1.2 Web Server
- **Recommended:** Apache HTTP Server 2.4+
- **Alternative:** Nginx (with PHP-FPM)
- **Required Modules:**
  - `mod_rewrite` - URL rewriting
  - `mod_headers` - HTTP security headers
  - `mod_ssl` - HTTPS support (for production)

### 1.3 Database
- **Database System:** MySQL 5.7+ or MariaDB 10.3+
- **Database Name:** `sms2_db` (configurable)
- **Character Set:** UTF-8 (utf8mb4) - Supports full Unicode including emojis
- **Connection Method:** MySQLi (MySQL Improved Extension)
- **Port:** 
  - Auto-detected: 3307 for Windows/XAMPP, 3306 for Linux
  - Configurable via `.env` file (DB_PORT)
  - Fallback mechanism: Tries alternative ports if default fails
- **Required Privileges:**
  - SELECT, INSERT, UPDATE, DELETE
  - CREATE, ALTER (for migrations)
  - INDEX (for performance)

### 1.4 Backend Features & APIs
- **Session Management:** Native PHP sessions with security hardening
- **Authentication:** Password hashing using `password_hash()` (bcrypt)
- **File Upload:** Native PHP file handling with validation
- **API Integration:**
  - Google Gemini API (for chatbot)
  - OpenAI API (optional, for chatbot)
  - Anthropic Claude API (optional, for chatbot)
  - Custom API endpoints support

### 1.5 Backend Architecture
- **Pattern:** Server-side rendering (SSR)
- **Database Access:** MySQLi with prepared statements
- **Security:**
  - CSRF protection
  - SQL injection prevention (prepared statements)
  - XSS protection (input sanitization)
  - Session security hardening
  - Rate limiting for login attempts
- **Data Management:**
  - Archive system for historical data preservation
  - Automatic archiving based on configurable rules
  - Manual archiving with audit trail
  - Archive management interface

---

## 2. FRONTEND TECHNOLOGIES

### 2.1 Core Technologies
- **HTML:** HTML5
- **CSS:** CSS3
- **JavaScript:** Vanilla JavaScript (ES5/ES6)
- **No Major Frontend Framework:** System uses vanilla JavaScript (no React, Vue, or Angular)

### 2.2 CSS Framework & Libraries
- **Custom CSS:** 
  - `css/d_styles.css` - Dashboard styles
  - `css/sms_styles.css` - General system styles
- **Bootstrap:** 
  - Used only in `pages/setup_chatbot_api.php` (setup page)
  - Version: 5.1.3 (via CDN)
  - **Note:** Main system does NOT use Bootstrap framework

### 2.3 JavaScript Libraries
- **Chart.js:**
  - Version: Latest (via CDN: `cdn.jsdelivr.net/npm/chart.js`)
  - Purpose: Data visualization (charts, graphs)
  - Used in: Fee reports, analytics pages
- **Vanilla JavaScript:**
  - Custom JavaScript for:
    - Form validation
    - AJAX requests
    - Modal dialogs
    - Responsive navigation (hamburger menu)
    - Dynamic content loading
    - File upload handling

### 2.4 Frontend Features
- **Responsive Design:** 
  - Mobile-first approach
  - CSS Media Queries for breakpoints:
    - Desktop: > 1024px
    - Tablet: 768px - 1024px
    - Mobile: < 768px
- **Browser Compatibility:**
  - Modern browsers (Chrome, Firefox, Safari, Edge)
  - IE11+ (with limitations)
- **AJAX:** Native JavaScript `XMLHttpRequest` and `fetch()` API

---

## 3. DEPLOYMENT REQUIREMENTS

### 3.1 Server Requirements

#### Minimum Requirements:
- **CPU:** 2 cores
- **RAM:** 2GB
- **Storage:** 10GB (for application + database)
- **Network:** Stable internet connection

#### Recommended Requirements:
- **CPU:** 4+ cores
- **RAM:** 4GB+
- **Storage:** 50GB+ (for application, database, uploads, logs)
- **Network:** High-speed connection

### 3.2 PHP Configuration Requirements

**php.ini Settings:**
```ini
upload_max_filesize = 50M
post_max_size = 100M
max_file_uploads = 50
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
display_errors = Off (production)
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
log_errors = On
error_log = /path/to/logs/php_errors.log
session.cookie_httponly = 1
session.cookie_secure = 1 (if using HTTPS)
session.cookie_samesite = Strict
```

### 3.3 Apache Configuration

**Required Modules:**
- `mod_rewrite`
- `mod_headers`
- `mod_ssl` (for HTTPS)

**Security Headers (via .htaccess):**
- X-Frame-Options
- X-Content-Type-Options
- Content-Security-Policy
- Strict-Transport-Security (HSTS)
- Referrer-Policy

### 3.4 Database Configuration

**MySQL/MariaDB Settings:**
```sql
character_set_server = utf8mb4
collation_server = utf8mb4_unicode_ci
max_connections = 100
innodb_buffer_pool_size = 1G (adjust based on RAM)
```

**Database User Requirements:**
- Dedicated user (not root)
- Limited privileges (SELECT, INSERT, UPDATE, DELETE)
- Strong password
- Host restriction (localhost or specific IP)

---

## 4. EXTERNAL DEPENDENCIES

### 4.1 CDN Resources (External)
- **Chart.js:** `https://cdn.jsdelivr.net/npm/chart.js`
- **Bootstrap (Setup Page Only):** `https://cdn.jsdelivr.net/npm/bootstrap@5.1.3`

### 4.2 API Services (Optional)
- **Google Gemini API:** For AI chatbot functionality
- **OpenAI API:** Alternative chatbot provider
- **Anthropic Claude API:** Alternative chatbot provider

**Note:** System can operate without AI APIs (database mode available)

---

## 5. FILE STRUCTURE & DEPENDENCIES

### 5.1 Directory Structure
```
sms2/
├── pages/              # PHP application files
│   ├── includes/       # Helper functions, configs
│   ├── api/            # API endpoints
│   └── css/            # Page-specific styles
├── css/                # Global stylesheets
├── images/             # Static images
├── uploads/            # User-uploaded files
├── database/           # SQL scripts, migrations
├── logs/               # Application logs
└── .env                # Environment variables (not in repo)
```

### 5.2 Key Configuration Files
- `pages/includes/db_config.php` - Database connection (with auto-port detection)
- `pages/includes/chatbot_config.php` - Chatbot/AI configuration
- `pages/includes/security_helper.php` - Security functions
- `pages/includes/archive_helper.php` - Archive system functions
- `pages/includes/env_loader.php` - Environment variable loader
- `pages/includes/workflow_helper.php` - Application workflow management
- `.htaccess` - Apache configuration
- `.env` - Environment variables (production secrets)

---

## 6. SECURITY REQUIREMENTS

### 6.1 SSL/TLS
- **HTTPS Required:** Yes (for production)
- **Certificate:** Valid SSL certificate
- **Protocol:** TLS 1.2 or higher

### 6.2 Environment Variables
- **File:** `.env` (not committed to version control)
- **Required Variables:**
  - `DB_HOST` (database host, default: localhost)
  - `DB_PORT` (database port, default: 3306 for Linux, 3307 for XAMPP)
  - `DB_USER` (database username)
  - `DB_PASS` (database password)
  - `DB_NAME` (database name, default: sms2_db)
  - `GEMINI_API_KEY` (if using AI chatbot)
  - `CHATBOT_DEBUG=false` (production)

### 6.3 File Permissions
- **Directories:** 755
- **Files:** 644
- **Upload Directory:** 755 (not executable)
- **Configuration Files:** 600 (sensitive files)

---

## 7. DEPLOYMENT PLATFORMS

### 7.1 Compatible Platforms
- **Linux:** Ubuntu, Debian, CentOS, RHEL
- **Windows:** Windows Server (with XAMPP/WAMP)
- **Cloud:** AWS, Azure, Google Cloud, DigitalOcean

### 7.2 Recommended Stack
- **LAMP:** Linux + Apache + MySQL + PHP
- **LEMP:** Linux + Nginx + MySQL + PHP-FPM
- **XAMPP:** For development/testing (Windows/Linux/Mac)

---

## 8. VERSION CONTROL

### 8.1 Repository
- **Platform:** GitHub
- **Repository:** `https://github.com/hoseagilnig/PNGMC.git`
- **Branch:** `main`

### 8.2 Excluded Files (.gitignore)
- `.env` files
- `logs/` directory
- `uploads/` directory
- Temporary files
- IDE configuration files

---

## 9. SYSTEM FEATURES & MODULES

### 9.1 Core Modules
- **Application Management:** School leaver and continuing student applications
- **Student Management:** Student records, enrollment, accounts
- **Financial Management:** Invoices, payments, fee tracking
- **Workflow Management:** Application processing, HOD review, notifications
- **Archive System:** Historical data preservation and management
- **User Management:** Role-based access control (Admin, Finance, Student Services, HOD)
- **Document Management:** File uploads, document tracking, verification
- **Chatbot System:** AI-powered assistance (Google Gemini, OpenAI, Claude)

### 9.2 Archive System
- **Archive Tables:**
  - `archived_applications` - Archived application records
  - `archived_students` - Archived student records
  - `archived_invoices` - Archived invoice records
  - `archived_application_documents` - Archived document references
  - `archive_log` - Archive operation audit trail
  - `archive_settings` - Configurable archive rules
- **Features:**
  - Manual archiving with reason/notes
  - Automatic archiving based on time-based rules
  - Archive management interface
  - Search and filter archived records
  - Complete data preservation
  - Audit logging of all archive operations

### 9.3 Database Schema
- **Core Tables:** users, students, applications, invoices, payments
- **Workflow Tables:** mandatory_checks, correspondence, workflow_notifications
- **Archive Tables:** archived_applications, archived_students, archived_invoices
- **System Tables:** programs, dormitories, system_settings, archive_settings

---

## 10. SUMMARY TABLE

| Category | Technology | Version/Details |
|----------|-----------|-----------------|
| **Backend Language** | PHP | 7.4+ (recommended: 8.0+) |
| **Web Server** | Apache | 2.4+ (or Nginx) |
| **Database** | MySQL/MariaDB | 5.7+ / 10.3+ |
| **Database Extension** | MySQLi | Built-in PHP extension |
| **Database Port** | Auto-detected | 3307 (Windows/XAMPP), 3306 (Linux) |
| **Frontend Language** | HTML5, CSS3, JavaScript | ES5/ES6 |
| **CSS Framework** | Custom CSS | No major framework |
| **JavaScript Framework** | Vanilla JS | No React/Vue/Angular |
| **Chart Library** | Chart.js | Latest (via CDN) |
| **Session Management** | PHP Native Sessions | With security hardening |
| **Authentication** | PHP password_hash | bcrypt algorithm |
| **File Upload** | PHP Native | With validation |
| **API Integration** | cURL | For external APIs |
| **Archive System** | Custom PHP | Manual & automatic archiving |

---

## 11. DEPLOYMENT CHECKLIST

### Pre-Deployment
- [ ] PHP 7.4+ installed with required extensions
- [ ] Apache/Nginx configured
- [ ] MySQL/MariaDB 5.7+ installed
- [ ] Database created and configured
- [ ] SSL certificate installed (for HTTPS)
- [ ] Environment variables configured (`.env` file)
- [ ] File permissions set correctly
- [ ] Security headers configured (`.htaccess`)
- [ ] Error logging configured
- [ ] Database user created with limited privileges

### Post-Deployment
- [ ] Test all user workflows
- [ ] Verify HTTPS is working
- [ ] Check error logs
- [ ] Test file uploads
- [ ] Verify database connections
- [ ] Test responsive design on multiple devices
- [ ] Verify security features (CSRF, rate limiting)
- [ ] Set up automated backups
- [ ] Configure monitoring

---

## 12. SUPPORT & MAINTENANCE

### 11.1 Log Files
- **PHP Errors:** `logs/php_errors.log`
- **Security Events:** `logs/security.log`
- **Application Logs:** `logs/app.log`

### 11.2 Backup Requirements
- **Database:** Daily automated backups recommended
- **Uploads:** Regular backup of `uploads/` directory
- **Configuration:** Backup `.env` and configuration files

### 11.3 Monitoring
- Server resource usage (CPU, RAM, Disk)
- Database performance
- Error log monitoring
- Security event monitoring
- Application uptime

---

## 13. NOTES FOR DEPLOYMENT TEAM

1. **No Node.js Required:** System does not use Node.js or npm
2. **No Build Process:** No compilation or build step required
3. **Simple Deployment:** Copy files to server, configure database, set environment variables
4. **CDN Dependencies:** Chart.js loaded from CDN (requires internet connection)
5. **Database Migrations:** Run SQL scripts in `database/` directory
6. **Environment Variables:** Critical - must configure `.env` file before deployment
7. **File Permissions:** Ensure uploads directory is writable but not executable
8. **Database Port:** Auto-detects Windows (3307) vs Linux (3306), can be overridden in `.env`
9. **Archive System:** Optional - run `database/create_archive_tables.sql` to enable archiving
10. **MySQL Service:** Must be running before system can function (check XAMPP Control Panel on Windows)

---

## 14. RECENT UPDATES (January 2025)

### Archive System Implementation
- ✅ Complete archive system for applications, students, and invoices
- ✅ Archive management interface with search and filtering
- ✅ Automatic archiving with configurable rules
- ✅ Archive audit trail and logging
- ✅ Manual archiving with reason/notes tracking

### Database Configuration Improvements
- ✅ Auto-detection of database port (Windows vs Linux)
- ✅ Fallback mechanism for port connection
- ✅ Environment variable support for all database settings
- ✅ Improved error handling and diagnostics

### Security Enhancements
- ✅ CSRF protection on all forms
- ✅ SQL injection prevention (prepared statements)
- ✅ Session security hardening
- ✅ Login rate limiting
- ✅ Environment variable support for API keys

### User Interface Improvements
- ✅ Responsive design for all dashboards
- ✅ Mobile-friendly navigation (hamburger menu)
- ✅ Student profile photo upload functionality
- ✅ Archive management interface

---

**Document Prepared For:** Management & Deployment Team  
**Last Updated:** January 2025  
**Status:** Production Ready  
**Version:** 2.0 (Archive System Included)

---

*End of Technical Specifications Document*

