<?php
/**
 * Setup Script for Application Workflow Tables
 * This script will create the application workflow tables
 * 
 * Access via: http://localhost/sms2/database/setup_application_tables.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Application Tables</title>
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
            padding: 10px;
            margin: 10px 0;
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
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Setup Application Workflow Tables</h1>
        
        <?php
        $conn = getDBConnection();
        
        if (!$conn) {
            echo '<div class="status error">✗ Database connection failed! Please check your database configuration.</div>';
        } else {
            // Check if tables already exist
            $tables_exist = true;
            $required_tables = ['applications', 'application_documents', 'mandatory_checks', 'correspondence', 'application_notes'];
            $missing_tables = [];
            
            foreach ($required_tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result->num_rows === 0) {
                    $tables_exist = false;
                    $missing_tables[] = $table;
                }
            }
            
            if ($tables_exist) {
                echo '<div class="status success">✓ All application workflow tables already exist!</div>';
                echo '<div class="info">The application workflow system is ready to use.</div>';
            } else {
                echo '<div class="status error">✗ Missing tables: ' . implode(', ', $missing_tables) . '</div>';
                echo '<div class="info">';
                echo '<strong>To fix this, you need to import the application workflow tables.</strong><br><br>';
                echo '<strong>Option 1: Using phpMyAdmin</strong><br>';
                echo '1. Open phpMyAdmin<br>';
                echo '2. Select the <strong>sms2_db</strong> database<br>';
                echo '3. Click on "Import" tab<br>';
                echo '4. Choose the file: <strong>database/application_workflow_tables.sql</strong><br>';
                echo '5. Click "Go"<br><br>';
                
                echo '<strong>Option 2: Using MySQL Command Line</strong><br>';
                echo '<pre>mysql -u root -p sms2_db < database/application_workflow_tables.sql</pre>';
                
                echo '<strong>Option 3: Copy and paste the SQL</strong><br>';
                echo 'Open <strong>database/application_workflow_tables.sql</strong> and copy all the SQL commands, then paste and execute them in phpMyAdmin SQL tab.';
                echo '</div>';
            }
            
            $conn->close();
        }
        ?>
        
        <a href="../pages/login.php" class="btn" style="text-decoration: none; display: inline-block;">← Back to Login</a>
    </div>
</body>
</html>

