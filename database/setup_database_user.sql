-- Database User Setup Script
-- Creates a dedicated database user for the SMS application
-- 
-- IMPORTANT: Replace 'your_secure_password' with a strong password!
-- Run this script as MySQL root user or with CREATE USER privileges
--
-- Usage: mysql -u root -p < setup_database_user.sql
-- Or execute in MySQL client: source setup_database_user.sql

-- Create dedicated database user
-- Replace 'your_secure_password' with a strong password
CREATE USER IF NOT EXISTS 'sms_user'@'localhost' IDENTIFIED BY 'your_secure_password';

-- Grant necessary privileges (adjust database name if different)
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES ON sms_database.* TO 'sms_user'@'localhost';

-- Grant privileges for creating temporary tables (needed for some operations)
GRANT CREATE TEMPORARY TABLES ON sms_database.* TO 'sms_user'@'localhost';

-- Grant file privilege only if needed for LOAD DATA INFILE (usually not needed)
-- GRANT FILE ON *.* TO 'sms_user'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Verify user creation
SELECT User, Host FROM mysql.user WHERE User = 'sms_user';

-- Show granted privileges
SHOW GRANTS FOR 'sms_user'@'localhost';

-- Instructions:
-- 1. After running this script, update pages/includes/db_config.php with:
--    - DB_USER = 'sms_user'
--    - DB_PASS = 'your_secure_password' (the one you set above)
-- 2. Remove root credentials from db_config.php
-- 3. Test the application to ensure the new user works correctly

