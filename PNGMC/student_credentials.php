<?php
/**
 * Student Credentials Display Page
 * Shows login credentials to students after enrollment
 * Can be accessed via: student_credentials.php?student_id=X or student_credentials.php?student_number=STU-XXXX
 */

session_start();
require_once 'pages/includes/db_config.php';
require_once 'pages/includes/student_account_helper.php';

$student = null;
$account = null;
$message = '';
$message_type = '';

// Get student by ID or student number
$student_id = $_GET['student_id'] ?? null;
$student_number = $_GET['student_number'] ?? null;

if ($student_id || $student_number) {
    $conn = getDBConnection();
    if ($conn) {
        if ($student_id) {
            $result = $conn->query("SELECT * FROM students WHERE student_id = " . intval($student_id));
        } else {
            $student_number_escaped = $conn->real_escape_string($student_number);
            $result = $conn->query("SELECT * FROM students WHERE student_number = '$student_number_escaped'");
        }
        
        if ($result && $result->num_rows > 0) {
            $student = $result->fetch_assoc();
            $student_id = $student['student_id'];
            
            // Get account details
            $account = getStudentAccount($student_id);
        }
        
        $conn->close();
    }
}

// If no student found, show error
if (!$student) {
    $message = "Student not found. Please check your student ID or student number.";
    $message_type = "error";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login Credentials - PNG Maritime College</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1d4e89 0%, #2e7d32 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .credentials-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 600px;
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
            margin-bottom: 20px;
        }
        .credential-item {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .credential-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
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
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .warning-box strong {
            color: #856404;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #1d4e89;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .btn:hover {
            background: #163c6a;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
    <div class="credentials-container">
        <div class="logo">
            <img src="images/pnmc.png" alt="PNG Maritime College">
        </div>
        <h1>Student Portal Credentials</h1>
        <p class="subtitle">Your login information for the Student Portal</p>
        
        <?php if ($message): ?>
            <div class="message error">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($student && $account): ?>
            <div class="credentials-box">
                <div class="credential-item">
                    <div class="credential-label">Student Name</div>
                    <div class="credential-value"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Student Number</div>
                    <div class="credential-value"><?php echo htmlspecialchars($student['student_number']); ?></div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Username</div>
                    <div class="credential-value"><?php echo htmlspecialchars($account['username']); ?></div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Default Password</div>
                    <div class="credential-value"><?php echo htmlspecialchars($student['student_number'] . substr(str_pad($student['student_id'], 4, '0', STR_PAD_LEFT), -4)); ?></div>
                </div>
            </div>
            
            <div class="warning-box">
                <strong>⚠️ Important:</strong> Please save these credentials securely. You will need them to access your student portal. 
                We recommend changing your password after your first login.
            </div>
            
            <a href="student_login.php" class="btn">Login to Student Portal</a>
            <a href="index.html" class="btn btn-secondary" style="margin-top: 10px;">Back to Homepage</a>
            
            <div class="info-box">
                <strong>Need Help?</strong><br>
                If you have trouble logging in, please contact Student Admin Service for assistance.
            </div>
        <?php elseif ($student && !$account): ?>
            <div class="message error">
                <strong>Account Not Created Yet</strong><br>
                Your student account has not been created yet. Accounts are automatically created when you are enrolled. 
                Please contact Student Admin Service if you believe this is an error.
            </div>
            <a href="index.html" class="btn btn-secondary">Back to Homepage</a>
        <?php endif; ?>
    </div>
</body>
</html>

