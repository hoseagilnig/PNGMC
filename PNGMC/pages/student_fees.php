<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'finance') {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';
require_once 'includes/fee_helper.php';

$filter = $_GET['filter'] ?? 'all';
$conn = getDBConnection();
$student_fees = [];
$stats = [];

if ($conn) {
    $tables_exist = $conn->query("SHOW TABLES LIKE 'student_fees'")->num_rows > 0;
    
    if ($tables_exist) {
        // Build query based on filter
        $sql = "SELECT 
            sf.*,
            s.first_name,
            s.last_name,
            s.student_number,
            s.email,
            s.phone,
            p.program_name,
            fp.plan_name,
            DATEDIFF(CURDATE(), sf.due_date) as days_overdue
        FROM student_fees sf
        JOIN students s ON sf.student_id = s.student_id
        LEFT JOIN enrollments e ON s.student_id = e.student_id
        LEFT JOIN programs p ON e.program_id = p.program_id
        LEFT JOIN fee_plans fp ON sf.plan_id = fp.plan_id
        WHERE 1=1";
        
        switch ($filter) {
            case 'outstanding':
                $sql .= " AND sf.outstanding_amount > 0";
                break;
            case 'overdue':
                $sql .= " AND sf.due_date < CURDATE() AND sf.outstanding_amount > 0";
                break;
            case 'holds':
                $sql .= " AND EXISTS (SELECT 1 FROM student_holds sh WHERE sh.student_id = s.student_id AND sh.hold_type = 'financial' AND sh.is_active = TRUE)";
                break;
            case 'paid':
                $sql .= " AND sf.status = 'paid'";
                break;
        }
        
        $sql .= " ORDER BY sf.due_date ASC, sf.outstanding_amount DESC";
        
        $result = $conn->query($sql);
        if ($result) {
            $student_fees = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        // Get statistics
        $stats = getFeeStatistics();
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Fees Management - Finance</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <style>
    .filter-tabs {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .filter-tab {
      padding: 10px 20px;
      background: #f8f9fa;
      border: 2px solid #ddd;
      border-radius: 5px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s;
    }
    .filter-tab:hover {
      background: #e9ecef;
    }
    .filter-tab.active {
      background: #1d4e89;
      color: white;
      border-color: #1d4e89;
    }
    .badge {
      padding: 4px 8px;
      border-radius: 3px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    .badge-pending {
      background: #17a2b8;
      color: white;
    }
    .badge-partial {
      background: #ffc107;
      color: #000;
    }
    .badge-paid {
      background: #28a745;
      color: white;
    }
    .badge-overdue {
      background: #dc3545;
      color: white;
    }
  </style>
</head>
<body>
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
        <div class="menu-section">Fee Management</div>
        <a class="menu-item" href="fee_management.php">Fee Plans Setup</a>
        <a class="menu-item" href="automated_triggers.php">Automated Triggers</a>
        <a class="menu-item" href="payment_reminders.php">Payment Reminders</a>
        <div class="menu-section">Billing & Invoices</div>
        <a class="menu-item" href="billing.php">Billing</a>
        <a class="menu-item" href="invoices.php">Invoices</a>
        <a class="menu-item active" href="student_fees.php">Student Fees</a>
        <div class="menu-section">Reports</div>
        <a class="menu-item" href="financial_reports.php">Financial Reports</a>
        <a class="menu-item" href="fee_reports.php">Fee Reports & Analysis</a>
        <a class="menu-item" href="workflow_manager.php">Workflow Manager</a>
      </div>
    </nav>

    <div class="content">
      <header style="margin-bottom: 30px;">
        <h1>Student Fees Management</h1>
        <p class="small">Manage and track student fees, outstanding amounts, and automatic holds.</p>
      </header>

      <!-- Statistics Summary -->
      <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="main-card">
          <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem;">Total Outstanding</h3>
          <div style="font-size: 2rem; font-weight: bold; color: #dc3545;">PGK <?php echo number_format($stats['total_outstanding'] ?? 0, 2); ?></div>
        </div>
        <div class="main-card">
          <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem;">Overdue Fees</h3>
          <div style="font-size: 2rem; font-weight: bold; color: #f57c00;">PGK <?php echo number_format($stats['overdue_fees'] ?? 0, 2); ?></div>
        </div>
        <div class="main-card">
          <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem;">Students with Holds</h3>
          <div style="font-size: 2rem; font-weight: bold; color: #dc3545;"><?php echo $stats['students_with_holds'] ?? 0; ?></div>
        </div>
      </div>

      <!-- Filter Tabs -->
      <div class="filter-tabs">
        <a href="student_fees.php?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All Fees</a>
        <a href="student_fees.php?filter=outstanding" class="filter-tab <?php echo $filter === 'outstanding' ? 'active' : ''; ?>">Outstanding</a>
        <a href="student_fees.php?filter=overdue" class="filter-tab <?php echo $filter === 'overdue' ? 'active' : ''; ?>">Overdue</a>
        <a href="student_fees.php?filter=holds" class="filter-tab <?php echo $filter === 'holds' ? 'active' : ''; ?>">Financial Holds</a>
        <a href="student_fees.php?filter=paid" class="filter-tab <?php echo $filter === 'paid' ? 'active' : ''; ?>">Paid</a>
      </div>

      <!-- Fees Table -->
      <div class="main-card">
        <table style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr style="background: #1d4e89; color: white;">
              <th style="padding: 12px; text-align: left;">Student</th>
              <th style="padding: 12px; text-align: left;">Program</th>
              <th style="padding: 12px; text-align: left;">Fee Description</th>
              <th style="padding: 12px; text-align: right;">Amount</th>
              <th style="padding: 12px; text-align: right;">Paid</th>
              <th style="padding: 12px; text-align: right;">Outstanding</th>
              <th style="padding: 12px; text-align: left;">Due Date</th>
              <th style="padding: 12px; text-align: center;">Days Overdue</th>
              <th style="padding: 12px; text-align: left;">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($student_fees)): ?>
              <?php foreach ($student_fees as $fee): ?>
                <tr style="border-bottom: 1px solid #ddd;">
                  <td style="padding: 12px;">
                    <?php echo htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']); ?><br>
                    <small style="color: #666;"><?php echo htmlspecialchars($fee['student_number']); ?></small>
                  </td>
                  <td style="padding: 12px;"><?php echo htmlspecialchars($fee['program_name'] ?? 'N/A'); ?></td>
                  <td style="padding: 12px;"><?php echo htmlspecialchars($fee['description'] ?? $fee['fee_type']); ?></td>
                  <td style="padding: 12px; text-align: right;">PGK <?php echo number_format($fee['net_amount'], 2); ?></td>
                  <td style="padding: 12px; text-align: right; color: #28a745;">PGK <?php echo number_format($fee['paid_amount'], 2); ?></td>
                  <td style="padding: 12px; text-align: right; font-weight: bold; color: #dc3545;">PGK <?php echo number_format($fee['outstanding_amount'], 2); ?></td>
                  <td style="padding: 12px;"><?php echo date('M d, Y', strtotime($fee['due_date'])); ?></td>
                  <td style="padding: 12px; text-align: center;">
                    <?php if ($fee['days_overdue'] > 0): ?>
                      <span style="background: #dc3545; color: white; padding: 4px 8px; border-radius: 3px; font-size: 0.85rem;">
                        <?php echo $fee['days_overdue']; ?> days
                      </span>
                    <?php else: ?>
                      <span style="color: #28a745;">-</span>
                    <?php endif; ?>
                  </td>
                  <td style="padding: 12px;">
                    <span class="badge badge-<?php echo $fee['status']; ?>">
                      <?php echo ucfirst($fee['status']); ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" style="text-align: center; padding: 40px; color: #666;">
                  No fees found matching the selected filter.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>

