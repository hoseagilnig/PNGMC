<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';
require_once 'includes/workflow_helper.php';

$conn = getDBConnection();
$stats = [];
$workflow_data = [];
$finance_data = [];
$recent_actions = [];

if ($conn) {
    // Overall Application Statistics
    $result = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
        SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
        SUM(CASE WHEN status = 'hod_review' THEN 1 ELSE 0 END) as hod_review,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'correspondence_sent' THEN 1 ELSE 0 END) as correspondence_sent,
        SUM(CASE WHEN status = 'checks_pending' THEN 1 ELSE 0 END) as checks_pending,
        SUM(CASE WHEN status = 'checks_completed' THEN 1 ELSE 0 END) as checks_completed,
        SUM(CASE WHEN status = 'enrolled' THEN 1 ELSE 0 END) as enrolled,
        SUM(CASE WHEN status = 'ineligible' THEN 1 ELSE 0 END) as ineligible
        FROM applications");
    $stats = $result->fetch_assoc();
    
    // Department-wise Statistics
    $result = $conn->query("SELECT 
        current_department,
        COUNT(*) as count,
        GROUP_CONCAT(DISTINCT status) as statuses
        FROM applications 
        WHERE current_department IS NOT NULL
        GROUP BY current_department");
    $workflow_data['departments'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Finance Statistics
    $result = $conn->query("SELECT 
        COUNT(*) as total_invoices,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid,
        SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial,
        SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue,
        SUM(total_amount) as total_amount,
        SUM(paid_amount) as total_paid,
        SUM(balance_amount) as total_balance
        FROM invoices");
    $finance_data['invoices'] = $result->fetch_assoc();
    
    // Payments Statistics
    $result = $conn->query("SELECT 
        COUNT(*) as total_payments,
        SUM(amount) as total_amount,
        DATE(MAX(payment_date)) as last_payment_date
        FROM payments");
    $finance_data['payments'] = $result->fetch_assoc();
    
    // Applications with Invoices
    $result = $conn->query("SELECT 
        COUNT(*) as apps_with_invoices
        FROM applications 
        WHERE invoice_id IS NOT NULL");
    $finance_data['applications'] = $result->fetch_assoc();
    
    // Recent Workflow Actions (Last 20)
    $table_check = $conn->query("SHOW TABLES LIKE 'workflow_actions'");
    if ($table_check->num_rows > 0) {
        $result = $conn->query("SELECT wa.*, u.full_name as performed_by_name, a.application_number, a.first_name, a.last_name
            FROM workflow_actions wa
            LEFT JOIN users u ON wa.performed_by = u.user_id
            LEFT JOIN applications a ON wa.application_id = a.application_id
            ORDER BY wa.created_at DESC
            LIMIT 20");
        $recent_actions = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Recent Notifications (Last 20)
    $table_check = $conn->query("SHOW TABLES LIKE 'workflow_notifications'");
    if ($table_check->num_rows > 0) {
        $result = $conn->query("SELECT n.*, a.application_number, a.first_name, a.last_name, u.full_name as created_by_name
            FROM workflow_notifications n
            LEFT JOIN applications a ON n.application_id = a.application_id
            LEFT JOIN users u ON n.created_by = u.user_id
            ORDER BY n.created_at DESC
            LIMIT 20");
        $workflow_data['notifications'] = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Workflow Monitor - Admin Dashboard</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <style>
    .monitor-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .stat-card {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .stat-card h3 {
      margin: 0 0 15px 0;
      color: #1d4e89;
      font-size: 1rem;
      border-bottom: 2px solid #1d4e89;
      padding-bottom: 8px;
    }
    .stat-value {
      font-size: 2.5rem;
      font-weight: bold;
      color: #1d4e89;
      margin: 10px 0;
    }
    .stat-label {
      color: #666;
      font-size: 0.9rem;
    }
    .progress-bar {
      width: 100%;
      height: 8px;
      background: #e0e0e0;
      border-radius: 4px;
      margin: 10px 0;
      overflow: hidden;
    }
    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #1d4e89, #2e7d32);
      transition: width 0.3s;
    }
    .workflow-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    .workflow-table th,
    .workflow-table td {
      padding: 10px;
      text-align: left;
      border-bottom: 1px solid #ddd;
      font-size: 0.9rem;
    }
    .workflow-table th {
      background: #1d4e89;
      color: white;
      font-weight: 600;
    }
    .workflow-table tr:hover {
      background: #f5f5f5;
    }
    .badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-warning { background: #fff3cd; color: #856404; }
    .badge-danger { background: #f8d7da; color: #721c24; }
    .badge-info { background: #d1ecf1; color: #0c5460; }
    .badge-primary { background: #cfe2ff; color: #084298; }
    .section-title {
      color: #1d4e89;
      margin: 30px 0 20px 0;
      padding-bottom: 10px;
      border-bottom: 2px solid #1d4e89;
    }
    .finance-summary {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-top: 15px;
    }
    .finance-item {
      text-align: center;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 6px;
    }
    .finance-item .amount {
      font-size: 1.5rem;
      font-weight: bold;
      color: #1d4e89;
      margin: 5px 0;
    }
    .finance-item .label {
      font-size: 0.85rem;
      color: #666;
    }
  </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="admin_dashboard.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                <img src="../images/pnmc.png" alt="PNG Maritime College Logo" class="logo-img">
                <span style="margin-left: 10px;">Workflow Monitor</span>
            </a>
        </div>
        <div class="user-info">
            <span>👤</span>
            <span>Logged in as <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>
        </div>
    </header>

    <div class="dashboard-wrap container">
    <nav class="sidebar" aria-label="Main navigation">
      <div class="brand">
        <a href="admin_dashboard.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
          <img src="../images/pnmc.png" alt="logo"> 
          <strong>PNGMC</strong>
        </a>
      </div>
      <div class="menu">
        <a class="menu-item" href="admin_dashboard.php">Dashboard</a>
        <div class="menu-section">Monitoring</div>
        <a class="menu-item active" href="workflow_monitor.php">Workflow Monitor</a>
        <a class="menu-item" href="applications.php">View Applications</a>
        <div class="menu-section">Administration</div>
        <a class="menu-item" href="manage_staff.php">Manage Staff</a>
        <a class="menu-item" href="system_settings.php">System Settings</a>
        <a class="menu-item" href="reports.php">Reports & Analytics</a>
      </div>
    </nav>

    <div class="content">
      <header style="margin-bottom: 30px;">
        <h1>Workflow Monitor</h1>
        <p class="small">Track and monitor overall application workflow progress across all departments</p>
      </header>

      <!-- Overall Statistics -->
      <section>
        <h2 class="section-title">Overall Application Statistics</h2>
        <div class="monitor-grid">
          <div class="stat-card">
            <h3>Total Applications</h3>
            <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
            <div class="stat-label">All time applications</div>
          </div>
          
          <div class="stat-card">
            <h3>In Progress</h3>
            <div class="stat-value"><?php echo ($stats['submitted'] ?? 0) + ($stats['under_review'] ?? 0) + ($stats['hod_review'] ?? 0); ?></div>
            <div class="stat-label">Awaiting processing</div>
            <div class="progress-bar">
              <div class="progress-fill" style="width: <?php 
                $total = $stats['total'] ?? 1;
                $in_progress = ($stats['submitted'] ?? 0) + ($stats['under_review'] ?? 0) + ($stats['hod_review'] ?? 0);
                echo ($total > 0) ? ($in_progress / $total * 100) : 0; 
              ?>%"></div>
            </div>
          </div>
          
          <div class="stat-card">
            <h3>Accepted</h3>
            <div class="stat-value" style="color: #2e7d32;"><?php echo $stats['accepted'] ?? 0; ?></div>
            <div class="stat-label">Approved by HOD</div>
          </div>
          
          <div class="stat-card">
            <h3>Enrolled</h3>
            <div class="stat-value" style="color: #1976d2;"><?php echo $stats['enrolled'] ?? 0; ?></div>
            <div class="stat-label">Successfully enrolled</div>
          </div>
        </div>
      </section>

      <!-- Status Breakdown -->
      <section>
        <h2 class="section-title">Application Status Breakdown</h2>
        <div class="monitor-grid">
          <div class="stat-card">
            <h3>Submitted</h3>
            <div class="stat-value"><?php echo $stats['submitted'] ?? 0; ?></div>
          </div>
          <div class="stat-card">
            <h3>Under Review</h3>
            <div class="stat-value"><?php echo $stats['under_review'] ?? 0; ?></div>
          </div>
          <div class="stat-card">
            <h3>HOD Review</h3>
            <div class="stat-value"><?php echo $stats['hod_review'] ?? 0; ?></div>
          </div>
          <div class="stat-card">
            <h3>Correspondence Sent</h3>
            <div class="stat-value"><?php echo $stats['correspondence_sent'] ?? 0; ?></div>
          </div>
          <div class="stat-card">
            <h3>Checks Pending</h3>
            <div class="stat-value"><?php echo $stats['checks_pending'] ?? 0; ?></div>
          </div>
          <div class="stat-card">
            <h3>Checks Completed</h3>
            <div class="stat-value"><?php echo $stats['checks_completed'] ?? 0; ?></div>
          </div>
          <div class="stat-card">
            <h3>Rejected/Ineligible</h3>
            <div class="stat-value" style="color: #d32f2f;"><?php echo ($stats['rejected'] ?? 0) + ($stats['ineligible'] ?? 0); ?></div>
          </div>
        </div>
      </section>

      <!-- Department-wise Distribution -->
      <section>
        <h2 class="section-title">Department-wise Distribution</h2>
        <div class="stat-card">
          <table class="workflow-table">
            <thead>
              <tr>
                <th>Department</th>
                <th>Applications</th>
                <th>Statuses</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($workflow_data['departments'])): ?>
                <?php foreach ($workflow_data['departments'] as $dept): ?>
                  <tr>
                    <td><strong><?php echo ucfirst(str_replace('studentservices', 'Student Services', $dept['current_department'])); ?></strong></td>
                    <td><?php echo $dept['count']; ?></td>
                    <td>
                      <?php 
                        $statuses = explode(',', $dept['statuses']);
                        foreach ($statuses as $status): 
                          $badge_class = 'badge-info';
                          if ($status === 'accepted') $badge_class = 'badge-success';
                          if ($status === 'rejected' || $status === 'ineligible') $badge_class = 'badge-danger';
                          if ($status === 'hod_review') $badge_class = 'badge-warning';
                      ?>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span>
                      <?php endforeach; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="3" style="text-align: center; color: #999;">No data available</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Finance Overview -->
      <section>
        <h2 class="section-title">Finance Overview</h2>
        <div class="stat-card">
          <h3>Invoice Statistics</h3>
          <div class="finance-summary">
            <div class="finance-item">
              <div class="label">Total Invoices</div>
              <div class="amount"><?php echo $finance_data['invoices']['total_invoices'] ?? 0; ?></div>
            </div>
            <div class="finance-item">
              <div class="label">Sent</div>
              <div class="amount" style="color: #1976d2;"><?php echo $finance_data['invoices']['sent'] ?? 0; ?></div>
            </div>
            <div class="finance-item">
              <div class="label">Paid</div>
              <div class="amount" style="color: #2e7d32;"><?php echo $finance_data['invoices']['paid'] ?? 0; ?></div>
            </div>
            <div class="finance-item">
              <div class="label">Partial</div>
              <div class="amount" style="color: #f57c00;"><?php echo $finance_data['invoices']['partial'] ?? 0; ?></div>
            </div>
            <div class="finance-item">
              <div class="label">Overdue</div>
              <div class="amount" style="color: #d32f2f;"><?php echo $finance_data['invoices']['overdue'] ?? 0; ?></div>
            </div>
          </div>
          
          <div class="finance-summary" style="margin-top: 20px;">
            <div class="finance-item">
              <div class="label">Total Amount</div>
              <div class="amount">PGK <?php echo number_format($finance_data['invoices']['total_amount'] ?? 0, 2); ?></div>
            </div>
            <div class="finance-item">
              <div class="label">Total Paid</div>
              <div class="amount" style="color: #2e7d32;">PGK <?php echo number_format($finance_data['invoices']['total_paid'] ?? 0, 2); ?></div>
            </div>
            <div class="finance-item">
              <div class="label">Outstanding</div>
              <div class="amount" style="color: #d32f2f;">PGK <?php echo number_format($finance_data['invoices']['total_balance'] ?? 0, 2); ?></div>
            </div>
          </div>
        </div>
        
        <div class="stat-card" style="margin-top: 20px;">
          <h3>Payment Statistics</h3>
          <div class="finance-summary">
            <div class="finance-item">
              <div class="label">Total Payments</div>
              <div class="amount"><?php echo $finance_data['payments']['total_payments'] ?? 0; ?></div>
            </div>
            <div class="finance-item">
              <div class="label">Total Amount</div>
              <div class="amount">PGK <?php echo number_format($finance_data['payments']['total_amount'] ?? 0, 2); ?></div>
            </div>
            <div class="finance-item">
              <div class="label">Last Payment</div>
              <div class="amount" style="font-size: 1rem;">
                <?php echo $finance_data['payments']['last_payment_date'] ?? 'N/A'; ?>
              </div>
            </div>
          </div>
        </div>
        
        <div class="stat-card" style="margin-top: 20px;">
          <h3>Application Finance Status</h3>
          <div class="finance-summary">
            <div class="finance-item">
              <div class="label">Applications with Invoices</div>
              <div class="amount"><?php echo $finance_data['applications']['apps_with_invoices'] ?? 0; ?></div>
            </div>
          </div>
        </div>
      </section>

      <!-- Recent Workflow Actions -->
      <?php if (!empty($recent_actions)): ?>
      <section>
        <h2 class="section-title">Recent Workflow Actions</h2>
        <div class="stat-card">
          <table class="workflow-table">
            <thead>
              <tr>
                <th>Date/Time</th>
                <th>Application</th>
                <th>Action</th>
                <th>From → To</th>
                <th>Performed By</th>
                <th>Department</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_actions as $action): ?>
                <tr>
                  <td><?php echo date('Y-m-d H:i', strtotime($action['created_at'])); ?></td>
                  <td>
                    <?php if ($action['application_number']): ?>
                      <a href="application_details.php?id=<?php echo $action['application_id']; ?>" style="color: #1d4e89; text-decoration: none;">
                        #<?php echo htmlspecialchars($action['application_number']); ?>
                      </a>
                      <br><small><?php echo htmlspecialchars($action['first_name'] . ' ' . $action['last_name']); ?></small>
                    <?php else: ?>
                      N/A
                    <?php endif; ?>
                  </td>
                  <td><span class="badge badge-primary"><?php echo htmlspecialchars($action['action_type']); ?></span></td>
                  <td>
                    <?php if ($action['from_status'] && $action['to_status']): ?>
                      <span class="badge badge-info"><?php echo htmlspecialchars($action['from_status']); ?></span> → 
                      <span class="badge badge-success"><?php echo htmlspecialchars($action['to_status']); ?></span>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($action['performed_by_name'] ?? 'System'); ?></td>
                  <td><span class="badge badge-info"><?php echo ucfirst(htmlspecialchars($action['performed_by_department'] ?? 'N/A')); ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
      <?php endif; ?>

      <!-- Recent Notifications -->
      <?php if (!empty($workflow_data['notifications'])): ?>
      <section>
        <h2 class="section-title">Recent Workflow Notifications</h2>
        <div class="stat-card">
          <table class="workflow-table">
            <thead>
              <tr>
                <th>Date/Time</th>
                <th>Application</th>
                <th>From → To</th>
                <th>Title</th>
                <th>Type</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($workflow_data['notifications'] as $notif): ?>
                <tr>
                  <td><?php echo date('Y-m-d H:i', strtotime($notif['created_at'])); ?></td>
                  <td>
                    <?php if ($notif['application_number']): ?>
                      <a href="application_details.php?id=<?php echo $notif['application_id']; ?>" style="color: #1d4e89; text-decoration: none;">
                        #<?php echo htmlspecialchars($notif['application_number']); ?>
                      </a>
                      <br><small><?php echo htmlspecialchars($notif['first_name'] . ' ' . $notif['last_name']); ?></small>
                    <?php else: ?>
                      System Notification
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge badge-info"><?php echo ucfirst(htmlspecialchars($notif['from_department'])); ?></span> → 
                    <span class="badge badge-primary"><?php echo ucfirst(htmlspecialchars($notif['to_department'])); ?></span>
                  </td>
                  <td><?php echo htmlspecialchars($notif['title']); ?></td>
                  <td><span class="badge badge-warning"><?php echo ucfirst(str_replace('_', ' ', $notif['notification_type'])); ?></span></td>
                  <td>
                    <span class="badge <?php echo $notif['status'] === 'read' ? 'badge-success' : 'badge-danger'; ?>">
                      <?php echo ucfirst($notif['status']); ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
      <?php endif; ?>

    </div>
  </div>

</body>
</html>

