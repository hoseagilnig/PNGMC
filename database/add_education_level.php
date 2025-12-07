<?php
/**
 * Migration Script to Add education_level Column to applications table
 * This script adds support for Grade 10 and Grade 12 students
 * 
 * Access via: http://localhost/sms2/database/add_education_level.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_column'])) {
    $conn = getDBConnection();
    
    if (!$conn) {
        $message = "Database connection failed!";
        $message_type = "error";
    } else {
        // Check if column already exists
        $check_column = $conn->query("SHOW COLUMNS FROM applications LIKE 'education_level'");
        
        if ($check_column->num_rows > 0) {
            $message = "Column 'education_level' already exists in the applications table.";
            $message_type = "success";
        } else {
            // Add education_level column
            $sql = "ALTER TABLE applications ADD COLUMN education_level ENUM('Grade 10', 'Grade 12') NULL AFTER province";
            
            if ($conn->query($sql)) {
                $message = "Successfully added 'education_level' column to applications table.";
                $message_type = "success";
            } else {
                $message = "Error adding column: " . $conn->error;
                $message_type = "error";
            }
        }
        
        $conn->close();
    }
} else {
    // Check if column exists
    $conn = getDBConnection();
    if ($conn) {
        $check_column = $conn->query("SHOW COLUMNS FROM applications LIKE 'education_level'");
        $column_exists = $check_column->num_rows > 0;
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Education Level Column</title>
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
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }
        button:hover {
            background: #163c6a;
        }
        .status {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #1d4e89;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Education Level Column</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($column_exists)): ?>
            <div class="status">
                <strong>Current Status:</strong><br>
                <?php if ($column_exists): ?>
                    ✓ Column 'education_level' exists in the applications table.
                <?php else: ?>
                    ✗ Column 'education_level' does not exist. Click the button below to add it.
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <p>This script adds the <code>education_level</code> column to the <code>applications</code> table to support both Grade 10 and Grade 12 student applications.</p>
        
        <?php if (!isset($column_exists) || !$column_exists): ?>
            <form method="POST">
                <button type="submit" name="add_column">Add Education Level Column</button>
            </form>
        <?php else: ?>
            <div class="message info">
                The column already exists. No action needed.
            </div>
        <?php endif; ?>
        
        <p style="margin-top: 30px;">
            <a href="../apply.php">← Back to Application Form</a>
        </p>
    </div>
</body>
</html>

