<?php
/**
 * Migration Script to Add Profile Photo Column to Students Table
 * Adds profile_photo_path column for storing student profile photos
 * 
 * Access via: http://localhost/sms2/database/add_student_photo_column.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$message = '';
$message_type = '';
$changes_made = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_column'])) {
    $conn = getDBConnection();
    
    if (!$conn) {
        $message = "Database connection failed!";
        $message_type = "error";
    } else {
        // Check if column already exists
        $col_check = $conn->query("SHOW COLUMNS FROM students LIKE 'profile_photo_path'");
        if ($col_check->num_rows == 0) {
            $sql = "ALTER TABLE students ADD COLUMN profile_photo_path VARCHAR(500) NULL AFTER updated_at";
            if ($conn->query($sql)) {
                $changes_made[] = "Added 'profile_photo_path' column to students table";
                $message = "Successfully added profile_photo_path column to students table!";
                $message_type = "success";
            } else {
                $message = "Error adding column: " . $conn->error;
                $message_type = "error";
            }
        } else {
            $changes_made[] = "'profile_photo_path' column already exists in students table";
            $message = "Column already exists. No changes needed.";
            $message_type = "info";
        }
        
        // Create uploads/student_photos directory if it doesn't exist
        $upload_dir = __DIR__ . '/../uploads/student_photos';
        if (!file_exists($upload_dir)) {
            if (mkdir($upload_dir, 0755, true)) {
                $changes_made[] = "Created uploads/student_photos directory";
            } else {
                $changes_made[] = "Warning: Could not create uploads/student_photos directory. Please create it manually.";
            }
        } else {
            $changes_made[] = "uploads/student_photos directory already exists";
        }
        
        $conn->close();
    }
} else {
    // Check current status
    $conn = getDBConnection();
    if ($conn) {
        $col_check = $conn->query("SHOW COLUMNS FROM students LIKE 'profile_photo_path'");
        $column_exists = $col_check->num_rows > 0;
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student Photo Column - Migration</title>
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
            margin-bottom: 20px;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .changes-list {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .changes-list ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .changes-list li {
            margin: 5px 0;
        }
        .btn {
            background: #1d4e89;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #153d6b;
        }
        .status {
            padding: 10px;
            background: #e9ecef;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Student Profile Photo Column</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($column_exists)): ?>
            <div class="status">
                <strong>Current Status:</strong><br>
                Profile Photo Column: <?php echo $column_exists ? '✅ Exists' : '❌ Not Found'; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($changes_made)): ?>
            <div class="changes-list">
                <strong>Changes Made:</strong>
                <ul>
                    <?php foreach ($changes_made as $change): ?>
                        <li><?php echo htmlspecialchars($change); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <p>This migration will:</p>
            <ul>
                <li>Add <code>profile_photo_path</code> column to the <code>students</code> table</li>
                <li>Create <code>uploads/student_photos</code> directory for storing photos</li>
            </ul>
            
            <button type="submit" name="add_column" class="btn">Run Migration</button>
        </form>
        
        <p style="margin-top: 30px; color: #666; font-size: 0.9rem;">
            <a href="../student_profile.php" style="color: #1d4e89;">Go to Student Profile Page</a> | 
            <a href="../student_dashboard.php" style="color: #1d4e89;">Go to Student Dashboard</a>
        </p>
    </div>
</body>
</html>

