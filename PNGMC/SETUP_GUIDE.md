# Quick Setup Guide - PNG Maritime College SMS

## Step-by-Step Database Setup

### Step 1: Import Database

1. Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
2. Click on "New" to create a database (or the database will be created automatically)
3. Click on "Import" tab
4. Click "Choose File" and select: `database/sms2_database.sql`
5. Click "Go" to import

**OR using MySQL command line:**
```bash
mysql -u root -p < database/sms2_database.sql
```

### Step 2: Configure Database Connection

1. Open `pages/includes/db_config.php`
2. Update these lines with your MySQL credentials:
   ```php
   define('DB_HOST', 'localhost');  // Usually 'localhost'
   define('DB_USER', 'root');        // Your MySQL username
   define('DB_PASS', '');            // Your MySQL password (leave empty if no password)
   define('DB_NAME', 'sms2_db');     // Database name
   ```

### Step 3: Test Connection

Open in your browser:
```
http://localhost/sms2/database/test_connection.php
```

You should see:
- ✓ Database connection successful!
- List of all tables
- User count

If you see an error, check:
- MySQL server is running
- Database credentials are correct
- Database was imported successfully

### Step 4: Update Passwords

Open in your browser:
```
http://localhost/sms2/database/update_passwords.php
```

This will update all password hashes. You should see:
```
✓ Updated password for: admin01
✓ Updated password for: admin02
...
Update complete!
```

### Step 5: Test Login

1. Go to: `http://localhost/sms2/pages/login.php`
2. Try logging in with:
   - Username: `admin01`
   - Password: `adminpass1`
   - User Type: `Administration`

If login works, you're all set! 🎉

## Troubleshooting

### "Database connection failed"
- Check MySQL is running
- Verify credentials in `db_config.php`
- Make sure database `sms2_db` exists

### "Invalid username, user type, or account is inactive"
- Make sure you ran `update_passwords.php`
- Check username, password, and user type match exactly
- Verify user exists in database

### "No tables found"
- Database wasn't imported correctly
- Re-import `sms2_database.sql`

## Default Login Credentials

**Administration:**
- `admin01` / `adminpass1`
- `admin02` / `adminpass2`
- `admin03` / `adminpass3`

**Finance:**
- `finance01` / `financepass1`
- `finance02` / `financepass2`
- `finance03` / `financepass3`

**Student Services:**
- `service01` / `servicepass1`
- `service02` / `servicepass2`
- `service03` / `servicepass3`

## Next Steps

After setup is complete:
1. Change default passwords for security
2. Add real student data
3. Configure system settings
4. Customize dashboards as needed

