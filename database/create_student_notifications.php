<?php
/**
 * Student Notifications System
 * Creates table for student notifications from Administration, Finance, and SAS
 * 
 * Access via: http://localhost/sms2/database/create_student_notifications.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$message = '';
$message_type = '';
$changes_made = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_table'])) {
    $conn = getDBConnection();
    
    if (!$conn) {
        $message = "Database connection failed!";
        $message_type = "error";
    } else {
        // Create student_notifications table
        $table_check = $conn->query("SHOW TABLES LIKE 'student_notifications'");
        if ($table_check->num_rows == 0) {
            $sql = "CREATE TABLE IF NOT EXISTS student_notifications (
                notification_id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                student_number VARCHAR(20) NOT NULL,
                from_department ENUM('admin', 'finance', 'studentservices') NOT NULL,
                notification_type ENUM('info', 'warning', 'important', 'action_required', 'payment', 'academic', 'general') DEFAULT 'info',
                title VARCHAR(200) NOT NULL,
                message TEXT NOT NULL,
                action_url VARCHAR(500) NULL,
                status ENUM('unread', 'read') DEFAULT 'unread',
                read_at TIMESTAMP NULL,
                created_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
                INDEX idx_student_id (student_id),
                INDEX idx_student_number (student_number),
                INDEX idx_status (status),
                INDEX idx_from_department (from_department),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if ($conn->query($sql)) {
                $changes_made[] = "Created 'student_notifications' table";
                $message = "Student notifications system created successfully!";
                $message_type = "success";
            } else {
                $message = "Error creating table: " . $conn->error;
                $message_type = "error";
            }
        } else {
            $changes_made[] = "'student_notifications' table already exists";
            $message = "Student notifications table already exists.";
            $message_type = "info";
        }
        
        $conn->close();
    }
} else {
    // Check current state
    $conn = getDBConnection();
    if ($conn) {
        $table_check = $conn->query("SHOW TABLES LIKE 'student_notifications'");
        $has_table = $table_check->num_rows > 0;
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Student Notifications System</title>
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
    <h1>Student Notifications System Setup</h1>
    
    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <div class="message info">
        <h3>What this script does:</h3>
        <ul>
            <li>Creates <code>student_notifications</code> table for storing notifications to students</li>
            <li>Supports notifications from: Administration, Finance, Student Admin Service</li>
            <li>Notification types: info, warning, important, action_required, payment, academic, general</li>
            <li>Tracks read/unread status</li>
        </ul>
    </div>
    
    <?php if (isset($has_table)): ?>
        <div class="message info">
            <h3>Current System Status:</h3>
            <ul>
                <li>student_notifications table: <?php echo $has_table ? '✅ Exists' : '❌ Missing'; ?></li>
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
        <button type="submit" name="create_table" class="btn" onclick="return confirm('This will create the student notifications table. Continue?');">
            🔧 Create Student Notifications Table
        </button>
    </form>
    
    <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
        <h3>Features:</h3>
        <ul>
            <li><strong>Notifications from Staff:</strong> Administration, Finance, and Student Admin Service can send notifications</li>
            <li><strong>Notification Types:</strong> Info, Warning, Important, Action Required, Payment, Academic, General</li>
            <li><strong>Student Dashboard:</strong> Students can view and manage notifications</li>
            <li><strong>Read/Unread Status:</strong> Track which notifications students have seen</li>
        </ul>
    </div>
</body>
</html>

