<?php
/**
 * Create Test Student Account
 * Creates a mock student account for testing the student portal
 * 
 * Access via: http://localhost/sms2/database/create_test_student_account.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';
require_once __DIR__ . '/../pages/includes/student_account_helper.php';

$message = '';
$message_type = '';
$credentials = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_test_account'])) {
    $conn = getDBConnection();
    
    if (!$conn) {
        $message = "Database connection failed!";
        $message_type = "error";
    } else {
        // Check if student_accounts table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'student_accounts'");
        if ($table_check->num_rows == 0) {
            $message = "Student accounts system is not set up yet. Please run create_student_accounts_system.php first.";
            $message_type = "error";
        } else {
            // Check if test student already exists
            $test_student = $conn->query("SELECT * FROM students WHERE student_number = 'TEST-2025-0001'");
            
            if ($test_student && $test_student->num_rows > 0) {
                // Update existing test student
                $student = $test_student->fetch_assoc();
                $student_id = $student['student_id'];
                
                // Check if account exists
                $account_check = $conn->query("SELECT * FROM student_accounts WHERE student_id = $student_id");
                if ($account_check && $account_check->num_rows > 0) {
                    // Reset password
                    $password = 'test123';
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $conn->query("UPDATE student_accounts SET password_hash = '$password_hash', account_status = 'active', last_login = NULL WHERE student_id = $student_id");
                    
                    $account = $account_check->fetch_assoc();
                    $credentials = [
                        'student_id' => $student_id,
                        'student_number' => $student['student_number'],
                        'username' => $account['username'],
                        'password' => $password,
                        'name' => $student['first_name'] . ' ' . $student['last_name']
                    ];
                    
                    $message = "Test student account updated successfully!";
                    $message_type = "success";
                } else {
                    // Create account
                    $password = 'test123';
                    if (createStudentAccount($student_id, $student['student_number'], $student['email'], $student['phone'], $password)) {
                        $account = getStudentAccount($student_id);
                        $credentials = [
                            'student_id' => $student_id,
                            'student_number' => $student['student_number'],
                            'username' => $account['username'],
                            'password' => $password,
                            'name' => $student['first_name'] . ' ' . $student['last_name']
                        ];
                        
                        $message = "Test student account created successfully!";
                        $message_type = "success";
                    }
                }
                
                // Update student status
                $conn->query("UPDATE students SET account_status = 'active', status = 'active' WHERE student_id = $student_id");
            } else {
                // Create new test student
                $student_number = 'TEST-2025-0001';
                $first_name = 'Test';
                $last_name = 'Student';
                $email = 'test.student@pngmc.ac.pg';
                $phone = '1234567890';
                $password = 'test123';
                
                // Insert student
                $stmt = $conn->prepare("INSERT INTO students (student_number, first_name, last_name, email, phone, enrollment_date, status, account_status) VALUES (?, ?, ?, ?, ?, CURDATE(), 'active', 'active')");
                $stmt->bind_param("sssss", $student_number, $first_name, $last_name, $email, $phone);
                $stmt->execute();
                $student_id = $conn->insert_id;
                $stmt->close();
                
                // Create account
                if (createStudentAccount($student_id, $student_number, $email, $phone, $password)) {
                    $account = getStudentAccount($student_id);
                    $credentials = [
                        'student_id' => $student_id,
                        'student_number' => $student_number,
                        'username' => $account['username'],
                        'password' => $password,
                        'name' => $first_name . ' ' . $last_name
                    ];
                    
                    $message = "Test student account created successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error creating student account.";
                    $message_type = "error";
                }
            }
        }
        
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Test Student Account</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 800px; margin: 0 auto; background: #f5f5f5; }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #1d4e89; margin-bottom: 20px; }
        .message { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .btn { padding: 12px 24px; background: #1d4e89; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; font-weight: 600; }
        .btn:hover { background: #163c6a; }
        .credentials-box {
            background: #f0f7ff;
            border: 2px solid #1d4e89;
            border-radius: 8px;
            padding: 25px;
            margin: 20px 0;
        }
        .credential-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }
        .credential-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .credential-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .credential-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: #1d4e89;
            font-family: 'Courier New', monospace;
            background: white;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .login-link {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
        }
        .login-link:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Create Test Student Account</h1>
        
        <div class="message info">
            <strong>Purpose:</strong> This script creates a mock student account for testing the student portal login and dashboard functionality.
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($credentials): ?>
            <div class="credentials-box">
                <h2 style="margin-top: 0; color: #1d4e89;">Test Account Credentials</h2>
                <div class="credential-item">
                    <div class="credential-label">Student Name</div>
                    <div class="credential-value"><?php echo htmlspecialchars($credentials['name']); ?></div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Student Number</div>
                    <div class="credential-value"><?php echo htmlspecialchars($credentials['student_number']); ?></div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Username</div>
                    <div class="credential-value"><?php echo htmlspecialchars($credentials['username']); ?></div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Password</div>
                    <div class="credential-value"><?php echo htmlspecialchars($credentials['password']); ?></div>
                </div>
                <a href="../student_login.php" class="login-link">🔗 Login to Student Portal →</a>
            </div>
        <?php endif; ?>
        
        <form method="POST" style="margin-top: 30px;">
            <button type="submit" name="create_test_account" class="btn">
                <?php echo $credentials ? '🔄 Reset Test Account' : '✨ Create Test Student Account'; ?>
            </button>
        </form>
        
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <h3>Test Account Details:</h3>
            <ul style="line-height: 1.8;">
                <li><strong>Student Number:</strong> TEST-2025-0001</li>
                <li><strong>Name:</strong> Test Student</li>
                <li><strong>Email:</strong> test.student@pngmc.ac.pg</li>
                <li><strong>Password:</strong> test123</li>
                <li><strong>Status:</strong> Active</li>
            </ul>
            <p style="margin-top: 15px; color: #666; font-size: 0.9rem;">
                <strong>Note:</strong> You can use this account to test all student portal features including notifications, course history, and account management.
            </p>
        </div>
    </div>
</body>
</html>

