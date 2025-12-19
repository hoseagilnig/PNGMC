<?php
session_start();
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'finance', 'studentservices'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';
require_once 'includes/student_account_helper.php';

$message = '';
$message_type = '';
$students = [];
$selected_student_id = $_GET['student_id'] ?? null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $student_id = intval($_POST['student_id']);
    $from_department = $_SESSION['role'];
    $title = trim($_POST['title']);
    $message_text = trim($_POST['message']);
    $notification_type = $_POST['notification_type'] ?? 'info';
    $action_url = trim($_POST['action_url'] ?? '');
    
    if (empty($title) || empty($message_text)) {
        $message = "Title and message are required!";
        $message_type = "error";
    } else {
        if (sendStudentNotification($student_id, $from_department, $title, $message_text, $notification_type, $action_url ?: null)) {
            $message = "Notification sent successfully!";
            $message_type = "success";
            
            // Clear form
            $title = '';
            $message_text = '';
            $notification_type = 'info';
            $action_url = '';
        } else {
            $message = "Error sending notification. Please try again.";
            $message_type = "error";
        }
    }
}

// Get students list
$conn = getDBConnection();
if ($conn) {
    $search_term = $_GET['search'] ?? '';
    $where = "1=1";
    
    if ($search_term) {
        $search_term = $conn->real_escape_string($search_term);
        $where .= " AND (s.student_number LIKE '%$search_term%' OR s.first_name LIKE '%$search_term%' OR s.last_name LIKE '%$search_term%' OR s.email LIKE '%$search_term%')";
    }
    
    $sql = "SELECT s.student_id, s.student_number, s.first_name, s.last_name, s.email, s.account_status 
            FROM students s 
            WHERE $where
            ORDER BY s.student_number DESC
            LIMIT 100";
    
    $result = $conn->query($sql);
    if ($result) {
        $students = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get selected student info
    $selected_student = null;
    if ($selected_student_id) {
        $result = $conn->query("SELECT * FROM students WHERE student_id = $selected_student_id");
        $selected_student = $result->fetch_assoc();
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Send Student Notification</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <style>
    .form-section {
      background: white;
      padding: 25px;
      border-radius: 10px;
      margin-bottom: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .form-group {
      margin-bottom: 20px;
    }
    label {
      display: block;
      margin-bottom: 5px;
      font-weight: 600;
      color: #1d4e89;
    }
    input, select, textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 1rem;
      box-sizing: border-box;
    }
    textarea {
      min-height: 150px;
      resize: vertical;
    }
    .search-box {
      margin-bottom: 20px;
      display: flex;
      gap: 10px;
    }
    .student-select {
      max-height: 200px;
      overflow-y: auto;
      border: 1px solid #ddd;
      border-radius: 5px;
      padding: 10px;
    }
    .student-option {
      padding: 10px;
      cursor: pointer;
      border-radius: 4px;
      margin-bottom: 5px;
      display: block;
      text-decoration: none;
      color: inherit;
      transition: background 0.2s;
    }
    .student-option:hover {
      background: #f0f7ff;
    }
    .student-option.selected {
      background: #1d4e89;
      color: white;
    }
    .student-option.selected:hover {
      background: #163c6a;
    }
  </style>
</head>
<body>
    <div class="dashboard-wrap container">
    <nav class="sidebar" aria-label="Main navigation">
      <div class="brand">
        <a href="<?php 
          if ($_SESSION['role'] === 'admin') echo 'admin_dashboard.php';
          elseif ($_SESSION['role'] === 'finance') echo 'finance_dashboard.php';
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
        <?php elseif ($_SESSION['role'] === 'finance'): ?>
          <a class="menu-item" href="finance_dashboard.php">Dashboard</a>
        <?php else: ?>
          <a class="menu-item" href="student_service_dashboard.php">Dashboard</a>
          <a class="menu-item" href="applications.php">School Leavers</a>
        <?php endif; ?>
        <div class="menu-section">Student Management</div>
        <a class="menu-item active" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">Send Notification</a>
        <a class="menu-item" href="manage_student_accounts.php">Student Accounts</a>
      </div>
    </nav>

    <div class="content">
      <header style="margin-bottom: 30px;">
        <h1>Send Student Notification</h1>
        <p class="small">Send notifications to students from <?php echo ucfirst(str_replace('studentservices', 'Student Admin Service', $_SESSION['role'])); ?></p>
      </header>

      <?php if ($message): ?>
        <div class="main-card" style="background: <?php echo $message_type === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $message_type === 'success' ? '#155724' : '#721c24'; ?>; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <div class="form-section">
        <h2 style="margin-bottom: 20px; color: #1d4e89;">Select Student</h2>
        
        <form method="GET" class="search-box">
          <input type="text" name="search" placeholder="Search by student number, name, or email..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
          <button type="submit" class="btn btn-primary">Search</button>
          <?php if (isset($_GET['search'])): ?>
            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-primary" style="text-decoration: none; display: inline-block;">Clear</a>
          <?php endif; ?>
        </form>
        
        <?php if (!empty($students)): ?>
          <div class="student-select">
            <?php foreach ($students as $stud): ?>
              <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?student_id=<?php echo $stud['student_id']; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>" 
                 class="student-option <?php echo ($selected_student_id == $stud['student_id']) ? 'selected' : ''; ?>">
                <strong><?php echo htmlspecialchars($stud['student_number']); ?></strong> - 
                <?php echo htmlspecialchars($stud['first_name'] . ' ' . $stud['last_name']); ?>
                <?php if ($stud['email']): ?>
                  <br><small><?php echo htmlspecialchars($stud['email']); ?></small>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="text-align: center; color: #999; padding: 20px;">No students found.</p>
        <?php endif; ?>
      </div>

      <?php if ($selected_student): ?>
      <div class="form-section">
        <h2 style="margin-bottom: 20px; color: #1d4e89;">Compose Notification</h2>
        <form method="POST">
          <input type="hidden" name="student_id" id="student_id" value="<?php echo $selected_student['student_id']; ?>">
          
          <div class="form-group">
            <label>Student</label>
            <input type="text" value="<?php echo htmlspecialchars($selected_student['student_number'] . ' - ' . $selected_student['first_name'] . ' ' . $selected_student['last_name']); ?>" readonly style="background: #f8f9fa;">
          </div>
          
          <div class="form-group">
            <label>Notification Type *</label>
            <select name="notification_type" required>
              <option value="info" <?php echo ($notification_type ?? 'info') === 'info' ? 'selected' : ''; ?>>Info</option>
              <option value="important" <?php echo ($notification_type ?? '') === 'important' ? 'selected' : ''; ?>>Important</option>
              <option value="warning" <?php echo ($notification_type ?? '') === 'warning' ? 'selected' : ''; ?>>Warning</option>
              <option value="action_required" <?php echo ($notification_type ?? '') === 'action_required' ? 'selected' : ''; ?>>Action Required</option>
              <option value="payment" <?php echo ($notification_type ?? '') === 'payment' ? 'selected' : ''; ?>>Payment</option>
              <option value="academic" <?php echo ($notification_type ?? '') === 'academic' ? 'selected' : ''; ?>>Academic</option>
              <option value="general" <?php echo ($notification_type ?? '') === 'general' ? 'selected' : ''; ?>>General</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" required placeholder="Notification title">
          </div>
          
          <div class="form-group">
            <label>Message *</label>
            <textarea name="message" required placeholder="Enter notification message..."><?php echo htmlspecialchars($message_text ?? ''); ?></textarea>
          </div>
          
          <div class="form-group">
            <label>Action URL (Optional)</label>
            <input type="text" name="action_url" value="<?php echo htmlspecialchars($action_url ?? ''); ?>" placeholder="e.g., student_dashboard.php or specific page URL">
            <small style="color: #666;">If provided, student can click to view details</small>
          </div>
          
          <button type="submit" name="send_notification" class="btn btn-primary">Send Notification</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>
  
</body>
</html>

