<?php
/**
 * Quick Test Student Login Page
 * Direct access to create and login with test account
 * 
 * Access via: http://localhost/sms2/test_student_login.php
 */

require_once 'pages/includes/db_config.php';
require_once 'pages/includes/student_account_helper.php';

$message = '';
$message_type = '';
$credentials = null;

// Auto-create test account if it doesn't exist
$conn = getDBConnection();
if ($conn) {
    // Check if test student exists
    $test_student = $conn->query("SELECT * FROM students WHERE student_number = 'TEST-2025-0001'");
    
    if (!$test_student || $test_student->num_rows == 0) {
        // Create test student
        $student_number = 'TEST-2025-0001';
        $first_name = 'Test';
        $last_name = 'Student';
        $email = 'test.student@pngmc.ac.pg';
        $phone = '1234567890';
        $password = 'test123';
        
        $stmt = $conn->prepare("INSERT INTO students (student_number, first_name, last_name, email, phone, enrollment_date, status, account_status) VALUES (?, ?, ?, ?, ?, CURDATE(), 'active', 'active')");
        $stmt->bind_param("sssss", $student_number, $first_name, $last_name, $email, $phone);
        $stmt->execute();
        $student_id = $conn->insert_id;
        $stmt->close();
        
        // Create account
        createStudentAccount($student_id, $student_number, $email, $phone, $password);
        $account = getStudentAccount($student_id);
        
        $credentials = [
            'student_number' => $student_number,
            'username' => $account['username'],
            'password' => $password,
            'name' => $first_name . ' ' . $last_name
        ];
    } else {
        // Get existing test account
        $student = $test_student->fetch_assoc();
        $account = getStudentAccount($student['student_id']);
        
        if ($account) {
            $credentials = [
                'student_number' => $student['student_number'],
                'username' => $account['username'],
                'password' => 'test123', // Default password
                'name' => $student['first_name'] . ' ' . $student['last_name']
            ];
        }
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Student Login - PNG Maritime College</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1d4e89 0%, #2e7d32 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo img {
            max-width: 150px;
        }
        h1 {
            text-align: center;
            color: #1d4e89;
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 0.9rem;
        }
        .credentials-box {
            background: #f0f7ff;
            border: 2px solid #1d4e89;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
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
        .btn {
            width: 100%;
            padding: 14px;
            background: #1d4e89;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: block;
            text-align: center;
            margin-top: 15px;
        }
        .btn:hover {
            background: #163c6a;
        }
        .btn-secondary {
            background: #6c757d;
            margin-top: 10px;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="images/pnmc.png" alt="PNG Maritime College">
        </div>
        <h1>🧪 Test Student Account</h1>
        <p class="subtitle">Mock account for testing the student portal</p>
        
        <?php if ($credentials): ?>
            <div class="credentials-box">
                <h2 style="margin-top: 0; color: #1d4e89; font-size: 1.3rem;">Login Credentials</h2>
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
            </div>
            
            <a href="student_login.php" class="btn">🔗 Login to Student Portal</a>
            <a href="index.html" class="btn btn-secondary">← Back to Homepage</a>
        <?php else: ?>
            <div class="info-box">
                <strong>Error:</strong> Could not create or retrieve test account. Please ensure the student accounts system is set up.
                <br><br>
                <a href="database/create_student_accounts_system.php" style="color: #1d4e89; font-weight: bold;">Set up Student Accounts System →</a>
            </div>
        <?php endif; ?>
        
        <div class="info-box" style="margin-top: 20px;">
            <strong>Note:</strong> This test account is automatically created when you visit this page. 
            Use it to test all student portal features.
        </div>
    </div>
</body>
</html>

