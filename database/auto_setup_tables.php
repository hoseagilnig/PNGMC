<?php
/**
 * Auto Setup Script for Application Workflow Tables
 * This script will automatically create the application workflow tables
 * 
 * Access via: http://localhost/sms2/database/auto_setup_tables.php
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
        // Read and execute the SQL file
        $sql_file = __DIR__ . '/application_workflow_tables.sql';
        
        if (!file_exists($sql_file)) {
            $message = "SQL file not found: application_workflow_tables.sql";
            $message_type = "error";
        } else {
            $sql = file_get_contents($sql_file);
            
            // Remove comments and USE statement
            $sql = preg_replace('/^--.*$/m', '', $sql);
            $sql = preg_replace('/^USE\s+\w+\s*;/mi', '', $sql);
            
            // Execute using multi_query for better handling
            $success = true;
            $errors = [];
            
            // Split into individual statements
            $statements = preg_split('/;\s*(?=CREATE|ALTER|INSERT|UPDATE|DELETE)/i', $sql);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement)) {
                    continue;
                }
                
                // Add semicolon if not present
                if (substr($statement, -1) !== ';') {
                    $statement .= ';';
                }
                
                if (!$conn->query($statement)) {
                    // Ignore "table already exists" errors (1050) and duplicate key errors
                    if ($conn->errno != 1050 && $conn->errno != 1061 && $conn->errno != 1062) {
                        $errors[] = $conn->error;
                        $success = false;
                    }
                } else {
                    // Extract table name if CREATE TABLE
                    if (preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?`?(\w+)`?/i', $statement, $matches)) {
                        $tables_created[] = $matches[1];
                    }
                }
            }
            
            if ($success && count($tables_created) > 0) {
                $message = "Successfully created " . count($tables_created) . " table(s): " . implode(', ', array_unique($tables_created));
                $message_type = "success";
            } else if (count($errors) > 0) {
                $message = "Some errors occurred: " . implode('<br>', array_slice($errors, 0, 5));
                $message_type = "error";
            } else {
                $message = "Tables processed. Some may already exist (this is normal).";
                $message_type = "success";
            }
        }
        
        $conn->close();
    }
}

// Check current status
$conn = getDBConnection();
$tables_status = [];
$required_tables = ['applications', 'application_documents', 'mandatory_checks', 'correspondence', 'application_notes'];

if ($conn) {
    foreach ($required_tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        $tables_status[$table] = $result->num_rows > 0;
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Setup Application Tables</title>
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
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d4e89;
            margin-bottom: 20px;
        }
        .status {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            font-weight: bold;
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
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .btn {
            padding: 12px 24px;
            background: #1d4e89;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            margin-top: 20px;
        }
        .btn:hover {
            background: #163c6a;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #1d4e89;
            color: white;
        }
        .check-mark {
            color: #28a745;
            font-weight: bold;
        }
        .cross-mark {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Auto Setup Application Workflow Tables</h1>
        
        <?php if ($message): ?>
          <div class="status <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="info">
            <strong>Current Table Status:</strong>
            <table>
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables_status as $table => $exists): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($table); ?></td>
                            <td>
                                <?php if ($exists): ?>
                                    <span class="check-mark">✓ Exists</span>
                                <?php else: ?>
                                    <span class="cross-mark">✗ Missing</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (in_array(false, $tables_status)): ?>
            <div class="info">
                <strong>Missing tables detected!</strong><br><br>
                Click the button below to automatically create all missing application workflow tables.
                This will create:
                <ul>
                    <li>applications - Main application records</li>
                    <li>application_documents - Document tracking</li>
                    <li>mandatory_checks - Mandatory checks tracking</li>
                    <li>correspondence - Correspondence history</li>
                    <li>application_notes - Internal notes</li>
                </ul>
            </div>

            <form method="POST">
                <button type="submit" name="create_tables" class="btn">Create Missing Tables</button>
            </form>
        <?php else: ?>
            <div class="status success">
                ✓ All application workflow tables exist! The system is ready to use.
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <a href="../apply.php" class="btn" style="text-decoration: none; display: inline-block; margin-right: 10px;">← Back to Application Form</a>
            <a href="../pages/login.php" class="btn" style="text-decoration: none; display: inline-block;">Go to Login</a>
        </div>
    </div>
</body>
</html>

