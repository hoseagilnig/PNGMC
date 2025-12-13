# PDO Database Connection Guide

**Purpose:** Use PDO database connection that works on both local (XAMPP) and DigitalOcean

---

## ✅ READY-TO-USE CODE

### **Simple Usage:**

```php
<?php
// Include the PDO configuration
require_once 'pages/includes/pdo_config.php';

try {
    // Get PDO connection (automatically uses environment variables)
    $pdo = getPDOConnection();
    
    // Your database queries here
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = ?");
    $stmt->execute(['admin']);
    $users = $stmt->fetchAll();
    
} catch (\PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo "Database error occurred.";
}
?>
```

---

## 🔧 HOW IT WORKS

### **Automatic Configuration:**

The PDO connection automatically:
- ✅ Reads from `.env` file (if exists)
- ✅ Auto-detects port: **3307** for Windows/XAMPP, **3306** for Linux/DigitalOcean
- ✅ Uses environment variables for all database settings
- ✅ Works seamlessly on both local and production

### **Environment Variables:**

Create a `.env` file in your project root:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306          # 3307 for XAMPP/Windows, 3306 for Linux/DigitalOcean
DB_USER=your_db_user
DB_PASS=your_password
DB_NAME=sms2_db
DB_CHARSET=utf8mb4
```

**On DigitalOcean:**
- Port will automatically be `3306` (Linux default)
- Uses credentials from `.env` file

**On Local (XAMPP):**
- Port will automatically be `3307` (XAMPP default)
- Uses credentials from `.env` file

---

## 📝 EXAMPLES

### **Example 1: Select Data**

```php
require_once 'pages/includes/pdo_config.php';

try {
    $pdo = getPDOConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "User: " . $user['full_name'];
    }
    
} catch (\PDOException $e) {
    error_log("Error: " . $e->getMessage());
}
```

### **Example 2: Insert Data**

```php
require_once 'pages/includes/pdo_config.php';

try {
    $pdo = getPDOConnection();
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $password_hash, $full_name, $role]);
    
    $new_user_id = $pdo->lastInsertId();
    echo "New user ID: " . $new_user_id;
    
} catch (\PDOException $e) {
    error_log("Error: " . $e->getMessage());
}
```

### **Example 3: Update Data**

```php
require_once 'pages/includes/pdo_config.php';

try {
    $pdo = getPDOConnection();
    
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    echo "Updated " . $stmt->rowCount() . " row(s)";
    
} catch (\PDOException $e) {
    error_log("Error: " . $e->getMessage());
}
```

### **Example 4: Delete Data**

```php
require_once 'pages/includes/pdo_config.php';

try {
    $pdo = getPDOConnection();
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    echo "Deleted " . $stmt->rowCount() . " row(s)";
    
} catch (\PDOException $e) {
    error_log("Error: " . $e->getMessage());
}
```

### **Example 5: Transaction**

```php
require_once 'pages/includes/pdo_config.php';

try {
    $pdo = getPDOConnection();
    
    $pdo->beginTransaction();
    
    // Multiple operations
    $stmt1 = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE account_id = ?");
    $stmt1->execute([$amount, $from_account]);
    
    $stmt2 = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE account_id = ?");
    $stmt2->execute([$amount, $to_account]);
    
    $pdo->commit();
    echo "Transaction completed";
    
} catch (\PDOException $e) {
    $pdo->rollBack();
    error_log("Transaction failed: " . $e->getMessage());
}
```

---

## 🔐 SECURITY FEATURES

### **1. Prepared Statements (SQL Injection Protection)**

✅ **Always use prepared statements:**

```php
// ✅ CORRECT - Uses prepared statement
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);

// ❌ WRONG - Vulnerable to SQL injection
$pdo->query("SELECT * FROM users WHERE username = '$username'");
```

### **2. Error Handling**

✅ **Proper error handling:**

```php
try {
    $pdo = getPDOConnection();
    // Your queries
} catch (\PDOException $e) {
    // Log error (don't expose to users)
    error_log("Database error: " . $e->getMessage());
    // Show user-friendly message
    echo "An error occurred. Please try again.";
}
```

### **3. Environment Variables**

✅ **Credentials stored in `.env` file (not in code):**

- `.env` file is in `.gitignore` (not committed to Git)
- Different credentials for local vs production
- Easy to update without changing code

---

## 🌍 DEPLOYMENT

### **On DigitalOcean:**

1. **Create `.env` file on server:**

```bash
cd /var/www/html
nano .env
```

2. **Add database credentials:**

```env
DB_HOST=localhost
DB_PORT=3306
DB_USER=your_production_user
DB_PASS=your_production_password
DB_NAME=sms2_db
DB_CHARSET=utf8mb4
```

3. **Set permissions:**

```bash
chmod 600 .env
chown www-data:www-data .env
```

4. **Use in your code:**

```php
require_once 'pages/includes/pdo_config.php';
$pdo = getPDOConnection();
// Your queries...
```

---

## ✅ BENEFITS

### **1. Works Everywhere**
- ✅ Local development (XAMPP/Windows)
- ✅ Production (DigitalOcean/Linux)
- ✅ No code changes needed

### **2. Secure**
- ✅ Uses environment variables
- ✅ Prepared statements (SQL injection protection)
- ✅ Proper error handling

### **3. Easy to Use**
- ✅ Simple function: `getPDOConnection()`
- ✅ Automatic port detection
- ✅ Consistent with existing MySQLi code

---

## 🔄 MIGRATION FROM HARDCODED VALUES

### **Before (Hardcoded - ❌ Not Secure):**

```php
$host = 'localhost';
$db   = 'pngmc_db';
$user = 'pngmc_user';
$pass = 'StrongPasswordHere';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, $options);
```

### **After (Environment Variables - ✅ Secure):**

```php
require_once 'pages/includes/pdo_config.php';
$pdo = getPDOConnection();
// That's it! Automatically uses .env file
```

---

## 📋 QUICK REFERENCE

### **File Location:**
- `pages/includes/pdo_config.php` - PDO configuration file

### **Function:**
- `getPDOConnection()` - Returns PDO connection object
- `testPDOConnection()` - Tests connection (returns true/false)

### **Environment Variables:**
- `DB_HOST` - Database host (default: localhost)
- `DB_PORT` - Database port (auto-detected: 3307 Windows, 3306 Linux)
- `DB_USER` - Database username
- `DB_PASS` - Database password
- `DB_NAME` - Database name (default: sms2_db)
- `DB_CHARSET` - Character set (default: utf8mb4)

---

## ✅ CHECKLIST

- [ ] `.env` file created with database credentials
- [ ] `pages/includes/pdo_config.php` included in your file
- [ ] Using `getPDOConnection()` function
- [ ] All queries use prepared statements
- [ ] Error handling implemented
- [ ] Tested on local environment
- [ ] Tested on DigitalOcean (production)

---

**Your PDO connection is now ready for both local and DigitalOcean!** 🚀

