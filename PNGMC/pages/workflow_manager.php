<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Allow all roles to access workflow manager
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'finance', 'studentservices', 'hod'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';
require_once 'includes/workflow_helper.php';

$message = '';
$message_type = '';

// Handle marking notification as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = intval($_GET['mark_read']);
    markNotificationRead($notification_id);
    // Redirect to remove the GET parameter
    header('Location: workflow_manager.php');
    exit;
}

// Handle workflow actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['workflow_action'])) {
    $conn = getDBConnection();
    if ($conn) {
        $application_id = intval($_POST['application_id']);
        $action = $_POST['workflow_action'];
        $current_role = $_SESSION['role'];
        
        // Get current application status
        $result = $conn->query("SELECT * FROM applications WHERE application_id = $application_id");
        $app = $result->fetch_assoc();
        
        switch ($action) {
            case 'forward_to_finance':
                if (in_array($current_role, ['admin', 'studentservices', 'hod'])) {
                    $notes = trim($_POST['notes'] ?? '');
                    forwardToDepartment(
                        $application_id,
                        'finance',
                        'Application Requires Finance Review',
                        "Application #{$app['application_number']} has been forwarded to Finance for payment verification and invoice processing.",
                        'correspondence_sent',
                        'finance_review'
                    );
                    $message = "Application forwarded to Finance department successfully!";
                    $message_type = "success";
                }
                break;
                
            case 'forward_to_studentservices':
                if (in_array($current_role, ['admin', 'finance', 'hod'])) {
                    $notes = trim($_POST['notes'] ?? '');
                    forwardToDepartment(
                        $application_id,
                        'studentservices',
                        'Application Requires Student Services Review',
                        "Application #{$app['application_number']} has been forwarded to Student Services for document verification and processing.",
                        'under_review',
                        'document_verification'
                    );
                    $message = "Application forwarded to Student Services successfully!";
                    $message_type = "success";
                }
                break;
                
            case 'forward_to_hod':
                if (in_array($current_role, ['admin', 'studentservices'])) {
                    $notes = trim($_POST['notes'] ?? '');
                    forwardToDepartment(
                        $application_id,
                        'hod',
                        'Application Requires HOD Approval',
                        "Application #{$app['application_number']} has been forwarded to Head of Department for final approval.",
                        'hod_review',
                        'hod_approval'
                    );
                    $message = "Application forwarded to HOD successfully!";
                    $message_type = "success";
                }
                break;
                
            case 'finance_approve':
                if ($current_role === 'finance') {
                    $approval_notes = trim($_POST['approval_notes'] ?? '');
                    $conn->query("UPDATE applications SET 
                        finance_approval_status = 'approved',
                        finance_approval_date = CURDATE(),
                        finance_approval_by = {$_SESSION['user_id']},
                        finance_notes = " . ($approval_notes ? "'" . $conn->real_escape_string($approval_notes) . "'" : "NULL") . "
                        WHERE application_id = $application_id");
                    
                    logWorkflowAction($application_id, 'finance_approval', 'Finance approved application', null, 'approved', $approval_notes);
                    
                    // Notify Student Services
                    createWorkflowNotification(
                        $application_id,
                        'finance',
                        'studentservices',
                        'Finance Approval Complete',
                        "Application #{$app['application_number']} has been approved by Finance. Ready for enrollment processing.",
                        'status_update',
                        "application_details.php?id=$application_id"
                    );
                    
                    $message = "Finance approval recorded successfully!";
                    $message_type = "success";
                }
                break;
                
            case 'finance_reject':
                if ($current_role === 'finance') {
                    $rejection_notes = trim($_POST['rejection_notes'] ?? '');
                    $conn->query("UPDATE applications SET 
                        finance_approval_status = 'rejected',
                        finance_approval_date = CURDATE(),
                        finance_approval_by = {$_SESSION['user_id']},
                        finance_notes = " . ($rejection_notes ? "'" . $conn->real_escape_string($rejection_notes) . "'" : "NULL") . "
                        WHERE application_id = $application_id");
                    
                    logWorkflowAction($application_id, 'finance_approval', 'Finance rejected application', null, 'rejected', $rejection_notes);
                    
                    // Notify Student Services
                    createWorkflowNotification(
                        $application_id,
                        'finance',
                        'studentservices',
                        'Finance Rejection',
                        "Application #{$app['application_number']} has been rejected by Finance. Reason: $rejection_notes",
                        'urgent',
                        "application_details.php?id=$application_id"
                    );
                    
                    $message = "Finance rejection recorded!";
                    $message_type = "success";
                }
                break;
        }
        $conn->close();
    }
}

// Mark that user has viewed workflow manager - this will help with dashboard banner
// (Notifications are already marked as read when clicked, so this is just for tracking)

// Get notifications for current user's department
// Map role to department name
$role_to_department = [
    'studentservices' => 'studentservices',
    'finance' => 'finance',
    'hod' => 'hod',
    'admin' => 'studentservices' // Admin can see Student Services notifications
];
$department = $role_to_department[$_SESSION['role']] ?? $_SESSION['role'];
$notifications = getUnreadNotifications($department, 50);

// Get pending applications for current department
$pending_applications = getPendingApplicationsForDepartment($department);

// Get workflow statistics
$conn = getDBConnection();
$workflow_stats = [];
if ($conn) {
    // Use the mapped department name (already defined above)
    $dept = $conn->real_escape_string($department);
    $result = $conn->query("SELECT COUNT(*) as count FROM workflow_notifications WHERE to_department = '$dept' AND status = 'unread'");
    $workflow_stats['unread_notifications'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE current_department = '$dept' AND status != 'enrolled' AND status != 'ineligible'");
    $workflow_stats['pending_applications'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM workflow_actions WHERE performed_by_department = '$dept' AND DATE(created_at) = CURDATE()");
    $workflow_stats['actions_today'] = $result->fetch_assoc()['count'];
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Workflow Manager - PNG Maritime College</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <style>
    .workflow-card {
      background: white;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .notification-item {
      padding: 20px;
      margin-bottom: 15px;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s ease;
      border-left: 5px solid #1d4e89;
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      position: relative;
      overflow: hidden;
    }
    .notification-item::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 5px;
      height: 100%;
      background: #1d4e89;
      transition: width 0.3s ease;
    }
    .notification-item:hover {
      transform: translateX(8px);
      box-shadow: 0 6px 20px rgba(29, 78, 137, 0.15);
    }
    .notification-item:hover::before {
      width: 8px;
    }
    .notification-item.unread {
      background: linear-gradient(135deg, #e7f3ff 0%, #cfe2ff 100%);
      border-left-color: #1d4e89;
      animation: pulse-border 2s infinite;
    }
    .notification-item.unread::after {
      content: '●';
      position: absolute;
      top: 15px;
      right: 15px;
      color: #dc3545;
      font-size: 12px;
      animation: blink 1.5s infinite;
    }
    .notification-item.urgent {
      background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
      border-left-color: #ffc107;
      border-left-width: 6px;
    }
    .notification-item.urgent::after {
      content: '⚠️';
      position: absolute;
      top: 15px;
      right: 15px;
      font-size: 18px;
    }
    @keyframes pulse-border {
      0%, 100% { border-left-color: #1d4e89; }
      50% { border-left-color: #4a90e2; }
    }
    @keyframes blink {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.3; }
    }
    .notification-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 12px;
      gap: 15px;
    }
    .notification-title {
      font-weight: 700;
      color: #1d4e89;
      font-size: 1.15rem;
      flex: 1;
      line-height: 1.4;
    }
    .notification-time {
      font-size: 0.85rem;
      color: #666;
      white-space: nowrap;
      padding: 4px 10px;
      background: rgba(255,255,255,0.7);
      border-radius: 12px;
    }
    .notification-message {
      color: #444;
      line-height: 1.7;
      font-size: 0.95rem;
      margin-bottom: 10px;
    }
    .notification-meta {
      display: flex;
      gap: 15px;
      font-size: 0.85rem;
      color: #666;
      padding-top: 10px;
      border-top: 1px solid rgba(0,0,0,0.1);
      flex-wrap: wrap;
    }
    .notification-meta span {
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .notification-meta .badge {
      background: #1d4e89;
      color: white;
      padding: 2px 8px;
      border-radius: 10px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    .workflow-action-btn {
      background: #1d4e89;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 14px;
      margin-right: 10px;
      margin-top: 10px;
    }
    .workflow-action-btn:hover {
      background: #163c6a;
    }
    .workflow-action-btn.danger {
      background: #dc3545;
    }
    .workflow-action-btn.danger:hover {
      background: #c82333;
    }
    .workflow-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 30px;
    }
    .stat-card {
      background: linear-gradient(135deg, #1d4e89 0%, #163c6a 100%);
      color: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
    }
    .stat-card h3 {
      margin: 0 0 10px 0;
      font-size: 14px;
      opacity: 0.9;
    }
    .stat-card .stat-number {
      font-size: 32px;
      font-weight: bold;
      margin: 0;
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
          elseif ($_SESSION['role'] === 'hod') echo 'hod_dashboard.php';
          else echo 'student_service_dashboard.php';
        ?>" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
          <img src="../images/pnmc.png" alt="logo"> 
          <strong>PNGMC</strong>
        </a>
      </div>
      <div class="menu">
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <a class="menu-item" href="admin_dashboard.php">Dashboard</a>
          <a class="menu-item" href="applications.php">Applications</a>
          <a class="menu-item active" href="workflow_manager.php">Workflow Manager</a>
          <a class="menu-item" href="manage_staff.php">Manage Staff</a>
          <a class="menu-item" href="reports.php">Reports</a>
        <?php elseif ($_SESSION['role'] === 'finance'): ?>
          <a class="menu-item" href="finance_dashboard.php">Dashboard</a>
          <a class="menu-item active" href="workflow_manager.php">Workflow Manager</a>
          <a class="menu-item" href="billing.php">Billing</a>
          <a class="menu-item" href="invoices.php">Invoices</a>
        <?php elseif ($_SESSION['role'] === 'hod'): ?>
          <a class="menu-item" href="hod_dashboard.php">Dashboard</a>
          <a class="menu-item" href="applications.php?status=hod_review">Pending Review</a>
          <a class="menu-item active" href="workflow_manager.php">Workflow Manager</a>
        <?php else: ?>
          <a class="menu-item" href="student_service_dashboard.php">Dashboard</a>
          <a class="menu-item" href="applications.php">School Leavers</a>
          <a class="menu-item" href="continuing_students.php">Candidates Returning</a>
          <a class="menu-item active" href="workflow_manager.php">Workflow Manager</a>
          <a class="menu-item" href="student_records.php">Student Records</a>
        <?php endif; ?>
      </div>
    </nav>

    <div class="content">
      <header style="margin-bottom: 30px;">
        <h1>Workflow Manager</h1>
        <p class="small">Manage cross-department workflow and track application progress</p>
      </header>

      <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>" style="margin-bottom: 20px;">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <!-- Workflow Statistics -->
      <div class="workflow-stats">
        <div class="stat-card">
          <h3>Unread Notifications</h3>
          <p class="stat-number"><?php echo $workflow_stats['unread_notifications'] ?? 0; ?></p>
        </div>
        <div class="stat-card">
          <h3>Pending Applications</h3>
          <p class="stat-number"><?php echo $workflow_stats['pending_applications'] ?? 0; ?></p>
        </div>
        <div class="stat-card">
          <h3>Actions Today</h3>
          <p class="stat-number"><?php echo $workflow_stats['actions_today'] ?? 0; ?></p>
        </div>
      </div>

      <!-- Notifications Section -->
      <div class="workflow-card">
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">
          Recent Notifications
        </h2>
        <?php if (empty($notifications)): ?>
          <p style="color: #666; text-align: center; padding: 20px;">No unread notifications</p>
        <?php else: ?>
          <?php foreach ($notifications as $notif): ?>
            <div class="notification-item <?php echo $notif['status']; ?> <?php echo $notif['notification_type'] === 'urgent' ? 'urgent' : ''; ?>" 
                 onclick="markAsReadAndNavigate(<?php echo $notif['notification_id']; ?>, '<?php echo htmlspecialchars($notif['action_url'] ?? ($notif['application_id'] ? 'application_details.php?id=' . $notif['application_id'] : 'sas_received_data.php'), ENT_QUOTES); ?>')">
              <div class="notification-header">
                <div class="notification-title">
                  <?php if ($notif['notification_type'] === 'urgent'): ?>
                    ⚠️ 
                  <?php elseif ($notif['notification_type'] === 'information'): ?>
                    ℹ️ 
                  <?php elseif ($notif['notification_type'] === 'approval_request'): ?>
                    ✓ 
                  <?php else: ?>
                    📬 
                  <?php endif; ?>
                  <?php echo htmlspecialchars($notif['title']); ?>
                </div>
                <div class="notification-time"><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></div>
              </div>
              <div class="notification-message">
                <?php echo nl2br(htmlspecialchars($notif['message'])); ?>
              </div>
              <div class="notification-meta">
                <span>
                  <strong>From:</strong> 
                  <span class="badge"><?php echo ucfirst(str_replace('studentservices', 'Student Services', $notif['from_department'])); ?></span>
                </span>
                <?php if ($notif['application_number']): ?>
                  <span>
                    <strong>Application:</strong> <?php echo htmlspecialchars($notif['application_number']); ?>
                  </span>
                <?php endif; ?>
                <?php if ($notif['notification_type']): ?>
                  <span>
                    <strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $notif['notification_type'])); ?>
                  </span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Pending Applications Section -->
      <div class="workflow-card">
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">
          Applications Pending in <?php echo ucfirst(str_replace('studentservices', 'Student Services', $_SESSION['role'])); ?>
        </h2>
        <?php if (empty($pending_applications)): ?>
          <p style="color: #666; text-align: center; padding: 20px;">No pending applications</p>
        <?php else: ?>
          <table class="data-table" style="width: 100%;">
            <thead>
              <tr>
                <th>Application #</th>
                <th>Name</th>
                <th>Status</th>
                <th>Workflow Stage</th>
                <th>Last Action</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pending_applications as $app): ?>
                <tr>
                  <td><?php echo htmlspecialchars($app['application_number']); ?></td>
                  <td><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                  <td><span class="badge badge-<?php echo $app['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?></span></td>
                  <td><?php echo htmlspecialchars($app['workflow_stage'] ?? 'N/A'); ?></td>
                  <td><?php echo $app['last_action_at'] ? date('M d, Y', strtotime($app['last_action_at'])) : 'N/A'; ?></td>
                  <td>
                    <a href="application_details.php?id=<?php echo $app['application_id']; ?>" class="workflow-action-btn">View</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <script>
    function markAsReadAndNavigate(notificationId, url) {
      // Mark notification as read via AJAX
      fetch('workflow_manager.php?mark_read=' + notificationId, {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      }).then(() => {
        // Navigate to the action URL
        if (url) {
          window.location.href = url;
        } else {
          // Reload page to refresh notifications
          window.location.reload();
        }
      }).catch(() => {
        // If AJAX fails, still navigate
        if (url) {
          window.location.href = url;
        } else {
          window.location.reload();
        }
      });
    }
  </script>
</body>
</html>

