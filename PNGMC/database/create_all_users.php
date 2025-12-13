<?php
/**
 * Create All Users Script
 * This script creates all default users (Admin, Finance, Student Services, and HOD) in the database
 * 
 * Access via: http://localhost/sms2/database/create_all_users.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$message = '';
$message_type = '';
$users_created = [];
$users_updated = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_users'])) {
    $conn = getDBConnection();
    
    if (!$conn) {
        $message = "Database connection failed!";
        $message_type = "error";
    } else {
        // First, ensure HOD role exists in users table
        $conn->query("ALTER TABLE users MODIFY role ENUM('admin', 'finance', 'studentservices', 'hod') NOT NULL");
        
        // All default users
        $all_users = [
            // Administration users
            ['username' => 'admin01', 'password' => 'adminpass1', 'full_name' => 'Alice Admin', 'role' => 'admin'],
            ['username' => 'admin02', 'password' => 'adminpass2', 'full_name' => 'Bob Admin', 'role' => 'admin'],
            ['username' => 'admin03', 'password' => 'adminpass3', 'full_name' => 'Carol Admin', 'role' => 'admin'],
            
            // Finance users
            ['username' => 'finance01', 'password' => 'financepass1', 'full_name' => 'Frank Finance', 'role' => 'finance'],
            ['username' => 'finance02', 'password' => 'financepass2', 'full_name' => 'Fiona Finance', 'role' => 'finance'],
            ['username' => 'finance03', 'password' => 'financepass3', 'full_name' => 'Fred Finance', 'role' => 'finance'],
            
            // Student Services users
            ['username' => 'service01', 'password' => 'servicepass1', 'full_name' => 'Sam Service', 'role' => 'studentservices'],
            ['username' => 'service02', 'password' => 'servicepass2', 'full_name' => 'Sally Service', 'role' => 'studentservices'],
            ['username' => 'service03', 'password' => 'servicepass3', 'full_name' => 'Sue Service', 'role' => 'studentservices'],
            
            // HOD users
            ['username' => 'hod01', 'password' => 'hodpass1', 'full_name' => 'Head of Department 1', 'role' => 'hod'],
            ['username' => 'hod02', 'password' => 'hodpass2', 'full_name' => 'Head of Department 2', 'role' => 'hod'],
            ['username' => 'hod03', 'password' => 'hodpass3', 'full_name' => 'Head of Department 3', 'role' => 'hod'],
        ];
        
        foreach ($all_users as $user) {
            // Check if user already exists
            $check = $conn->query("SELECT user_id, password_hash FROM users WHERE username = '{$user['username']}'");
            
            if ($check->num_rows == 0) {
                // User doesn't exist, create it
                $password_hash = password_hash($user['password'], PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, role, status) VALUES (?, ?, ?, ?, 'active')");
                $stmt->bind_param("ssss", $user['username'], $password_hash, $user['full_name'], $user['role']);
                
                if ($stmt->execute()) {
                    $users_created[] = $user['username'];
                }
                $stmt->close();
            } else {
                // User exists, update password if it's a placeholder
                $existing = $check->fetch_assoc();
                $placeholder_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
                
                if ($existing['password_hash'] === $placeholder_hash) {
                    // Update with proper hash
                    $password_hash = password_hash($user['password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
                    $stmt->bind_param("ss", $password_hash, $user['username']);
                    
                    if ($stmt->execute()) {
                        $users_updated[] = $user['username'];
                    }
                    $stmt->close();
                }
            }
        }
        
        if (!empty($users_created) || !empty($users_updated)) {
            $msg_parts = [];
            if (!empty($users_created)) {
                $msg_parts[] = "Created: " . implode(', ', $users_created);
            }
            if (!empty($users_updated)) {
                $msg_parts[] = "Updated passwords: " . implode(', ', $users_updated);
            }
            $message = implode(". ", $msg_parts);
            $message_type = "success";
        } else {
            $message = "All users already exist with proper passwords.";
            $message_type = "info";
        }
        
        $conn->close();
    }
}

// Get current users from database
$conn = getDBConnection();
$existing_users = [];
if ($conn) {
    $result = $conn->query("SELECT username, full_name, role, status FROM users ORDER BY role, username");
    if ($result) {
        $existing_users = $result->fetch_all(MYSQLI_ASSOC);
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create All Users</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
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
            margin-right: 10px;
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
            margin-top: 10px;
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
        .existing-users {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
        }
        .user-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 0.85rem;
            margin: 2px;
        }
        .badge-admin { background: #007bff; color: white; }
        .badge-finance { background: #28a745; color: white; }
        .badge-studentservices { background: #ffc107; color: #000; }
        .badge-hod { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create All Default Users</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <p>This script will create all default users for the system:</p>
        
        <div class="credentials">
            <h3>Default Users to be Created:</h3>
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
                    <tr><td colspan="4" style="background: #e9ecef; font-weight: bold;">Administration</td></tr>
                    <tr><td><strong>admin01</strong></td><td><strong>adminpass1</strong></td><td>Alice Admin</td><td>Admin</td></tr>
                    <tr><td><strong>admin02</strong></td><td><strong>adminpass2</strong></td><td>Bob Admin</td><td>Admin</td></tr>
                    <tr><td><strong>admin03</strong></td><td><strong>adminpass3</strong></td><td>Carol Admin</td><td>Admin</td></tr>
                    
                    <tr><td colspan="4" style="background: #e9ecef; font-weight: bold;">Finance</td></tr>
                    <tr><td><strong>finance01</strong></td><td><strong>financepass1</strong></td><td>Frank Finance</td><td>Finance</td></tr>
                    <tr><td><strong>finance02</strong></td><td><strong>financepass2</strong></td><td>Fiona Finance</td><td>Finance</td></tr>
                    <tr><td><strong>finance03</strong></td><td><strong>financepass3</strong></td><td>Fred Finance</td><td>Finance</td></tr>
                    
                    <tr><td colspan="4" style="background: #e9ecef; font-weight: bold;">Student Services</td></tr>
                    <tr><td><strong>service01</strong></td><td><strong>servicepass1</strong></td><td>Sam Service</td><td>Student Services</td></tr>
                    <tr><td><strong>service02</strong></td><td><strong>servicepass2</strong></td><td>Sally Service</td><td>Student Services</td></tr>
                    <tr><td><strong>service03</strong></td><td><strong>servicepass3</strong></td><td>Sue Service</td><td>Student Services</td></tr>
                    
                    <tr><td colspan="4" style="background: #e9ecef; font-weight: bold;">Head of Department (HOD)</td></tr>
                    <tr><td><strong>hod01</strong></td><td><strong>hodpass1</strong></td><td>Head of Department 1</td><td>HOD</td></tr>
                    <tr><td><strong>hod02</strong></td><td><strong>hodpass2</strong></td><td>Head of Department 2</td><td>HOD</td></tr>
                    <tr><td><strong>hod03</strong></td><td><strong>hodpass3</strong></td><td>Head of Department 3</td><td>HOD</td></tr>
                </tbody>
            </table>
        </div>
        
        <p><strong>Note:</strong> This script will:</p>
        <ul>
            <li>Create users that don't exist</li>
            <li>Update passwords for users with placeholder hashes</li>
            <li>Skip users that already exist with proper passwords</li>
        </ul>
        
        <form method="POST">
            <button type="submit" name="create_users">Create/Update All Users</button>
            <a href="check_users.php" style="color: #1d4e89; text-decoration: underline;">Check Existing Users</a>
        </form>
        
        <?php if (!empty($existing_users)): ?>
        <div class="existing-users">
            <h3>Current Users in Database (<?php echo count($existing_users); ?>)</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px;">
                <?php foreach ($existing_users as $user): ?>
                    <div class="user-badge badge-<?php echo $user['role']; ?>">
                        <?php echo htmlspecialchars($user['username']); ?> 
                        (<?php echo htmlspecialchars($user['role']); ?>)
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

