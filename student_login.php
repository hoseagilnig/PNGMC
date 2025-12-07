<?php
session_start();
require_once 'pages/includes/db_config.php';
require_once 'pages/includes/security_helper.php';
require_once 'pages/includes/student_account_helper.php';

$error = '';
$message = '';

// Check if already logged in
if (isset($_SESSION['student_loggedin']) && $_SESSION['student_loggedin'] === true) {
    header('Location: student_dashboard.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = "Please enter both username and password.";
        } else {
        $conn = getDBConnection();
        if ($conn) {
            // Check if student_accounts table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'student_accounts'");
            if ($table_check->num_rows > 0) {
                $stmt = $conn->prepare("SELECT sa.*, s.first_name, s.last_name, s.student_number, s.account_status as student_account_status 
                    FROM student_accounts sa 
                    LEFT JOIN students s ON sa.student_id = s.student_id 
                    WHERE sa.username = ? AND sa.account_status = 'active'");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $account = $result->fetch_assoc();
                    
                    // Verify password
                    if (password_verify($password, $account['password_hash'])) {
                        // Check if student account_status is also active
                        if ($account['student_account_status'] === 'active' || $account['student_account_status'] === 'on_hold') {
                            // Login successful
                            $_SESSION['student_loggedin'] = true;
                            $_SESSION['student_id'] = $account['student_id'];
                            $_SESSION['student_number'] = $account['student_number'];
                            $_SESSION['student_name'] = $account['first_name'] . ' ' . $account['last_name'];
                            $_SESSION['student_username'] = $account['username'];
                            
                            // Update last login
                            $conn->query("UPDATE student_accounts SET last_login = CURRENT_TIMESTAMP WHERE account_id = {$account['account_id']}");
                            
                            header('Location: student_dashboard.php');
                            exit;
                        } else {
                            $error = "Your account is currently inactive. Please contact Student Admin Service.";
                        }
                    } else {
                        $error = "Invalid username or password.";
                    }
                } else {
                    $error = "Invalid username or password.";
                }
                $stmt->close();
            } else {
                $error = "Student account system is not yet set up. Please contact administration.";
            }
            $conn->close();
        } else {
            $error = "Database connection error. Please try again later.";
        }
    }
}

// Redirect to password reset page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    header('Location: student_reset_password.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - PNG Maritime College</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body {
            background: linear-gradient(135deg, #1d4e89 0%, #2e7d32 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: Arial, sans-serif;
        }
        header {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 15px 30px;
        }
        header .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            font-size: 1.3rem;
            font-weight: 700;
            transition: transform 0.3s ease;
        }
        header .logo:hover {
            transform: scale(1.05);
        }
        header .logo img {
            height: 40px;
            width: auto;
        }
        .login-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            position: relative;
            z-index: 1;
        }
        .login-container {
            background: white;
            padding: 25px 30px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
            box-sizing: border-box;
            animation: slideUp 0.6s ease-out;
            position: relative;
            overflow: hidden;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .login-container .logo {
            text-align: center;
            margin-bottom: 15px;
        }
        .login-container .logo img {
            max-width: 90px;
            margin-bottom: 8px;
        }
        h1 {
            text-align: center;
            color: #1d4e89;
            margin-bottom: 5px;
            font-size: 24px;
            font-weight: 700;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
            font-size: 12px;
        }
        .form-group {
            margin-bottom: 12px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            transition: all 0.3s ease;
            background: white;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #1d4e89;
            box-shadow: 0 0 0 4px rgba(29, 78, 137, 0.1);
            transform: translateY(-2px);
        }
        
        input[type="text"]:hover,
        input[type="password"]:hover {
            border-color: #b0b0b0;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #1d4e89 0%, #163c6a 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(29, 78, 137, 0.3);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29, 78, 137, 0.4);
            background: linear-gradient(135deg, #163c6a 0%, #1d4e89 100%);
        }
        .btn:active {
            transform: translateY(0);
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        .message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .forgot-password {
            text-align: center;
            margin-top: 15px;
        }
        .forgot-password a {
            color: #1d4e89;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .forgot-password a:hover {
            text-decoration: underline;
        }
        .reset-form {
            display: none;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
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
    </style>
</head>
<body>
    <header>
        <a href="index.html" class="logo">
            <img src="images/pnmc.png" alt="PNG Maritime College Logo">
            <span>PNG Maritime College</span>
        </a>
    </header>
    
    <section class="login-section">
        <div class="login-container">
            <div class="logo">
                <img src="images/pnmc.png" alt="PNG Maritime College">
            </div>
        <h1>Student Portal</h1>
        <p class="subtitle">Login to access your student account</p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <form id="loginForm" method="POST" autocomplete="off">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <!-- Hidden dummy fields to trick browser autofill -->
            <input type="text" style="position: absolute; left: -9999px;" tabindex="-1" autocomplete="off">
            <input type="password" style="position: absolute; left: -9999px;" tabindex="-1" autocomplete="off">
            
            <div class="form-group">
                <label for="username">Username / Student Number</label>
                <input type="text" id="username" name="username" required autofocus autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
            </div>
            
            <button type="submit" name="login" class="btn">Login</button>
        </form>
        
        <div class="forgot-password">
            <a href="student_reset_password.php">Forgot Password?</a>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 0.85rem;">
            <p>Don't have an account? Your account will be created automatically when you are enrolled.</p>
            <p>For assistance, contact Student Admin Service.</p>
        </div>
        </div>
    </section>
</body>
</html>

