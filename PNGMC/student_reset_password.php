<?php
session_start();
require_once 'pages/includes/db_config.php';
require_once 'pages/includes/student_account_helper.php';

$error = '';
$message = '';
$step = 'request'; // 'request', 'reset'

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $username = trim($_POST['username'] ?? '');
    
    if (empty($username)) {
        $error = "Please enter your username or student number.";
    } else {
        $conn = getDBConnection();
        if ($conn) {
            $table_check = $conn->query("SHOW TABLES LIKE 'student_accounts'");
            if ($table_check->num_rows > 0) {
                $username_escaped = $conn->real_escape_string($username);
                $stmt = $conn->prepare("SELECT sa.*, s.email, s.first_name, s.last_name 
                    FROM student_accounts sa 
                    LEFT JOIN students s ON sa.student_id = s.student_id 
                    WHERE sa.username = ? OR sa.student_number = ?");
                $stmt->bind_param("ss", $username, $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $account = $result->fetch_assoc();
                    
                    // Generate reset token
                    $reset_token = bin2hex(random_bytes(32));
                    $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Store token in database
                    $update_stmt = $conn->prepare("UPDATE student_accounts SET password_reset_token = ?, password_reset_expires = ? WHERE account_id = ?");
                    $update_stmt->bind_param("ssi", $reset_token, $reset_expires, $account['account_id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    // TODO: Send email with reset link
                    // For now, show the reset link directly
                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/student_reset_password.php?token=" . $reset_token;
                    
                    $message = "Password reset link generated. Please use the link below to reset your password (valid for 1 hour):";
                    $message .= "<br><br><strong><a href='$reset_link' style='word-break: break-all;'>$reset_link</a></strong>";
                    $message .= "<br><br><small>Note: Email functionality is not yet configured. Please copy this link to reset your password.</small>";
                } else {
                    $error = "Account not found. Please check your username or student number.";
                }
                $stmt->close();
            } else {
                $error = "Student account system is not yet set up.";
            }
            $conn->close();
        }
    }
}

// Handle password reset with token
if (isset($_GET['token'])) {
    $step = 'reset';
    $token = $_GET['token'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($new_password) || empty($confirm_password)) {
            $error = "Please enter both password fields.";
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            $conn = getDBConnection();
            if ($conn) {
                $token_escaped = $conn->real_escape_string($token);
                $result = $conn->query("SELECT * FROM student_accounts WHERE password_reset_token = '$token_escaped' AND password_reset_expires > NOW()");
                
                if ($result && $result->num_rows === 1) {
                    $account = $result->fetch_assoc();
                    
                    // Update password
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE student_accounts SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE account_id = ?");
                    $update_stmt->bind_param("si", $password_hash, $account['account_id']);
                    
                    if ($update_stmt->execute()) {
                        $message = "Password reset successfully! You can now login with your new password.";
                        $step = 'success';
                    } else {
                        $error = "Error updating password. Please try again.";
                    }
                    $update_stmt->close();
                } else {
                    $error = "Invalid or expired reset token. Please request a new password reset.";
                    $step = 'request';
                }
                $conn->close();
            }
        }
    } else {
        // Verify token is valid
        $conn = getDBConnection();
        if ($conn) {
            $token_escaped = $conn->real_escape_string($token);
            $result = $conn->query("SELECT * FROM student_accounts WHERE password_reset_token = '$token_escaped' AND password_reset_expires > NOW()");
            
            if (!$result || $result->num_rows === 0) {
                $error = "Invalid or expired reset token. Please request a new password reset.";
                $step = 'request';
            }
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - PNG Maritime College</title>
    <style>
        body {
            background: linear-gradient(135deg, #1d4e89 0%, #2e7d32 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }
        .reset-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
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
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
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
        }
        .btn:hover {
            background: #163c6a;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .back-to-login {
            text-align: center;
            margin-top: 15px;
        }
        .back-to-login a {
            color: #1d4e89;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .back-to-login a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="logo">
            <img src="images/pnmc.png" alt="PNG Maritime College">
        </div>
        <h1>Reset Password</h1>
        <p class="subtitle">Student Portal Password Reset</p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($step === 'request'): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username / Student Number</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                <button type="submit" name="request_reset" class="btn">Request Password Reset</button>
            </form>
            <div class="back-to-login">
                <a href="student_login.php">← Back to Login</a>
            </div>
        <?php elseif ($step === 'reset'): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                    <small style="color: #666;">Must be at least 6 characters</small>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                <button type="submit" name="reset_password" class="btn">Reset Password</button>
            </form>
            <div class="back-to-login">
                <a href="student_login.php">← Back to Login</a>
            </div>
        <?php elseif ($step === 'success'): ?>
            <div class="back-to-login">
                <a href="student_login.php" class="btn">Go to Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

