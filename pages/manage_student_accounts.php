<?php
session_start();
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'studentservices'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';
require_once 'includes/student_account_helper.php';

$message = '';
$message_type = '';
$students = [];
$search_term = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    if ($conn) {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'activate_account') {
                $student_id = intval($_POST['student_id']);
                if (activateStudentAccount($student_id)) {
                    $message = "Student account activated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error activating account.";
                    $message_type = "error";
                }
            } elseif ($_POST['action'] === 'put_on_hold') {
                $student_id = intval($_POST['student_id']);
                if (putAccountOnHold($student_id)) {
                    $message = "Student account put on hold successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error updating account status.";
                    $message_type = "error";
                }
            } elseif ($_POST['action'] === 'create_account') {
                $student_id = intval($_POST['student_id']);
                
                // Get student info
                $result = $conn->query("SELECT student_number, email, phone FROM students WHERE student_id = $student_id");
                $student = $result->fetch_assoc();
                
                if ($student && createStudentAccount($student_id, $student['student_number'], $student['email'], $student['phone'])) {
                    $message = "Student account created successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error creating account. Account may already exist.";
                    $message_type = "error";
                }
            }
        }
        $conn->close();
    }
}

// Get students
$conn = getDBConnection();
if ($conn) {
    $search_term = $_GET['search'] ?? '';
    $where = "1=1";
    
    if ($search_term) {
        $search_term = $conn->real_escape_string($search_term);
        $where .= " AND (s.student_number LIKE '%$search_term%' OR s.first_name LIKE '%$search_term%' OR s.last_name LIKE '%$search_term%' OR s.email LIKE '%$search_term%')";
    }
    
    // Check if account_status column exists
    $col_check = $conn->query("SHOW COLUMNS FROM students LIKE 'account_status'");
    $has_account_status = $col_check->num_rows > 0;
    
    // Check if student_accounts table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'student_accounts'");
    $has_accounts_table = $table_check->num_rows > 0;
    
    if ($has_accounts_table) {
        $sql = "SELECT s.*, sa.username, sa.account_status as login_account_status, sa.last_login, 
                (SELECT COUNT(*) FROM student_course_history WHERE student_id = s.student_id) as course_count
                FROM students s 
                LEFT JOIN student_accounts sa ON s.student_id = sa.student_id 
                WHERE $where
                ORDER BY s.created_at DESC";
    } else {
        $sql = "SELECT s.*, NULL as username, NULL as login_account_status, NULL as last_login, 0 as course_count
                FROM students s 
                WHERE $where
                ORDER BY s.created_at DESC";
    }
    
    $result = $conn->query($sql);
    if ($result) {
        $students = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Student Accounts</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <style>
    .search-box {
      margin-bottom: 20px;
      display: flex;
      gap: 10px;
    }
    .search-box input {
      flex: 1;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
    }
    .search-box button {
      padding: 10px 20px;
      background: #1d4e89;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    .account-status {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    .status-active { background: #d4edda; color: #155724; }
    .status-on-hold { background: #fff3cd; color: #856404; }
    .status-suspended { background: #f8d7da; color: #721c24; }
    .status-inactive { background: #e2e3e5; color: #383d41; }
    .action-buttons {
      display: flex;
      gap: 5px;
    }
    .btn-sm {
      padding: 5px 10px;
      font-size: 0.85rem;
    }
  </style>
</head>
<body>
    <div class="dashboard-wrap container">
    <nav class="sidebar" aria-label="Main navigation">
      <div class="brand">
        <a href="<?php 
          if ($_SESSION['role'] === 'admin') echo 'admin_dashboard.php';
          else echo 'student_service_dashboard.php';
        ?>" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
          <img src="../images/pnmc.png" alt="logo"> 
          <strong>PNGMC</strong>
        </a>
      </div>
      <div class="menu">
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <a class="menu-item" href="admin_dashboard.php">Dashboard</a>
          <a class="menu-item" href="workflow_monitor.php">Workflow Monitor</a>
        <?php else: ?>
          <a class="menu-item" href="student_service_dashboard.php">Dashboard</a>
          <a class="menu-item" href="applications.php">School Leavers</a>
          <a class="menu-item" href="continuing_students.php">Candidates Returning</a>
        <?php endif; ?>
        <div class="menu-section">Student Management</div>
        <a class="menu-item active" href="manage_student_accounts.php">Student Accounts</a>
        <a class="menu-item" href="student_records.php">Student Records</a>
      </div>
    </nav>

    <div class="content">
      <header style="margin-bottom: 30px;">
        <h1>Manage Student Accounts</h1>
        <p class="small">Manage student login accounts, activate/deactivate accounts, and track account status</p>
      </header>

      <?php if ($message): ?>
        <div class="main-card" style="background: <?php echo $message_type === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $message_type === 'success' ? '#155724' : '#721c24'; ?>; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <!-- Search -->
      <div class="main-card" style="margin-bottom: 20px;">
        <form method="GET" class="search-box">
          <input type="text" name="search" placeholder="Search by student number, name, or email..." value="<?php echo htmlspecialchars($search_term); ?>">
          <button type="submit">Search</button>
          <?php if ($search_term): ?>
            <a href="manage_student_accounts.php" class="btn btn-primary" style="text-decoration: none; display: inline-block; padding: 10px 20px;">Clear</a>
          <?php endif; ?>
        </form>
      </div>

      <!-- Students Table -->
      <div class="main-card">
        <h2>Student Accounts</h2>
        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
          <thead>
            <tr>
              <th>Student Number</th>
              <th>Name</th>
              <th>Email</th>
              <th>Account Status</th>
              <th>Username</th>
              <th>Last Login</th>
              <th>Courses</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($students)): ?>
              <tr>
                <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                  No students found.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($students as $student): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($student['student_number']); ?></strong></td>
                  <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                  <td><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></td>
                  <td>
                    <?php 
                    $status = $student['account_status'] ?? ($student['login_account_status'] ?? 'inactive');
                    $status_class = 'status-' . str_replace('_', '-', $status);
                    ?>
                    <span class="account-status <?php echo $status_class; ?>">
                      <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                    </span>
                  </td>
                  <td><?php echo htmlspecialchars($student['username'] ?? 'No account'); ?></td>
                  <td>
                    <?php 
                    if ($student['last_login']) {
                      echo date('M d, Y H:i', strtotime($student['last_login']));
                    } else {
                      echo 'Never';
                    }
                    ?>
                  </td>
                  <td><?php echo $student['course_count'] ?? 0; ?></td>
                  <td>
                    <div class="action-buttons">
                      <?php if (!$student['username']): ?>
                        <form method="POST" style="display: inline;">
                          <input type="hidden" name="action" value="create_account">
                          <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                          <button type="submit" class="btn btn-primary btn-sm">Create Account</button>
                        </form>
                      <?php else: ?>
                        <?php if ($status === 'on_hold' || $status === 'inactive'): ?>
                          <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="activate_account">
                            <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                            <button type="submit" class="btn btn-primary btn-sm" style="background: #28a745;">Activate</button>
                          </form>
                        <?php endif; ?>
                        <?php if ($status === 'active'): ?>
                          <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="put_on_hold">
                            <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                            <button type="submit" class="btn btn-primary btn-sm" style="background: #ffc107; color: #000;" onclick="return confirm('Put this account on hold?')">Put on Hold</button>
                          </form>
                        <?php endif; ?>
                      <?php endif; ?>
                      <a href="send_student_notification.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-primary btn-sm" style="text-decoration: none; display: inline-block; background: #17a2b8;">Send Notification</a>
                      <a href="student_account_details.php?id=<?php echo $student['student_id']; ?>" class="btn btn-primary btn-sm" style="text-decoration: none; display: inline-block;">View Details</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>

