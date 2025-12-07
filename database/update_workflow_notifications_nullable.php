<?php
/**
 * Update workflow_notifications table to allow NULL application_id
 * This allows notifications for non-application related items (like Finance to SAS transfers)
 * 
 * Access via: http://localhost/sms2/database/update_workflow_notifications_nullable.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_table'])) {
    $conn = getDBConnection();
    
    if (!$conn) {
        $message = "Database connection failed!";
        $message_type = "error";
    } else {
        // Check if table exists
        $table_exists = $conn->query("SHOW TABLES LIKE 'workflow_notifications'")->num_rows > 0;
        
        if ($table_exists) {
            // Check current column definition
            $col_result = $conn->query("SHOW COLUMNS FROM workflow_notifications WHERE Field = 'application_id'");
            $col_info = $col_result->fetch_assoc();
            $is_nullable = strtoupper($col_info['Null']) === 'YES';
            
            if ($is_nullable) {
                $message = "The application_id column already allows NULL values. No update needed.";
                $message_type = "info";
            } else {
                // Find and drop foreign key constraint(s) first
                $fk_result = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'workflow_notifications' 
                    AND COLUMN_NAME = 'application_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL");
                
                $fk_dropped = true;
                $fk_names = [];
                if ($fk_result && $fk_result->num_rows > 0) {
                    while ($row = $fk_result->fetch_assoc()) {
                        $fk_names[] = $row['CONSTRAINT_NAME'];
                    }
                }
                
                // Drop all foreign key constraints
                foreach ($fk_names as $fk_name) {
                    if (!$conn->query("ALTER TABLE workflow_notifications DROP FOREIGN KEY `$fk_name`")) {
                        $fk_dropped = false;
                        $message = "Error dropping foreign key constraint '$fk_name': " . $conn->error;
                        $message_type = "error";
                        break;
                    }
                }
                
                if ($fk_dropped) {
                    // Modify application_id to allow NULL
                    if ($conn->query("ALTER TABLE workflow_notifications MODIFY application_id INT NULL")) {
                        // Re-add foreign key constraint if we dropped one
                        if (!empty($fk_names)) {
                            $conn->query("ALTER TABLE workflow_notifications ADD CONSTRAINT workflow_notifications_ibfk_1 FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE");
                        }
                        
                        $message = "workflow_notifications table updated successfully! application_id can now be NULL for non-application notifications.";
                        $message_type = "success";
                    } else {
                        $message = "Error updating table: " . $conn->error;
                        $message_type = "error";
                    }
                }
            }
        } else {
            $message = "workflow_notifications table does not exist. Please run create_workflow_tables.php first.";
            $message_type = "error";
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
    <title>Update Workflow Notifications Table</title>
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
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #1d4e89;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Update Workflow Notifications Table</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <strong>ℹ️ About This Update:</strong>
            <p>This script updates the <code>workflow_notifications</code> table to allow NULL values for <code>application_id</code>. This enables notifications for non-application related items, such as Finance to SAS data transfers.</p>
        </div>
        
        <p><strong>Note:</strong> This update is required for the Finance to SAS data transfer notification system to work properly.</p>
        
        <form method="POST">
            <button type="submit" name="update_table">Update Table</button>
        </form>
    </div>
</body>
</html>

