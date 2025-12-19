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

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    $conn = getDBConnection();
    if ($conn) {
        $invoice_id = $_POST['invoice_id'] ?? null;
        $student_id = $_POST['student_id'];
        $payment_date = $_POST['payment_date'];
        $amount = $_POST['amount'];
        $payment_method = $_POST['payment_method'];
        $reference_number = $_POST['reference_number'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        // Get payment prefix
        $prefix_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'payment_prefix'");
        $prefix = $prefix_result ? $prefix_result->fetch_assoc()['setting_value'] : 'PAY-';
        $payment_number = $prefix . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare("INSERT INTO payments (payment_number, invoice_id, student_id, payment_date, payment_method, amount, reference_number, notes, processed_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siisssssi", $payment_number, $invoice_id, $student_id, $payment_date, $payment_method, $amount, $reference_number, $notes, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            // Update invoice if linked
            if ($invoice_id) {
                $update_stmt = $conn->prepare("UPDATE invoices SET paid_amount = paid_amount + ?, balance_amount = balance_amount - ? WHERE invoice_id = ?");
                $update_stmt->bind_param("ddi", $amount, $amount, $invoice_id);
                $update_stmt->execute();
                
                // Update invoice status
                $conn->query("UPDATE invoices SET status = CASE 
                    WHEN balance_amount <= 0 THEN 'paid'
                    WHEN balance_amount < total_amount THEN 'partial'
                    WHEN due_date < CURDATE() THEN 'overdue'
                    ELSE 'sent'
                END WHERE invoice_id = $invoice_id");
                $update_stmt->close();
            }
            
            $message = "Payment recorded successfully! Payment #: " . $payment_number;
            $message_type = "success";
        } else {
            $message = "Error: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
        $conn->close();
    }
}

// Get data
$conn = getDBConnection();
$students = [];
$invoices = [];
$payments = [];
if ($conn) {
    $result = $conn->query("SELECT student_id, student_number, first_name, last_name FROM students WHERE status = 'active' ORDER BY last_name");
    if ($result) {
        $students = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    $result = $conn->query("SELECT invoice_id, invoice_number, student_id, balance_amount FROM invoices WHERE balance_amount > 0 ORDER BY due_date");
    if ($result) {
        $invoices = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    $result = $conn->query("SELECT p.*, s.student_number, s.first_name, s.last_name FROM payments p LEFT JOIN students s ON p.student_id = s.student_id ORDER BY p.created_at DESC LIMIT 50");
    if ($result) {
        $payments = $result->fetch_all(MYSQLI_ASSOC);
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Billing - Finance</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <style>
    .message { padding: 12px; margin: 10px 0; border-radius: 5px; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .form-section { background: var(--card-bg); padding: 20px; border-radius: 10px; margin-bottom: 20px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
    label { display: block; margin-bottom: 5px; font-weight: 600; color: var(--primary); }
    input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: var(--primary); color: white; }
    .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; background: var(--primary); color: white; }
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
        <a class="menu-item active" href="billing.php">Billing</a>
        <a class="menu-item" href="invoices.php">Invoices</a>
        <a class="menu-item" href="financial_reports.php">Financial Reports</a>
      </div>
    </nav>

    <div class="content">
      <div class="main-card">
        <h1>Billing & Payments</h1>
        
        <?php if ($message): ?>
          <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="form-section">
          <h2>Record Payment</h2>
          <form method="POST">
            <input type="hidden" name="action" value="record_payment">
            <div class="form-row">
              <div>
                <label>Student *</label>
                <select name="student_id" id="student_id" required>
                  <option value="">Select Student</option>
                  <?php foreach ($students as $student): ?>
                    <option value="<?php echo $student['student_id']; ?>">
                      <?php echo htmlspecialchars($student['student_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label>Invoice (Optional)</label>
                <select name="invoice_id" id="invoice_id">
                  <option value="">Select Invoice (Optional)</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div>
                <label>Payment Date *</label>
                <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
              </div>
              <div>
                <label>Amount *</label>
                <input type="number" name="amount" step="0.01" min="0.01" required>
              </div>
            </div>
            <div class="form-row">
              <div>
                <label>Payment Method *</label>
                <select name="payment_method" required>
                  <option value="cash">Cash</option>
                  <option value="bank_transfer">Bank Transfer</option>
                  <option value="check">Check</option>
                  <option value="card">Card</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div>
                <label>Reference Number</label>
                <input type="text" name="reference_number" placeholder="Transaction/Check number">
              </div>
            </div>
            <div class="form-row">
              <div>
                <label>Notes</label>
                <textarea name="notes" rows="2"></textarea>
              </div>
            </div>
            <button type="submit" class="btn">Record Payment</button>
          </form>
        </div>

        <div class="form-section">
          <h2>Recent Payments</h2>
          <table>
            <thead>
              <tr>
                <th>Payment #</th>
                <th>Student</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Reference</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($payments)): ?>
                <tr><td colspan="6" style="text-align: center;">No payments found.</td></tr>
              <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($payment['payment_number']); ?></td>
                    <td><?php echo htmlspecialchars(($payment['first_name'] ?? '') . ' ' . ($payment['last_name'] ?? '')); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($payment['payment_date'])); ?></td>
                    <td><?php echo number_format($payment['amount'], 2); ?></td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                    <td><?php echo htmlspecialchars($payment['reference_number'] ?? '-'); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('student_id').addEventListener('change', function() {
      const studentId = this.value;
      const invoiceSelect = document.getElementById('invoice_id');
      invoiceSelect.innerHTML = '<option value="">Select Invoice (Optional)</option>';
      
      if (studentId) {
        // Load invoices for this student
        fetch(`get_invoices.php?student_id=${studentId}`)
          .then(response => response.json())
          .then(data => {
            data.forEach(invoice => {
              const option = document.createElement('option');
              option.value = invoice.invoice_id;
              option.textContent = `${invoice.invoice_number} - Balance: ${parseFloat(invoice.balance_amount).toFixed(2)}`;
              invoiceSelect.appendChild(option);
            });
          })
          .catch(error => console.error('Error:', error));
      }
    });
  </script>
</body>
</html>

