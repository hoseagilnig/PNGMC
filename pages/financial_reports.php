<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'finance') {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';

$conn = getDBConnection();
$stats = [];
if ($conn) {
    // Financial statistics
    $result = $conn->query("SELECT SUM(total_amount) as total FROM invoices");
    $stats['total_invoiced'] = $result->fetch_assoc()['total'] ?? 0;
    
    $result = $conn->query("SELECT SUM(paid_amount) as total FROM invoices");
    $stats['total_paid'] = $result->fetch_assoc()['total'] ?? 0;
    
    $result = $conn->query("SELECT SUM(balance_amount) as total FROM invoices WHERE balance_amount > 0");
    $stats['outstanding'] = $result->fetch_assoc()['total'] ?? 0;
    
    $result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE payment_date = CURDATE()");
    $stats['paid_today'] = $result->fetch_assoc()['total'] ?? 0;
    
    $result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())");
    $stats['paid_this_month'] = $result->fetch_assoc()['total'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM invoices WHERE status = 'overdue'");
    $stats['overdue_count'] = $result->fetch_assoc()['count'];
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Financial Reports - Finance</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <style>
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
    .stat-card { background: var(--card-bg); padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .stat-card h3 { color: var(--primary); margin-bottom: 10px; }
    .stat-card .num { font-size: 2rem; font-weight: bold; color: var(--primary); }
    .report-section { background: var(--card-bg); padding: 20px; border-radius: 10px; margin: 20px 0; }
  </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="finance_dashboard.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                <img src="../images/pnmc.png" alt="PNG Maritime College Logo" class="logo-img">
                <span style="margin-left: 10px;">Finance Dashboard</span>
            </a>
        </div>
        <div class="user-info">
            Logged in as <?php echo htmlspecialchars($_SESSION['name']); ?>
        </div>
    </header>

    <div class="dashboard-wrap container">
    <nav class="sidebar" aria-label="Main navigation">
      <div class="brand">
        <a href="finance_dashboard.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
          <img src="../images/pnmc.png" alt="logo"> 
          <strong>PNGMC</strong>
        </a>
      </div>
      <div class="menu">
        <a class="menu-item" href="finance_dashboard.php">Dashboard</a>
        <a class="menu-item" href="billing.php">Billing</a>
        <a class="menu-item" href="invoices.php">Invoices</a>
        <a class="menu-item active" href="financial_reports.php">Financial Reports</a>
      </div>
    </nav>

    <div class="content">
      <div class="main-card">
        <h1>Financial Reports</h1>
        
        <div class="stats-grid">
          <div class="stat-card">
            <h3>Total Invoiced</h3>
            <div class="num"><?php echo number_format($stats['total_invoiced'], 2); ?></div>
          </div>
          <div class="stat-card">
            <h3>Total Paid</h3>
            <div class="num"><?php echo number_format($stats['total_paid'], 2); ?></div>
          </div>
          <div class="stat-card">
            <h3>Outstanding</h3>
            <div class="num"><?php echo number_format($stats['outstanding'], 2); ?></div>
          </div>
          <div class="stat-card">
            <h3>Paid Today</h3>
            <div class="num"><?php echo number_format($stats['paid_today'], 2); ?></div>
          </div>
          <div class="stat-card">
            <h3>Paid This Month</h3>
            <div class="num"><?php echo number_format($stats['paid_this_month'], 2); ?></div>
          </div>
          <div class="stat-card">
            <h3>Overdue Invoices</h3>
            <div class="num"><?php echo $stats['overdue_count']; ?></div>
          </div>
        </div>

        <div class="report-section">
          <h2>Financial Overview</h2>
          <p>This section provides a comprehensive overview of the financial status. More detailed reports can be added here as needed.</p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>

