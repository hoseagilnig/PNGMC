<?php
/**
 * Student Accounts System Migration
 * Creates student account system for enrolled students
 * 
 * Access via: http://localhost/sms2/database/create_student_accounts_system.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$message = '';
$message_type = '';
$changes_made = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_system'])) {
    $conn = getDBConnection();
    
    if (!$conn) {
        $message = "Database connection failed!";
        $message_type = "error";
    } else {
        // 1. Add account_status column to students table
        $col_check = $conn->query("SHOW COLUMNS FROM students LIKE 'account_status'");
        if ($col_check->num_rows == 0) {
            $sql = "ALTER TABLE students ADD COLUMN account_status ENUM('active', 'on_hold', 'suspended', 'inactive') DEFAULT 'active' AFTER status";
            if ($conn->query($sql)) {
                $changes_made[] = "Added 'account_status' column to students table";
            }
        } else {
            $changes_made[] = "'account_status' column already exists in students table";
        }
        
        // 2. Add course_end_date to students table (to track when account should go on hold)
        $col_check = $conn->query("SHOW COLUMNS FROM students LIKE 'course_end_date'");
        if ($col_check->num_rows == 0) {
            $sql = "ALTER TABLE students ADD COLUMN course_end_date DATE NULL AFTER enrollment_date";
            if ($conn->query($sql)) {
                $changes_made[] = "Added 'course_end_date' column to students table";
            }
        } else {
            $changes_made[] = "'course_end_date' column already exists in students table";
        }
        
        // 3. Create student_accounts table
        $table_check = $conn->query("SHOW TABLES LIKE 'student_accounts'");
        if ($table_check->num_rows == 0) {
            $sql = "CREATE TABLE IF NOT EXISTS student_accounts (
                account_id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                student_number VARCHAR(20) UNIQUE NOT NULL,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                phone VARCHAR(20),
                account_status ENUM('active', 'on_hold', 'suspended', 'inactive') DEFAULT 'active',
                last_login TIMESTAMP NULL,
                password_reset_token VARCHAR(100) NULL,
                password_reset_expires TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
                INDEX idx_student_id (student_id),
                INDEX idx_student_number (student_number),
                INDEX idx_username (username),
                INDEX idx_account_status (account_status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if ($conn->query($sql)) {
                $changes_made[] = "Created 'student_accounts' table";
            }
        } else {
            $changes_made[] = "'student_accounts' table already exists";
        }
        
        // 4. Create student_course_history table (to track all courses a student has enrolled in)
        $table_check = $conn->query("SHOW TABLES LIKE 'student_course_history'");
        if ($table_check->num_rows == 0) {
            $sql = "CREATE TABLE IF NOT EXISTS student_course_history (
                history_id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                student_number VARCHAR(20) NOT NULL,
                course_name VARCHAR(200) NOT NULL,
                course_type ENUM('Nautical', 'Engineering', 'Other') NULL,
                enrollment_date DATE NOT NULL,
                course_end_date DATE NULL,
                completion_date DATE NULL,
                status ENUM('enrolled', 'completed', 'withdrawn', 'suspended') DEFAULT 'enrolled',
                application_id INT NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
                FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE SET NULL,
                INDEX idx_student_id (student_id),
                INDEX idx_student_number (student_number),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if ($conn->query($sql)) {
                $changes_made[] = "Created 'student_course_history' table";
            }
        } else {
            $changes_made[] = "'student_course_history' table already exists";
        }
        
        // 5. Update existing enrolled students to have account_status = 'active'
        $conn->query("UPDATE students SET account_status = 'active' WHERE status = 'active' AND account_status IS NULL");
        $affected = $conn->affected_rows;
        if ($affected > 0) {
            $changes_made[] = "Updated $affected existing students to have account_status = 'active'";
        }
        
        // 6. Set account_status to 'on_hold' for graduated/completed students
        $conn->query("UPDATE students SET account_status = 'on_hold' WHERE status IN ('graduated', 'inactive') AND account_status IS NULL");
        $affected = $conn->affected_rows;
        if ($affected > 0) {
            $changes_made[] = "Updated $affected completed/graduated students to have account_status = 'on_hold'";
        }
        
        $message = "Student accounts system created successfully!";
        $message_type = "success";
        $conn->close();
    }
} else {
    // Check current state
    $conn = getDBConnection();
    if ($conn) {
        $col_check = $conn->query("SHOW COLUMNS FROM students LIKE 'account_status'");
        $has_account_status = $col_check->num_rows > 0;
        
        $table_check = $conn->query("SHOW TABLES LIKE 'student_accounts'");
        $has_accounts_table = $table_check->num_rows > 0;
        
        $table_check = $conn->query("SHOW TABLES LIKE 'student_course_history'");
        $has_history_table = $table_check->num_rows > 0;
        
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Student Accounts System</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 800px; margin: 0 auto; }
        .message { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .btn { padding: 10px 20px; background: #1d4e89; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; }
        .btn:hover { background: #163c6a; }
        ul { line-height: 1.8; }
    </style>
</head>
<body>
    <h1>Student Accounts System Setup</h1>
    
    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <div class="message info">
        <h3>What this script does:</h3>
        <ul>
            <li>Adds <code>account_status</code> column to <code>students</code> table (active, on_hold, suspended, inactive)</li>
            <li>Adds <code>course_end_date</code> column to track when course ends</li>
            <li>Creates <code>student_accounts</code> table for student login credentials</li>
            <li>Creates <code>student_course_history</code> table to track all courses enrolled</li>
            <li>Updates existing students with appropriate account status</li>
        </ul>
    </div>
    
    <?php if (isset($has_account_status)): ?>
        <div class="message info">
            <h3>Current System Status:</h3>
            <ul>
                <li>account_status column: <?php echo $has_account_status ? '✅ Exists' : '❌ Missing'; ?></li>
                <li>student_accounts table: <?php echo $has_accounts_table ? '✅ Exists' : '❌ Missing'; ?></li>
                <li>student_course_history table: <?php echo $has_history_table ? '✅ Exists' : '❌ Missing'; ?></li>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($changes_made)): ?>
        <div class="message success">
            <h3>Changes Made:</h3>
            <ul>
                <?php foreach ($changes_made as $change): ?>
                    <li><?php echo htmlspecialchars($change); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" style="margin-top: 30px;">
        <button type="submit" name="create_system" class="btn" onclick="return confirm('This will create the student accounts system. Continue?');">
            🔧 Create Student Accounts System
        </button>
    </form>
    
    <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
        <h3>Features:</h3>
        <ul>
            <li><strong>Student Accounts:</strong> Each enrolled student gets a login account</li>
            <li><strong>Account Status:</strong> active, on_hold (after course ends), suspended, inactive</li>
            <li><strong>Course History:</strong> Track all courses a student has enrolled in</li>
            <li><strong>Returning Students:</strong> Accounts can be reactivated when they return</li>
            <li><strong>Automatic Status:</strong> Accounts automatically go on hold when course ends</li>
        </ul>
    </div>
</body>
</html>

