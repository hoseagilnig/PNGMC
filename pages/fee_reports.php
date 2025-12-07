<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'finance') {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';
require_once 'includes/fee_helper.php';

$view = $_GET['view'] ?? 'overview';
$period = $_GET['period'] ?? 'week';
$parameter = $_GET['parameter'] ?? 'all';
$summary = $_GET['summary'] ?? 'true';

$conn = getDBConnection();
$invoice_receipt_data = [];
$payment_mode_data = [];
$outstanding_by_param = [];
$ageing_summary = [];
$ageing_detail = [];
$weekly_receipts = [];

if ($conn) {
    $tables_exist = $conn->query("SHOW TABLES LIKE 'student_fees'")->num_rows > 0;
    
    if ($tables_exist) {
        // Invoice vs Receipt Analysis
        $invoice_receipt_data = getInvoiceReceiptAnalysis($period);
        
        // Payment Mode Analysis
        $payment_mode_data = getPaymentModeAnalysis($period);
        
        // Outstanding by Parameter
        if ($parameter !== 'all') {
            $outstanding_by_param = getOutstandingByParameter($parameter, $_GET['value'] ?? null);
        }
        
        // Ageing Analysis
        $ageing_summary = getAgeingAnalysis(true);
        $ageing_detail = getAgeingAnalysis(false);
        
        // Weekly Receipts
        $result = $conn->query("SELECT 
            DATE(payment_date) as date,
            DAYNAME(payment_date) as day_name,
            SUM(amount_paid) as total,
            COUNT(*) as count
        FROM fee_payment_history
        WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(payment_date)
        ORDER BY date DESC");
        if ($result) {
            $weekly_receipts = $result->fetch_all(MYSQLI_ASSOC);
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
  <title>Fee Reports & Analysis - Finance</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .tabs {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      border-bottom: 2px solid #ddd;
      flex-wrap: wrap;
    }
    .tab {
      padding: 12px 24px;
      background: #f8f9fa;
      border: none;
      cursor: pointer;
      border-radius: 5px 5px 0 0;
      font-weight: 600;
    }
    .tab.active {
      background: #1d4e89;
      color: white;
    }
    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
    }
    .chart-container {
      background: white;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
      height: 400px;
    }
    .filter-controls {
      display: flex;
      gap: 15px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .filter-controls select,
    .filter-controls input {
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 5px;
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
        <a class="menu-item" href="student_fees.php">Student Fees</a>
        <div class="menu-section">Reports</div>
        <a class="menu-item" href="financial_reports.php">Financial Reports</a>
        <a class="menu-item active" href="fee_reports.php">Fee Reports & Analysis</a>
        <a class="menu-item" href="workflow_manager.php">Workflow Manager</a>
      </div>
    </nav>

    <div class="content">
      <header style="margin-bottom: 30px;">
        <h1>Fee Reports & Analysis</h1>
        <p class="small">Comprehensive fee analysis: Invoice vs Receipt, Payment Mode, Outstanding Analysis, Ageing Reports, and more.</p>
      </header>

      <div class="tabs">
        <button class="tab <?php echo $view === 'overview' ? 'active' : ''; ?>" onclick="showView('overview')">Overview</button>
        <button class="tab <?php echo $view === 'invoice_receipt' ? 'active' : ''; ?>" onclick="showView('invoice_receipt')">Invoice vs Receipt</button>
        <button class="tab <?php echo $view === 'payment_mode' ? 'active' : ''; ?>" onclick="showView('payment_mode')">Payment Mode</button>
        <button class="tab <?php echo $view === 'receipts' ? 'active' : ''; ?>" onclick="showView('receipts')">Weekly Receipts</button>
        <button class="tab <?php echo $view === 'outstanding' ? 'active' : ''; ?>" onclick="showView('outstanding')">Outstanding Analysis</button>
        <button class="tab <?php echo $view === 'ageing' ? 'active' : ''; ?>" onclick="showView('ageing')">Ageing Analysis</button>
      </div>

      <!-- Overview Tab -->
      <div id="overview" class="tab-content <?php echo $view === 'overview' ? 'active' : ''; ?>">
        <div class="main-card">
          <h2>Reports Overview</h2>
          <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
            <a href="fee_reports.php?view=invoice_receipt" class="stat-card" style="text-decoration: none; color: inherit;">
              <h3>Invoice vs Receipt</h3>
              <div class="num">Analysis</div>
              <p style="margin: 10px 0 0 0; color: #666; font-size: 0.9rem;">Compare invoices and receipts</p>
            </a>
            <a href="fee_reports.php?view=payment_mode" class="stat-card" style="text-decoration: none; color: inherit;">
              <h3>Payment Mode</h3>
              <div class="num">Analysis</div>
              <p style="margin: 10px 0 0 0; color: #666; font-size: 0.9rem;">Payment method breakdown</p>
            </a>
            <a href="fee_reports.php?view=receipts" class="stat-card" style="text-decoration: none; color: inherit;">
              <h3>Weekly Receipts</h3>
              <div class="num">Analysis</div>
              <p style="margin: 10px 0 0 0; color: #666; font-size: 0.9rem;">Receipt trends</p>
            </a>
            <a href="fee_reports.php?view=outstanding" class="stat-card" style="text-decoration: none; color: inherit;">
              <h3>Outstanding</h3>
              <div class="num">Analysis</div>
              <p style="margin: 10px 0 0 0; color: #666; font-size: 0.9rem;">By parameters</p>
            </a>
            <a href="fee_reports.php?view=ageing" class="stat-card" style="text-decoration: none; color: inherit;">
              <h3>Ageing</h3>
              <div class="num">Analysis</div>
              <p style="margin: 10px 0 0 0; color: #666; font-size: 0.9rem;">Summary & Detail</p>
            </a>
          </div>
        </div>
      </div>

      <!-- Invoice vs Receipt Tab -->
      <div id="invoice_receipt" class="tab-content <?php echo $view === 'invoice_receipt' ? 'active' : ''; ?>">
        <div class="main-card">
          <h2>Invoice vs Receipt Analysis</h2>
          <div class="filter-controls">
            <select onchange="window.location.href='fee_reports.php?view=invoice_receipt&period=' + this.value">
              <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
              <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
            </select>
          </div>
          <div class="chart-container">
            <canvas id="invoiceReceiptChart"></canvas>
          </div>
          <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
              <tr style="background: #1d4e89; color: white;">
                <th style="padding: 12px; text-align: left;">Date</th>
                <th style="padding: 12px; text-align: right;">Invoices Count</th>
                <th style="padding: 12px; text-align: right;">Invoices Amount</th>
                <th style="padding: 12px; text-align: right;">Receipts Count</th>
                <th style="padding: 12px; text-align: right;">Receipts Amount</th>
                <th style="padding: 12px; text-align: right;">Difference</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($invoice_receipt_data as $row): ?>
                <tr style="border-bottom: 1px solid #ddd;">
                  <td style="padding: 12px;"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                  <td style="padding: 12px; text-align: right;"><?php echo $row['invoices_count'] ?? 0; ?></td>
                  <td style="padding: 12px; text-align: right;">PGK <?php echo number_format($row['invoices_amount'] ?? 0, 2); ?></td>
                  <td style="padding: 12px; text-align: right;"><?php echo $row['receipts_count'] ?? 0; ?></td>
                  <td style="padding: 12px; text-align: right;">PGK <?php echo number_format($row['receipts_amount'] ?? 0, 2); ?></td>
                  <td style="padding: 12px; text-align: right; color: <?php echo ($row['invoices_amount'] ?? 0) > ($row['receipts_amount'] ?? 0) ? '#dc3545' : '#28a745'; ?>;">
                    PGK <?php echo number_format(($row['invoices_amount'] ?? 0) - ($row['receipts_amount'] ?? 0), 2); ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Payment Mode Tab -->
      <div id="payment_mode" class="tab-content <?php echo $view === 'payment_mode' ? 'active' : ''; ?>">
        <div class="main-card">
          <h2>Payment Mode Analysis</h2>
          <div class="filter-controls">
            <select onchange="window.location.href='fee_reports.php?view=payment_mode&period=' + this.value">
              <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
              <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
            </select>
          </div>
          <div class="chart-container">
            <canvas id="paymentModeChart"></canvas>
          </div>
          <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
            <?php foreach ($payment_mode_data as $mode): ?>
              <div class="stat-card">
                <h3><?php echo ucfirst(str_replace('_', ' ', $mode['payment_method'])); ?></h3>
                <div class="num">PGK <?php echo number_format($mode['total_amount'], 2); ?></div>
                <p style="margin: 10px 0 0 0; color: #666; font-size: 0.9rem;"><?php echo $mode['count']; ?> transactions</p>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Weekly Receipts Tab -->
      <div id="receipts" class="tab-content <?php echo $view === 'receipts' ? 'active' : ''; ?>">
        <div class="main-card">
          <h2>Weekly Receipts Analysis</h2>
          <div class="chart-container">
            <canvas id="weeklyReceiptsChart"></canvas>
          </div>
          <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
              <tr style="background: #1d4e89; color: white;">
                <th style="padding: 12px; text-align: left;">Date</th>
                <th style="padding: 12px; text-align: left;">Day</th>
                <th style="padding: 12px; text-align: right;">Total Amount</th>
                <th style="padding: 12px; text-align: right;">Transaction Count</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($weekly_receipts as $receipt): ?>
                <tr style="border-bottom: 1px solid #ddd;">
                  <td style="padding: 12px;"><?php echo date('M d, Y', strtotime($receipt['date'])); ?></td>
                  <td style="padding: 12px;"><?php echo $receipt['day_name']; ?></td>
                  <td style="padding: 12px; text-align: right; font-weight: bold;">PGK <?php echo number_format($receipt['total'], 2); ?></td>
                  <td style="padding: 12px; text-align: right;"><?php echo $receipt['count']; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Outstanding Analysis Tab -->
      <div id="outstanding" class="tab-content <?php echo $view === 'outstanding' ? 'active' : ''; ?>">
        <div class="main-card">
          <h2>Outstanding Amounts by Parameters</h2>
          <div class="filter-controls">
            <select onchange="window.location.href='fee_reports.php?view=outstanding&parameter=' + this.value">
              <option value="all" <?php echo $parameter === 'all' ? 'selected' : ''; ?>>All</option>
              <option value="program" <?php echo $parameter === 'program' ? 'selected' : ''; ?>>By Program</option>
              <option value="department" <?php echo $parameter === 'department' ? 'selected' : ''; ?>>By Department</option>
              <option value="location" <?php echo $parameter === 'location' ? 'selected' : ''; ?>>By Location</option>
            </select>
          </div>
          <?php if (!empty($outstanding_by_param)): ?>
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
              <thead>
                <tr style="background: #1d4e89; color: white;">
                  <th style="padding: 12px; text-align: left;">Student</th>
                  <th style="padding: 12px; text-align: left;">Program</th>
                  <th style="padding: 12px; text-align: right;">Outstanding Amount</th>
                  <th style="padding: 12px; text-align: left;">Due Date</th>
                  <th style="padding: 12px; text-align: center;">Days Overdue</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($outstanding_by_param as $fee): ?>
                  <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 12px;"><?php echo htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']); ?></td>
                    <td style="padding: 12px;"><?php echo htmlspecialchars($fee['program_name'] ?? 'N/A'); ?></td>
                    <td style="padding: 12px; text-align: right; font-weight: bold; color: #dc3545;">PGK <?php echo number_format($fee['outstanding_amount'], 2); ?></td>
                    <td style="padding: 12px;"><?php echo date('M d, Y', strtotime($fee['due_date'])); ?></td>
                    <td style="padding: 12px; text-align: center;">
                      <?php 
                        $days = (strtotime('today') - strtotime($fee['due_date'])) / 86400;
                        if ($days > 0) echo '<span style="background: #dc3545; color: white; padding: 4px 8px; border-radius: 3px;">' . intval($days) . ' days</span>';
                      ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p style="color: #666; margin-top: 20px;">Select a parameter to view outstanding amounts.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Ageing Analysis Tab -->
      <div id="ageing" class="tab-content <?php echo $view === 'ageing' ? 'active' : ''; ?>">
        <div class="main-card">
          <h2>Outstanding Amount Ageing Analysis</h2>
          <div class="filter-controls">
            <a href="fee_reports.php?view=ageing&summary=true" class="btn <?php echo $summary === 'true' ? 'active' : ''; ?>" style="padding: 8px 16px; background: <?php echo $summary === 'true' ? '#1d4e89' : '#f8f9fa'; ?>; color: <?php echo $summary === 'true' ? 'white' : '#333'; ?>; text-decoration: none; border-radius: 5px;">Summary</a>
            <a href="fee_reports.php?view=ageing&summary=false" class="btn <?php echo $summary === 'false' ? 'active' : ''; ?>" style="padding: 8px 16px; background: <?php echo $summary === 'false' ? '#1d4e89' : '#f8f9fa'; ?>; color: <?php echo $summary === 'false' ? 'white' : '#333'; ?>; text-decoration: none; border-radius: 5px;">Detail</a>
          </div>
          
          <?php if ($summary === 'true'): ?>
            <!-- Summary View -->
            <div class="chart-container">
              <canvas id="ageingChart"></canvas>
            </div>
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
              <thead>
                <tr style="background: #1d4e89; color: white;">
                  <th style="padding: 12px; text-align: left;">Age Bucket</th>
                  <th style="padding: 12px; text-align: right;">Count</th>
                  <th style="padding: 12px; text-align: right;">Total Amount</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($ageing_summary as $bucket): ?>
                  <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 12px; font-weight: 600;"><?php echo htmlspecialchars($bucket['age_bucket']); ?></td>
                    <td style="padding: 12px; text-align: right;"><?php echo $bucket['count']; ?></td>
                    <td style="padding: 12px; text-align: right; font-weight: bold;">PGK <?php echo number_format($bucket['total_amount'], 2); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <!-- Detail View -->
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
              <thead>
                <tr style="background: #1d4e89; color: white;">
                  <th style="padding: 12px; text-align: left;">Student</th>
                  <th style="padding: 12px; text-align: left;">Program</th>
                  <th style="padding: 12px; text-align: right;">Outstanding</th>
                  <th style="padding: 12px; text-align: left;">Due Date</th>
                  <th style="padding: 12px; text-align: center;">Days Overdue</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($ageing_detail as $fee): ?>
                  <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 12px;"><?php echo htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']); ?></td>
                    <td style="padding: 12px;"><?php echo htmlspecialchars($fee['program_name'] ?? 'N/A'); ?></td>
                    <td style="padding: 12px; text-align: right; font-weight: bold;">PGK <?php echo number_format($fee['outstanding_amount'], 2); ?></td>
                    <td style="padding: 12px;"><?php echo date('M d, Y', strtotime($fee['due_date'])); ?></td>
                    <td style="padding: 12px; text-align: center;">
                      <?php if (isset($fee['days_overdue']) && $fee['days_overdue'] > 0): ?>
                        <span style="background: #dc3545; color: white; padding: 4px 8px; border-radius: 3px;">
                          <?php echo $fee['days_overdue']; ?> days
                        </span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script>
    function showView(viewName) {
      window.location.href = 'fee_reports.php?view=' + viewName;
    }

    // Charts initialization
    <?php if ($view === 'invoice_receipt' && !empty($invoice_receipt_data)): ?>
    const invoiceReceiptCtx = document.getElementById('invoiceReceiptChart').getContext('2d');
    new Chart(invoiceReceiptCtx, {
      type: 'bar',
      data: {
        labels: [<?php echo implode(',', array_map(function($r) { return "'" . date('M d', strtotime($r['date'])) . "'"; }, $invoice_receipt_data)); ?>],
        datasets: [{
          label: 'Invoices',
          data: [<?php echo implode(',', array_map(function($r) { return $r['invoices_amount'] ?? 0; }, $invoice_receipt_data)); ?>],
          backgroundColor: '#1d4e89'
        }, {
          label: 'Receipts',
          data: [<?php echo implode(',', array_map(function($r) { return $r['receipts_amount'] ?? 0; }, $invoice_receipt_data)); ?>],
          backgroundColor: '#28a745'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false
      }
    });
    <?php endif; ?>

    <?php if ($view === 'payment_mode' && !empty($payment_mode_data)): ?>
    const paymentModeCtx = document.getElementById('paymentModeChart').getContext('2d');
    new Chart(paymentModeCtx, {
      type: 'pie',
      data: {
        labels: [<?php echo implode(',', array_map(function($m) { return "'" . ucfirst(str_replace('_', ' ', $m['payment_method'])) . "'"; }, $payment_mode_data)); ?>],
        datasets: [{
          data: [<?php echo implode(',', array_map(function($m) { return $m['total_amount']; }, $payment_mode_data)); ?>],
          backgroundColor: ['#1d4e89', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false
      }
    });
    <?php endif; ?>

    <?php if ($view === 'receipts' && !empty($weekly_receipts)): ?>
    const weeklyReceiptsCtx = document.getElementById('weeklyReceiptsChart').getContext('2d');
    new Chart(weeklyReceiptsCtx, {
      type: 'line',
      data: {
        labels: [<?php echo implode(',', array_map(function($r) { return "'" . date('M d', strtotime($r['date'])) . "'"; }, $weekly_receipts)); ?>],
        datasets: [{
          label: 'Receipts (PGK)',
          data: [<?php echo implode(',', array_map(function($r) { return $r['total']; }, $weekly_receipts)); ?>],
          borderColor: '#28a745',
          backgroundColor: 'rgba(40, 167, 69, 0.1)',
          fill: true
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false
      }
    });
    <?php endif; ?>

    <?php if ($view === 'ageing' && $summary === 'true' && !empty($ageing_summary)): ?>
    const ageingCtx = document.getElementById('ageingChart').getContext('2d');
    new Chart(ageingCtx, {
      type: 'bar',
      data: {
        labels: [<?php echo implode(',', array_map(function($b) { return "'" . $b['age_bucket'] . "'"; }, $ageing_summary)); ?>],
        datasets: [{
          label: 'Amount (PGK)',
          data: [<?php echo implode(',', array_map(function($b) { return $b['total_amount']; }, $ageing_summary)); ?>],
          backgroundColor: ['#28a745', '#ffc107', '#ff9800', '#dc3545']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false
      }
    });
    <?php endif; ?>
  </script>
</body>
</html>

