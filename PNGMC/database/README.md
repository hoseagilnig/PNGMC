# Database Setup Instructions

This directory contains the database schema and setup files for the PNG Maritime College Student Management System.

## Files

- `sms2_database.sql` - Complete database schema with all tables
- `application_workflow_tables.sql` - Additional tables for application workflow (run after main schema)
- `update_passwords.php` - Script to update password hashes in the database
- `test_connection.php` - Test script to verify database connection (access via browser)

## Setup Steps

### 1. Create the Database

Import the SQL files into your MySQL database (in order):

**Step 1: Import main database schema**
```bash
mysql -u root -p < sms2_database.sql
```

**Step 2: Import application workflow tables**
```bash
mysql -u root -p < application_workflow_tables.sql
```

**Using phpMyAdmin:**
1. Open phpMyAdmin
2. Click on "Import" tab
3. Choose the file `sms2_database.sql` and click "Go"
4. Import again, choose `application_workflow_tables.sql` and click "Go"

**Using MySQL Workbench:**
1. Open MySQL Workbench
2. Connect to your MySQL server
3. File → Open SQL Script → Select `sms2_database.sql` → Execute
4. File → Open SQL Script → Select `application_workflow_tables.sql` → Execute

### 2. Configure Database Connection

Edit `pages/includes/db_config.php` and update the following values:

```php
define('DB_HOST', 'localhost');  // Your MySQL host
define('DB_USER', 'root');        // Your MySQL username
define('DB_PASS', '');            // Your MySQL password
define('DB_NAME', 'sms2_db');     // Database name
```

### 3. Test Database Connection

After importing the database, test the connection:

**Via Web Browser:**
```
http://localhost/sms2/database/test_connection.php
```

This will show you:
- Connection status
- List of all tables
- User count and sample users
- Password hash verification

### 4. Update Password Hashes

After creating the database, run the password update script:

```bash
php update_passwords.php
```

Or via web browser (if PHP is configured):
```
http://localhost/sms2/database/update_passwords.php
```

This will update all user passwords with properly hashed versions.

## Default Login Credentials

After setup, you can login with these credentials:

**Administration:**
- Username: `admin01` / Password: `adminpass1`
- Username: `admin02` / Password: `adminpass2`
- Username: `admin03` / Password: `adminpass3`

**Finance:**
- Username: `finance01` / Password: `financepass1`
- Username: `finance02` / Password: `financepass2`
- Username: `finance03` / Password: `financepass3`

**Student Services:**
- Username: `service01` / Password: `servicepass1`
- Username: `service02` / Password: `servicepass2`
- Username: `service03` / Password: `servicepass3`

## Database Structure

The database includes the following main tables:

- **users** - Staff/User accounts
- **students** - Student records
- **programs** - Academic programs
- **enrollments** - Student enrollments
- **dormitories** - Dormitory information
- **dormitory_assignments** - Student dormitory assignments
- **invoices** - Financial invoices
- **invoice_items** - Invoice line items
- **payments** - Payment records
- **support_tickets** - Support ticket system
- **ticket_comments** - Ticket comments
- **advising_appointments** - Advising appointments
- **system_settings** - System configuration
- **activity_log** - Activity logging
- **applications** - Student applications/expressions of interest
- **application_documents** - Documents submitted with applications
- **mandatory_checks** - Mandatory checks tracking (medical, police clearance, etc.)
- **correspondence** - Correspondence history with applicants
- **application_notes** - Internal notes and comments on applications

## Notes

- All passwords should be changed after initial setup for security
- The database uses UTF8MB4 encoding for full Unicode support
- Foreign key constraints are enabled for data integrity
- Indexes are created on frequently queried columns for performance

