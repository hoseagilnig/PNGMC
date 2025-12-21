<?php
// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/security_helper.php';

$error = '';
$popup_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token'])) {
        $error = 'Security token missing. Please refresh the page and try again.';
    } elseif (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $usertype = isset($_POST['usertype']) ? $_POST['usertype'] : '';

        if ($username === '' || $password === '' || $usertype === '') {
            $error = 'Please fill in all fields.';
        } else {
            // Connect to database
            $conn = getDBConnection();
            
            if (!$conn) {
                $error = 'Database connection failed. Please contact administrator.';
            } else {
                // First check if user exists (without role filter for better debugging)
                $check_stmt = $conn->prepare("SELECT user_id, username, password_hash, full_name, role, status FROM users WHERE username = ?");
                $check_stmt->bind_param("s", $username);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows === 0) {
                    $error = 'Username not found.';
                    $check_stmt->close();
                } else {
                    $user = $check_result->fetch_assoc();
                    $check_stmt->close();
                    
                    // Check if role matches
                    if ($user['role'] !== $usertype) {
                        $popup_message = 'Password and Username does not match';
                        $error = '';
                    } elseif ($user['status'] !== 'active') {
                        $error = 'Account is inactive. Please contact administrator.';
                    } else {
                        // Verify password
                        if (password_verify($password, $user['password_hash'])) {
                            // Check rate limiting
                            require_once __DIR__ . '/includes/security_helper.php';
                            
                            if (!checkLoginRateLimit($username)) {
                                $remaining_time = 300 - (time() - ($_SESSION['login_attempts_' . md5($username)]['time'] ?? time()));
                                $error = "Too many login attempts. Please try again in " . ceil($remaining_time / 60) . " minutes.";
                            } else {
                                // Successful login
                                // Regenerate session ID to prevent fixation
                                session_regenerate_id(true);
                                
                                // Initialize secure session
                                initSecureSession();
                                
                                $_SESSION['loggedin'] = true;
                                $_SESSION['user_id'] = $user['user_id'];
                                $_SESSION['username'] = $user['username'];
                                $_SESSION['name'] = $user['full_name'];
                                $_SESSION['role'] = $user['role'];
                                
                                // Clear failed login attempts
                                clearLoginAttempts($username);
                                
                                // Log successful login
                                logSecurityEvent('LOGIN_SUCCESS', "User: {$user['username']}");
                                
                                // Update last login timestamp
                                $update_stmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?");
                                $update_stmt->bind_param("i", $user['user_id']);
                                $update_stmt->execute();
                                $update_stmt->close();
                            
                                // Redirect to role dashboard (using normalized role)
                                $normalized_role = strtolower(trim($user['role']));
                                // Handle aliases
                                if ($normalized_role === 'sas' || $normalized_role === 'student_services' || $normalized_role === 'student services') {
                                    $normalized_role = 'studentservices';
                                }
                                
                                if ($normalized_role === 'admin') {
                                    header('Location: admin_dashboard.php');
                                    exit;
                                } elseif ($normalized_role === 'finance') {
                                    header('Location: finance_dashboard.php');
                                    exit;
                                } elseif ($normalized_role === 'studentservices') {
                                    header('Location: student_service_dashboard.php');
                                    exit;
                                } elseif ($normalized_role === 'hod') {
                                    header('Location: hod_dashboard.php');
                                    exit;
                                } else {
                                    $error = 'Unknown role: ' . htmlspecialchars($user['role']);
                                }
                            } // Close successful login block (else from rate limit check)
                        } else {
                            // Record failed login attempt
                            require_once __DIR__ . '/includes/security_helper.php';
                            recordFailedLogin($username);
                            
                            $remaining = getRemainingLoginAttempts($username);
                            if ($remaining <= 0) {
                                $error = 'Too many failed login attempts. Please try again later.';
                            } else {
                                $error = 'Password and Username does not match. ' . $remaining . ' attempt(s) remaining.';
                            }
                            
                            // Log failed login
                            logSecurityEvent('LOGIN_FAILED', "User: $username");
                        } // Close password_verify else
                    } // Close role check else
                } // Close user found else
                
                $conn->close();
            } // Close database connection else
        } // Close username/password/usertype else
    } // Close CSRF token else
} // Close POST request if
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - PNG Maritime College Students Portal</title>
  
  <!-- Cross-browser compatibility CSS -->
  <link rel="stylesheet" href="../css/browser-compat.css">
  <link rel="stylesheet" href="../css/sms_styles.css">
  <link rel="stylesheet" href="../css/responsive.css">
  
  <!-- Polyfill for older browsers -->
  <script src="https://polyfill.io/v3/polyfill.min.js?features=default,es5,es6,es2015,es2016,es2017,Array.prototype.includes,Object.assign,Promise,fetch,Element.prototype.closest,Element.prototype.matches"></script>
  <style>
    body {
      background: linear-gradient(135deg, #1d4e89 0%, #163c6a 50%, #0f2a4a 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      position: relative;
      overflow-x: hidden;
    }

    /* Animated background waves */
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.05" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
      background-size: cover;
      z-index: 0;
      animation: waveMove 20s ease-in-out infinite;
    }

    @keyframes waveMove {
      0%, 100% { transform: translateX(0) translateY(0); }
      50% { transform: translateX(-50px) translateY(-20px); }
    }

    header {
      position: relative;
      z-index: 10;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
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
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.2);
      padding: 20px 25px;
      max-width: 380px;
      width: 100%;
      animation: slideUp 0.6s ease-out;
      position: relative;
      overflow: hidden;
    }
    
    @media (max-width: 480px) {
      .login-container {
        max-width: 90%;
        padding: 18px 20px;
      }
    }

    .login-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
      background: linear-gradient(90deg, #1d4e89 0%, #28a745 50%, #1d4e89 100%);
      background-size: 200% 100%;
      animation: gradientShift 3s ease infinite;
    }

    @keyframes gradientShift {
      0%, 100% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
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

    .login-container h2 {
      text-align: center;
      color: #1d4e89;
      font-size: 22px;
      font-weight: 700;
      margin: 0 0 5px 0;
      letter-spacing: 0.5px;
    }
    
    @media (max-width: 480px) {
      .login-container h2 {
        font-size: 20px;
      }
    }

    .login-container .subtitle {
      text-align: center;
      color: #666;
      font-size: 12px;
      margin-bottom: 20px;
    }

    .login-container form {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .login-container label {
      color: #333;
      font-weight: 600;
      font-size: 14px;
      margin-bottom: 8px;
      display: block;
      letter-spacing: 0.3px;
    }

    .login-container input[type="text"],
    .login-container input[type="password"],
    .login-container select {
      width: 100%;
      padding: 10px 15px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s ease;
      background: white;
      box-sizing: border-box;
      font-family: inherit;
    }

    .login-container input[type="text"]:focus,
    .login-container input[type="password"]:focus,
    .login-container select:focus {
      outline: none;
      border-color: #1d4e89;
      box-shadow: 0 0 0 4px rgba(29, 78, 137, 0.1);
      transform: translateY(-2px);
    }

    .login-container input[type="text"]:hover,
    .login-container input[type="password"]:hover,
    .login-container select:hover {
      border-color: #b0b0b0;
    }

    .login-container select {
      cursor: pointer;
      appearance: none;
      background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"><path fill="%231d4e89" d="M6 9L1 4h10z"/></svg>');
      background-repeat: no-repeat;
      background-position: right 18px center;
      padding-right: 45px;
    }

    .error-msg {
      background: #fee;
      color: #c33;
      padding: 12px 18px;
      border-radius: 10px;
      border-left: 4px solid #dc3545;
      font-size: 14px;
      margin-bottom: 20px;
      animation: shake 0.5s ease;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-10px); }
      75% { transform: translateX(10px); }
    }

    .login-container .cta-btn {
      background: linear-gradient(135deg, #1d4e89 0%, #163c6a 100%);
      color: white;
      border: none;
      padding: 12px;
      border-radius: 8px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(29, 78, 137, 0.3);
      letter-spacing: 0.5px;
      margin-top: 10px;
      text-transform: uppercase;
    }

    .login-container .cta-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(29, 78, 137, 0.4);
      background: linear-gradient(135deg, #163c6a 0%, #1d4e89 100%);
    }

    .login-container .cta-btn:active {
      transform: translateY(0);
    }

    /* Custom Styled Modal for Login */
    .custom-modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(5px);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 10000;
      opacity: 0;
      animation: fadeIn 0.3s ease-out forwards;
    }

    .custom-modal {
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1);
      max-width: 450px;
      width: 90%;
      position: relative;
      transform: scale(0.9) translateY(20px);
      animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    .custom-modal.error {
      border-top: 5px solid #dc3545;
    }

    .custom-modal-header {
      padding: 25px 30px 20px;
      text-align: center;
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }

    .custom-modal-icon {
      width: 70px;
      height: 70px;
      margin: 0 auto 15px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 35px;
      animation: iconBounce 0.6s ease-out 0.2s both;
      background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
      color: white;
      box-shadow: 0 10px 30px rgba(220, 53, 69, 0.3);
    }

    .custom-modal-title {
      font-size: 24px;
      font-weight: 700;
      color: #1d4e89;
      margin: 0;
      letter-spacing: 0.5px;
    }

    .custom-modal-body {
      padding: 25px 30px;
      color: #333;
      line-height: 1.8;
      font-size: 15px;
      text-align: center;
    }

    .custom-modal-footer {
      padding: 20px 30px 25px;
      text-align: center;
      border-top: 1px solid rgba(0, 0, 0, 0.1);
    }

    .custom-modal-btn {
      background: linear-gradient(135deg, #1d4e89 0%, #163c6a 100%);
      color: white;
      border: none;
      padding: 14px 40px;
      border-radius: 50px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(29, 78, 137, 0.3);
      letter-spacing: 0.5px;
    }

    .custom-modal-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(29, 78, 137, 0.4);
      background: linear-gradient(135deg, #163c6a 0%, #1d4e89 100%);
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes modalSlideIn {
      from {
        transform: scale(0.9) translateY(20px);
        opacity: 0;
      }
      to {
        transform: scale(1) translateY(0);
        opacity: 1;
      }
    }

    @keyframes iconBounce {
      0% {
        transform: scale(0);
        opacity: 0;
      }
      50% {
        transform: scale(1.1);
      }
      100% {
        transform: scale(1);
        opacity: 1;
      }
    }

    @media (max-width: 768px) {
      .login-container {
        padding: 40px 30px;
        border-radius: 15px;
      }

      .login-container h2 {
        font-size: 28px;
      }

      .custom-modal {
        max-width: 95%;
        border-radius: 15px;
      }
    }
  </style>
</head>
<body>

  <!-- Header -->
  <header>
    <div class="logo">
      <a href="../index.html" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
        <img src="../images/pnmc.png" alt="PNG Maritime College Logo" class="logo-img">
        <span style="margin-left: 10px;">PNG Maritime College</span>
      </a>
    </div>
  </header>

  <!-- Login Form Section -->
  <section class="login-section">
    <div class="login-container">
      <div class="logo" style="text-align: center; margin-bottom: 15px;">
        <img src="../images/pnmc.png" alt="PNG Maritime College Logo" style="max-width: 90px; height: auto; margin-bottom: 8px;">
      </div>
      <h2>Staff Login</h2>
      <p class="subtitle">Access your dashboard</p>
      <?php if ($error): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <form action="" method="post" autocomplete="off">
        <!-- CSRF Token -->
        <?php 
        // Generate token and ensure it's stored in session
        $csrf_token = generateCSRFToken();
        ?>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <!-- Hidden dummy fields to trick browser autofill -->
        <input type="text" style="position: absolute; left: -9999px;" tabindex="-1" autocomplete="off">
        <input type="password" style="position: absolute; left: -9999px;" tabindex="-1" autocomplete="off">
        
        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="Enter your username" required autocomplete="off" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="new-password">

        <label for="usertype">User Type</label>
        <select id="usertype" name="usertype" required>
          <option value="">Select User Type</option>
          <option value="admin" <?php echo (isset($_POST['usertype']) && $_POST['usertype']==='admin') ? 'selected' : ''; ?>>Administration</option>
          <option value="finance" <?php echo (isset($_POST['usertype']) && $_POST['usertype']==='finance') ? 'selected' : ''; ?>>Finance</option>
          <option value="studentservices" <?php echo (isset($_POST['usertype']) && $_POST['usertype']==='studentservices') ? 'selected' : ''; ?>>Student Services</option>
          <option value="hod" <?php echo (isset($_POST['usertype']) && $_POST['usertype']==='hod') ? 'selected' : ''; ?>>Head of Department</option>
        </select>

        <button type="submit" class="cta-btn">Login</button>
      </form>
    </div>
  </section>

  <!-- Custom Styled Modal -->
  <div id="customModal" class="custom-modal-overlay" style="display: none;">
    <div class="custom-modal error" id="modalContent">
      <div class="custom-modal-header">
        <div class="custom-modal-icon" id="modalIcon">✕</div>
        <h3 class="custom-modal-title" id="modalTitle">Login Error</h3>
      </div>
      <div class="custom-modal-body" id="modalBody">
        <!-- Message content will be inserted here -->
      </div>
      <div class="custom-modal-footer">
        <button class="custom-modal-btn" onclick="closeCustomModal()">OK</button>
      </div>
    </div>
  </div>

  <script>
    // Custom Modal Functions
    function showCustomModal(title, message) {
      const modal = document.getElementById('customModal');
      const modalTitle = document.getElementById('modalTitle');
      const modalBody = document.getElementById('modalBody');

      modalTitle.textContent = title;
      modalBody.textContent = message;

      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }

    function closeCustomModal() {
      const modal = document.getElementById('customModal');
      modal.style.animation = 'fadeIn 0.3s ease-out reverse';
      setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
      }, 300);
    }

    // Close modal when clicking outside
    document.getElementById('customModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeCustomModal();
      }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        const modal = document.getElementById('customModal');
        if (modal.style.display === 'flex') {
          closeCustomModal();
        }
      }
    });

    // Show popup message if exists
    <?php if ($popup_message): ?>
    window.addEventListener('DOMContentLoaded', function() {
      showCustomModal('Login Error', <?php echo json_encode($popup_message); ?>);
    });
    <?php endif; ?>
  </script>

</body>
</html>
