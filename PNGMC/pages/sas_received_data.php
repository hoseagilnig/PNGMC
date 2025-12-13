<?php
session_start();
// Allow studentservices, admin, and finance roles
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['studentservices', 'admin', 'finance'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';

// Mark that user has viewed the transfers page - update session timestamp
if ($_SESSION['role'] === 'studentservices') {
    $_SESSION['last_viewed_transfers'] = time();
}

$conn = getDBConnection();
$transfers = [];
$stats = [
    'total_transfers' => 0,
    'total_amount' => 0,
    'today_transfers' => 0
];

// Get transfers
if ($conn) {
    $tables_exist = $conn->query("SHOW TABLES LIKE 'finance_to_sas_transfers'")->num_rows > 0;
    
    if ($tables_exist) {
        // Get all transfers
        $result = $conn->query("SELECT ft.*, 
            s.first_name, s.last_name, s.student_number,
            u.full_name as transferred_by_name
            FROM finance_to_sas_transfers ft
            LEFT JOIN students s ON ft.student_id = s.student_id
            LEFT JOIN users u ON ft.transferred_by = u.user_id
            ORDER BY ft.transferred_at DESC");
        if ($result) {
            $transfers = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        // Get statistics
        $result = $conn->query("SELECT 
            COUNT(*) as total,
            SUM(amount) as total_amount,
            SUM(CASE WHEN DATE(transferred_at) = CURDATE() THEN 1 ELSE 0 END) as today_count
            FROM finance_to_sas_transfers");
        if ($result) {
            $stats_row = $result->fetch_assoc();
            $stats['total_transfers'] = $stats_row['total'] ?? 0;
            $stats['total_amount'] = $stats_row['total_amount'] ?? 0;
            $stats['today_transfers'] = $stats_row['today_count'] ?? 0;
        }
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Received Data from Finance - SAS</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <style>
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .stat-card {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
    }
    .stat-card h3 {
      margin: 0 0 10px 0;
      color: #666;
      font-size: 0.9rem;
    }
    .stat-card .num {
      font-size: 2rem;
      font-weight: bold;
      color: #1d4e89;
    }
    .transfer-item {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 10px;
      border-left: 4px solid #1d4e89;
    }
    .new-badge {
      background: #dc3545;
      color: white;
      padding: 2px 6px;
      border-radius: 10px;
      font-size: 0.75rem;
      margin-left: 10px;
    }
  </style>
</head>
<body>
    <div class="dashboard-wrap container">
    <nav class="sidebar" aria-label="Main navigation">
      <div class="brand">
        <a href="student_service_dashboard.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
          <img src="../images/pnmc.png" alt="logo"> 
          <strong>PNGMC</strong>
        </a>
      </div>
      <div class="menu">
        <a class="menu-item" href="student_service_dashboard.php">Dashboard</a>
        <div class="menu-section">Workflow</div>
        <a class="menu-item" href="proforma_invoices.php">Proforma Invoices</a>
        <a class="menu-item" href="withdrawal_advice.php">Withdrawal Advice</a>
        <a class="menu-item" href="disciplinary_advice.php">Disciplinary Advice</a>
        <a class="menu-item" href="student_schedules.php">Student Schedules</a>
        <a class="menu-item" href="fees_monitor.php">Fees Monitor</a>
        <a class="menu-item" href="red_green_days.php">Red & Green Days</a>
        <a class="menu-item active" href="sas_received_data.php">Received from Finance</a>
        <a class="menu-item" href="workflow_manager.php">Workflow Manager</a>
      </div>
    </nav>

    <div class="content">
      <header style="margin-bottom: 30px;">
        <h1>Received Data from Finance</h1>
        <p class="small">View payment data received from Finance: Payment Receipt Numbers (PNGMC receipt book), Payment Date (per bank statement), and AR Records (MYOB - Summary & Individual).</p>
      </header>

      <!-- Statistics -->
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total Transfers</h3>
          <div class="num"><?php echo $stats['total_transfers']; ?></div>
        </div>
        <div class="stat-card">
          <h3>Total Amount</h3>
          <div class="num">PGK <?php echo number_format($stats['total_amount'], 2); ?></div>
        </div>
        <div class="stat-card">
          <h3>Transfers Today</h3>
          <div class="num"><?php echo $stats['today_transfers']; ?></div>
        </div>
      </div>

      <div class="main-card">
        <h2>Transferred Data from Finance</h2>
        <?php if (!empty($transfers)): ?>
          <?php foreach ($transfers as $transfer): ?>
            <div class="transfer-item">
              <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                <div style="flex: 1;">
                  <h3 style="margin: 0 0 10px 0; color: #1d4e89;">
                    Receipt: <?php echo htmlspecialchars($transfer['receipt_number']); ?>
                    <?php if (date('Y-m-d', strtotime($transfer['transferred_at'])) === date('Y-m-d')): ?>
                      <span class="new-badge">NEW</span>
                    <?php endif; ?>
                  </h3>
                  <p style="margin: 5px 0; color: #666;">
                    <strong>Payment Date:</strong> <?php echo date('M d, Y', strtotime($transfer['payment_date'])); ?><br>
                    <?php if ($transfer['student_id']): ?>
                      <strong>Student:</strong> <?php echo htmlspecialchars($transfer['first_name'] . ' ' . $transfer['last_name']); ?> 
                      (<?php echo htmlspecialchars($transfer['student_number']); ?>)<br>
                    <?php endif; ?>
                    <strong>Amount:</strong> <span style="font-weight: bold; color: #28a745;">PGK <?php echo number_format($transfer['amount'], 2); ?></span><br>
                    <strong>Transferred by:</strong> <?php echo htmlspecialchars($transfer['transferred_by_name'] ?? 'Finance'); ?><br>
                    <strong>Transferred at:</strong> <?php echo date('M d, Y H:i', strtotime($transfer['transferred_at'])); ?>
                  </p>
                </div>
              </div>
              
              <?php if ($transfer['ar_record_summary'] || $transfer['ar_record_individual']): ?>
                <div style="margin-top: 15px; padding: 15px; background: white; border-radius: 5px;">
                  <?php if ($transfer['ar_record_summary']): ?>
                    <div style="margin-bottom: 10px;">
                      <strong style="color: #1d4e89;">AR Record - Summary (MYOB):</strong>
                      <p style="margin: 5px 0 0 0; color: #555; white-space: pre-wrap;"><?php echo htmlspecialchars($transfer['ar_record_summary']); ?></p>
                    </div>
                  <?php endif; ?>
                  
                  <?php if ($transfer['ar_record_individual']): ?>
                    <div>
                      <strong style="color: #1d4e89;">AR Record - Individual (MYOB):</strong>
                      <p style="margin: 5px 0 0 0; color: #555; white-space: pre-wrap;"><?php echo htmlspecialchars($transfer['ar_record_individual']); ?></p>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color: #666; margin-top: 20px;">No data received from Finance yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>

