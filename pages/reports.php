<?php
session_start();
// Allow admin and hod roles to access reports
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'hod'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';

$conn = getDBConnection();
$stats = [];
if ($conn) {
    // Get statistics
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $stats['total_staff'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM students");
    $stats['total_students'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
    $stats['active_students'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM programs");
    $stats['total_programs'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM invoices WHERE status != 'paid'");
    $stats['outstanding_invoices'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM support_tickets WHERE status = 'open'");
    $stats['open_tickets'] = $result->fetch_assoc()['count'];
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports - Admin</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <style>
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
    .stat-card { background: var(--card-bg); padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .stat-card h3 { color: var(--primary); margin-bottom: 10px; }
    .stat-card .num { font-size: 2rem; font-weight: bold; color: var(--primary); }
    .report-section { background: var(--card-bg); padding: 20px; border-radius: 10px; margin: 20px 0; }
  </style>
</head>
<body>
    <div class="dashboard-wrap container">
    <nav class="sidebar" aria-label="Main navigation">
      <div class="brand">
        <a href="<?php 
          if ($_SESSION['role'] === 'admin') echo 'admin_dashboard.php';
          elseif ($_SESSION['role'] === 'hod') echo 'hod_dashboard.php';
          else echo 'admin_dashboard.php';
        ?>" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
          <img src="../images/pnmc.png" alt="logo"> 
          <strong>PNGMC</strong>
        </a>
      </div>
      <div class="menu">
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <a class="menu-item" href="admin_dashboard.php">Dashboard</a>
          <div class="menu-section">Administration</div>
          <a class="menu-item" href="manage_staff.php">Manage Staff</a>
          <a class="menu-item" href="system_settings.php">System Settings</a>
          <div class="menu-section">Reports</div>
          <a class="menu-item active" href="reports.php">Reports</a>
        <?php elseif ($_SESSION['role'] === 'hod'): ?>
          <a class="menu-item" href="hod_dashboard.php">Dashboard</a>
          <div class="menu-section">Applications</div>
          <a class="menu-item" href="applications.php?status=hod_review">Pending Review</a>
          <a class="menu-item" href="workflow_manager.php">Workflow Manager</a>
          <div class="menu-section">Reports</div>
          <a class="menu-item active" href="reports.php">Reports</a>
        <?php endif; ?>
      </div>
    </nav>

    <div class="content">
      <div class="main-card">
        <h1>Reports & Statistics</h1>
        
        <div class="stats-grid">
          <div class="stat-card">
            <h3>Total Staff</h3>
            <div class="num"><?php echo $stats['total_staff']; ?></div>
          </div>
          <div class="stat-card">
            <h3>Total Students</h3>
            <div class="num"><?php echo $stats['total_students']; ?></div>
          </div>
          <div class="stat-card">
            <h3>Active Students</h3>
            <div class="num"><?php echo $stats['active_students']; ?></div>
          </div>
          <div class="stat-card">
            <h3>Programs</h3>
            <div class="num"><?php echo $stats['total_programs']; ?></div>
          </div>
          <div class="stat-card">
            <h3>Outstanding Invoices</h3>
            <div class="num"><?php echo $stats['outstanding_invoices']; ?></div>
          </div>
          <div class="stat-card">
            <h3>Open Tickets</h3>
            <div class="num"><?php echo $stats['open_tickets']; ?></div>
          </div>
        </div>

        <div class="report-section">
          <h2>System Overview</h2>
          <p>This section provides an overview of the system statistics. More detailed reports can be added here as needed.</p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>

