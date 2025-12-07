<?php
/**
 * Create Workflow Management Tables
 * This creates tables for cross-department workflow, notifications, and audit trail
 * 
 * Access via: http://localhost/sms2/database/create_workflow_tables.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$message = '';
$message_type = '';
$tables_created = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_tables'])) {
    $conn = getDBConnection();
    
    if (!$conn) {
        $message = "Database connection failed!";
        $message_type = "error";
    } else {
        // Add HOD role to users table if it doesn't exist
        $conn->query("ALTER TABLE users MODIFY role ENUM('admin', 'finance', 'studentservices', 'hod') NOT NULL");
        
        // Workflow Notifications Table
        $sql = "CREATE TABLE IF NOT EXISTS workflow_notifications (
            notification_id INT AUTO_INCREMENT PRIMARY KEY,
            application_id INT NULL,
            from_department ENUM('studentservices', 'finance', 'admin', 'hod') NOT NULL,
            to_department ENUM('studentservices', 'finance', 'admin', 'hod') NOT NULL,
            notification_type ENUM('action_required', 'status_update', 'approval_request', 'information', 'urgent') DEFAULT 'action_required',
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            action_url VARCHAR(500),
            status ENUM('unread', 'read', 'action_taken') DEFAULT 'unread',
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            read_at TIMESTAMP NULL,
            action_taken_at TIMESTAMP NULL,
            FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_application_id (application_id),
            INDEX idx_to_department (to_department),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            $tables_created[] = 'workflow_notifications';
        }
        
        // Workflow Actions (Audit Trail) Table
        $sql = "CREATE TABLE IF NOT EXISTS workflow_actions (
            action_id INT AUTO_INCREMENT PRIMARY KEY,
            application_id INT NOT NULL,
            action_type ENUM('status_change', 'assessment', 'hod_decision', 'correspondence', 'check_update', 'enrollment', 'finance_approval', 'note_added') NOT NULL,
            from_status VARCHAR(50),
            to_status VARCHAR(50),
            performed_by INT NOT NULL,
            performed_by_department ENUM('studentservices', 'finance', 'admin', 'hod') NOT NULL,
            action_description TEXT NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE,
            FOREIGN KEY (performed_by) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_application_id (application_id),
            INDEX idx_performed_by (performed_by),
            INDEX idx_performed_by_department (performed_by_department),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            $tables_created[] = 'workflow_actions';
        }
        
        // Add workflow tracking columns to applications table
        $columns_to_add = [
            'current_department' => "ENUM('studentservices', 'finance', 'admin', 'hod') NULL",
            'workflow_stage' => "VARCHAR(50) DEFAULT 'submitted'",
            'last_action_at' => "TIMESTAMP NULL",
            'last_action_by' => "INT NULL",
            'finance_approval_status' => "ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'",
            'finance_approval_date' => "DATE NULL",
            'finance_approval_by' => "INT NULL",
            'finance_notes' => "TEXT NULL"
        ];
        
        foreach ($columns_to_add as $column => $definition) {
            $check = $conn->query("SHOW COLUMNS FROM applications LIKE '$column'");
            if ($check->num_rows == 0) {
                $conn->query("ALTER TABLE applications ADD COLUMN $column $definition");
            }
        }
        
        // Add foreign key for last_action_by
        $fk_check = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'applications' AND COLUMN_NAME = 'last_action_by' AND CONSTRAINT_NAME LIKE 'fk_%'");
        if ($fk_check->num_rows == 0) {
            $conn->query("ALTER TABLE applications ADD CONSTRAINT fk_last_action_by FOREIGN KEY (last_action_by) REFERENCES users(user_id) ON DELETE SET NULL");
        }
        
        if (!empty($tables_created)) {
            $message = "Workflow tables created successfully: " . implode(', ', $tables_created);
            $message_type = "success";
        } else {
            $message = "All tables already exist or there was an error.";
            $message_type = "info";
        }
        
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Workflow Tables</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d4e89;
        }
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        button {
            background: #1d4e89;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #163c6a;
        }
        ul {
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create Workflow Management Tables</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <p>This script will create the following tables and columns:</p>
        <ul>
            <li><strong>workflow_notifications</strong> - Cross-department notifications system</li>
            <li><strong>workflow_actions</strong> - Complete audit trail of all workflow actions</li>
            <li><strong>Applications table updates</strong> - Add workflow tracking columns:
                <ul>
                    <li>current_department</li>
                    <li>workflow_stage</li>
                    <li>last_action_at</li>
                    <li>last_action_by</li>
                    <li>finance_approval_status</li>
                    <li>finance_approval_date</li>
                    <li>finance_approval_by</li>
                    <li>finance_notes</li>
                </ul>
            </li>
            <li><strong>Users table update</strong> - Add 'hod' role</li>
        </ul>
        
        <form method="POST">
            <button type="submit" name="create_tables">Create Workflow Tables</button>
        </form>
    </div>
</body>
</html>

