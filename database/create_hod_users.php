<?php
/**
 * Create HOD Users Script
 * This script creates default Head of Department users in the database
 * 
 * Access via: http://localhost/sms2/database/create_hod_users.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$message = '';
$message_type = '';
$users_created = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_users'])) {
    $conn = getDBConnection();
    
    if (!$conn) {
        $message = "Database connection failed!";
        $message_type = "error";
    } else {
        // First, ensure HOD role exists in users table
        $conn->query("ALTER TABLE users MODIFY role ENUM('admin', 'finance', 'studentservices', 'hod') NOT NULL");
        
        // Default HOD users
        $hod_users = [
            ['username' => 'hod01', 'password' => 'hodpass1', 'full_name' => 'Head of Department 1', 'role' => 'hod'],
            ['username' => 'hod02', 'password' => 'hodpass2', 'full_name' => 'Head of Department 2', 'role' => 'hod'],
            ['username' => 'hod03', 'password' => 'hodpass3', 'full_name' => 'Head of Department 3', 'role' => 'hod'],
        ];
        
        foreach ($hod_users as $user) {
            // Check if user already exists
            $check = $conn->query("SELECT user_id FROM users WHERE username = '{$user['username']}'");
            
            if ($check->num_rows == 0) {
                // Hash password
                $password_hash = password_hash($user['password'], PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, role, status) VALUES (?, ?, ?, ?, 'active')");
                $stmt->bind_param("ssss", $user['username'], $password_hash, $user['full_name'], $user['role']);
                
                if ($stmt->execute()) {
                    $users_created[] = $user['username'];
                }
                $stmt->close();
            }
        }
        
        if (!empty($users_created)) {
            $message = "HOD users created successfully: " . implode(', ', $users_created);
            $message_type = "success";
        } else {
            $message = "All HOD users already exist or there was an error.";
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
    <title>Create HOD Users</title>
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
        .credentials {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .credentials table {
            width: 100%;
            border-collapse: collapse;
        }
        .credentials th, .credentials td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .credentials th {
            background: #1d4e89;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create HOD (Head of Department) Users</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <p>This script will create the following HOD users:</p>
        
        <div class="credentials">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Full Name</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>hod01</strong></td>
                        <td><strong>hodpass1</strong></td>
                        <td>Head of Department 1</td>
                        <td>HOD</td>
                    </tr>
                    <tr>
                        <td><strong>hod02</strong></td>
                        <td><strong>hodpass2</strong></td>
                        <td>Head of Department 2</td>
                        <td>HOD</td>
                    </tr>
                    <tr>
                        <td><strong>hod03</strong></td>
                        <td><strong>hodpass3</strong></td>
                        <td>Head of Department 3</td>
                        <td>HOD</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <p><strong>Note:</strong> If users already exist, they will not be overwritten.</p>
        
        <form method="POST">
            <button type="submit" name="create_users">Create HOD Users</button>
        </form>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <h3>Default Login Credentials Summary</h3>
            <p><strong>Administration:</strong></p>
            <ul>
                <li>admin01 / adminpass1</li>
                <li>admin02 / adminpass2</li>
                <li>admin03 / adminpass3</li>
            </ul>
            
            <p><strong>Finance:</strong></p>
            <ul>
                <li>finance01 / financepass1</li>
                <li>finance02 / financepass2</li>
                <li>finance03 / financepass3</li>
            </ul>
            
            <p><strong>Student Services:</strong></p>
            <ul>
                <li>service01 / servicepass1</li>
                <li>service02 / servicepass2</li>
                <li>service03 / servicepass3</li>
            </ul>
            
            <p><strong>Head of Department (HOD):</strong></p>
            <ul>
                <li>hod01 / hodpass1</li>
                <li>hod02 / hodpass2</li>
                <li>hod03 / hodpass3</li>
            </ul>
        </div>
    </div>
</body>
</html>

