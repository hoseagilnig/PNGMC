<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'finance') {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';

$message = '';
$message_type = '';
$conn = getDBConnection();
$payments = [];
$total_amount = 0;
$search_term = $_GET['search'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

if ($conn) {
    // Build query
    $where_conditions = [];
    $query_params = [];
    $param_types = '';
    
    if (!empty($search_term)) {
        $where_conditions[] = "(p.payment_number LIKE ? OR s.student_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR p.reference_number LIKE ?)";
        $search_param = '%' . $search_term . '%';
        $query_params = array_merge($query_params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
        $param_types .= 'sssss';
    }
    
    if (!empty($filter_date_from)) {
        $where_conditions[] = "p.payment_date >= ?";
        $query_params[] = $filter_date_from;
        $param_types .= 's';
    }
    
    if (!empty($filter_date_to)) {
        $where_conditions[] = "p.payment_date <= ?";
        $query_params[] = $filter_date_to;
        $param_types .= 's';
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Check if payments table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'payments'");
    if ($table_check && $table_check->num_rows > 0) {
        $sql = "SELECT p.*, 
                s.student_number, 
                s.first_name, 
                s.last_name,
                i.invoice_number,
                u.full_name as processed_by_name
                FROM payments p 
                LEFT JOIN students s ON p.student_id = s.student_id 
                LEFT JOIN invoices i ON p.invoice_id = i.invoice_id
                LEFT JOIN users u ON p.processed_by = u.user_id
                $where_clause
                ORDER BY p.payment_date DESC, p.created_at DESC 
                LIMIT 500";
        
        if (!empty($query_params)) {
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($param_types, ...$query_params);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result) {
                    $payments = $result->fetch_all(MYSQLI_ASSOC);
                }
                $stmt->close();
            }
        } else {
            $result = $conn->query($sql);
            if ($result) {
                $payments = $result->fetch_all(MYSQLI_ASSOC);
            }
        }
        
        // Calculate total
        foreach ($payments as $payment) {
            $total_amount += floatval($payment['amount']);
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
  <title>Payments - Finance</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <style>
    .message { padding: 12px; margin: 10px 0; border-radius: 5px; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .filter-section { background: var(--card-bg); padding: 20px; border-radius: 10px; margin-bottom: 20px; }
    .filter-row { display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; margin-bottom: 15px; align-items: end; }
    .filter-row input, .filter-row select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
    .btn { padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
    .btn:hover { opacity: 0.9; }
    .btn-secondary { background: #6c757d; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; background: var(--card-bg); }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: var(--primary); color: white; font-weight: 600; }
    tr:hover { background: #f8f9fa; }
    .text-right { text-align: right; }
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; }
    .badge-success { background: #28a745; color: white; }
    .summary-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    .summary-row { display: flex; justify-content: space-between; align-items: center; }
    .summary-label { font-weight: 600; color: #666; }
    .summary-value { font-size: 1.5rem; font-weight: bold; color: var(--primary); }
  </style>
</head>
<body>
  <?php echo getSidebarOverlay(); ?>
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
        <a class="menu-item" href="invoices.php">Invoices</a>
        <a class="menu-item active" href="payments.php">Payments</a>
        <a class="menu-item" href="fee_reports.php">Reports</a>
      </div>
    </nav>

    <div class="content">
      <header style="margin-bottom: 30px;">
        <h1>Payments</h1>
        <p class="small">View and manage all payment records</p>
      </header>

      <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <!-- Summary -->
      <div class="summary-box">
        <div class="summary-row">
          <div>
            <div class="summary-label">Total Payments Displayed</div>
            <div class="summary-value"><?php echo count($payments); ?></div>
          </div>
          <div>
            <div class="summary-label">Total Amount</div>
            <div class="summary-value">PGK <?php echo number_format($total_amount, 2); ?></div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filter-section">
        <form method="GET" action="payments.php">
          <div class="filter-row">
            <div>
              <label>Search</label>
              <input type="text" name="search" placeholder="Payment #, Student #, Name, Reference..." value="<?php echo htmlspecialchars($search_term); ?>">
            </div>
            <div>
              <label>Date From</label>
              <input type="date" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
            </div>
            <div>
              <label>Date To</label>
              <input type="date" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
            </div>
            <div>
              <button type="submit" class="btn">Filter</button>
              <a href="payments.php" class="btn btn-secondary" style="margin-left: 10px;">Clear</a>
            </div>
          </div>
        </form>
      </div>

      <!-- Payments Table -->
      <div class="form-section" style="background: var(--card-bg); padding: 20px; border-radius: 10px;">
        <h2 style="margin-bottom: 20px; color: var(--primary);">Payment Records</h2>
        
        <?php if (empty($payments)): ?>
          <p style="text-align: center; padding: 40px; color: #666;">No payments found.</p>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>Payment #</th>
                <th>Date</th>
                <th>Student</th>
                <th>Invoice #</th>
                <th>Method</th>
                <th>Reference</th>
                <th class="text-right">Amount (PGK)</th>
                <th>Processed By</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($payments as $payment): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($payment['payment_number'] ?? 'N/A'); ?></strong></td>
                  <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                  <td>
                    <?php if ($payment['student_number']): ?>
                      <?php echo htmlspecialchars($payment['student_number']); ?><br>
                      <small style="color: #666;"><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></small>
                    <?php else: ?>
                      <span style="color: #999;">N/A</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($payment['invoice_number']): ?>
                      <?php echo htmlspecialchars($payment['invoice_number']); ?>
                    <?php else: ?>
                      <span style="color: #999;">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge badge-success">
                      <?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'] ?? 'N/A')); ?>
                    </span>
                  </td>
                  <td><?php echo htmlspecialchars($payment['reference_number'] ?? '-'); ?></td>
                  <td class="text-right"><strong>PGK <?php echo number_format($payment['amount'], 2); ?></strong></td>
                  <td><?php echo htmlspecialchars($payment['processed_by_name'] ?? 'N/A'); ?></td>
                  <td><small style="color: #666;"><?php echo htmlspecialchars(substr($payment['notes'] ?? '', 0, 50)); ?><?php echo strlen($payment['notes'] ?? '') > 50 ? '...' : ''; ?></small></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr style="background: #f8f9fa;">
                <td colspan="6" style="text-align: right; font-weight: bold; padding: 12px;">Total:</td>
                <td class="text-right" style="font-weight: bold; padding: 12px;">PGK <?php echo number_format($total_amount, 2); ?></td>
                <td colspan="2"></td>
              </tr>
            </tfoot>
          </table>
        <?php endif; ?>
      </div>

      <!-- Quick Actions -->
      <div style="margin-top: 20px;">
        <a href="billing.php" class="btn">Record New Payment</a>
        <a href="fee_reports.php?view=payments" class="btn btn-secondary" style="margin-left: 10px;">View Payment Reports</a>
      </div>
    </div>
  </div>
</body>
</html>


