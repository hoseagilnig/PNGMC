<?php
/**
 * Database Connection Test Script
 * This script tests the database connection and displays connection status
 * 
 * Access via: http://localhost/sms2/database/test_connection.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #1d4e89;
            color: white;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #1d4e89;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-link:hover {
            background: #163c6a;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Connection Test</h1>
        
        <?php
        // Test 1: Configuration Check
        echo '<div class="info"><strong>Configuration:</strong><br>';
        echo 'Host: ' . DB_HOST . '<br>';
        echo 'Database: ' . DB_NAME . '<br>';
        echo 'User: ' . DB_USER . '<br>';
        echo 'Charset: ' . DB_CHARSET . '</div>';
        
        // Test 2: Connection Test
        echo '<h2>Connection Test</h2>';
        $conn = getDBConnection();
        
        if ($conn) {
            echo '<div class="status success">✓ Database connection successful!</div>';
            
            // Test 3: Database Selection
            echo '<h2>Database Information</h2>';
            $result = $conn->query("SELECT DATABASE() as db_name");
            if ($result) {
                $row = $result->fetch_assoc();
                echo '<div class="info">Connected to database: <strong>' . htmlspecialchars($row['db_name']) . '</strong></div>';
            }
            
            // Test 4: Check Tables
            echo '<h2>Database Tables</h2>';
            $result = $conn->query("SHOW TABLES");
            if ($result && $result->num_rows > 0) {
                echo '<table>';
                echo '<tr><th>Table Name</th><th>Status</th></tr>';
                while ($row = $result->fetch_array()) {
                    $tableName = $row[0];
                    $countResult = $conn->query("SELECT COUNT(*) as count FROM `$tableName`");
                    $count = $countResult ? $countResult->fetch_assoc()['count'] : 0;
                    echo '<tr><td>' . htmlspecialchars($tableName) . '</td><td>' . $count . ' records</td></tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="status error">✗ No tables found. Please import the database schema.</div>';
            }
            
            // Test 5: Check Users Table
            echo '<h2>Users Table Check</h2>';
            $result = $conn->query("SELECT COUNT(*) as count FROM users");
            if ($result) {
                $row = $result->fetch_assoc();
                $userCount = $row['count'];
                echo '<div class="info">Total users in database: <strong>' . $userCount . '</strong></div>';
                
                if ($userCount > 0) {
                    echo '<h3>Sample Users:</h3>';
                    $result = $conn->query("SELECT username, full_name, role, status FROM users LIMIT 5");
                    if ($result && $result->num_rows > 0) {
                        echo '<table>';
                        echo '<tr><th>Username</th><th>Full Name</th><th>Role</th><th>Status</th></tr>';
                        while ($row = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['username']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['role']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                } else {
                    echo '<div class="status error">No users found. Please import the database schema.</div>';
                }
            }
            
            // Test 6: Password Hash Check
            echo '<h2>Password Hash Check</h2>';
            $result = $conn->query("SELECT username, password_hash FROM users LIMIT 1");
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $hash = $row['password_hash'];
                if (strlen($hash) >= 60 && (substr($hash, 0, 4) === '$2y$' || substr($hash, 0, 4) === '$2a$')) {
                    echo '<div class="status success">✓ Password hashes appear to be properly formatted (bcrypt)</div>';
                } else {
                    echo '<div class="status error">⚠ Password hashes may need to be updated. Run: php database/update_passwords.php</div>';
                }
            }
            
            $conn->close();
        } else {
            echo '<div class="status error">✗ Database connection failed!</div>';
            echo '<div class="info">';
            echo '<strong>Possible issues:</strong><br>';
            echo '1. MySQL server is not running<br>';
            echo '2. Database credentials in db_config.php are incorrect<br>';
            echo '3. Database "sms2_db" does not exist (import sms2_database.sql first)<br>';
            echo '4. MySQL user does not have permission to access the database';
            echo '</div>';
        }
        ?>
        
        <a href="../pages/login.php" class="back-link">← Back to Login</a>
    </div>
</body>
</html>

