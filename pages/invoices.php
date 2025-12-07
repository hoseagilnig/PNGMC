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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    if ($conn) {
        if (isset($_POST['action']) && $_POST['action'] === 'create') {
            $student_id = $_POST['student_id'];
            $invoice_date = $_POST['invoice_date'];
            $due_date = $_POST['due_date'];
            $description = $_POST['description'];
            
            // Get invoice prefix
            $prefix_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'invoice_prefix'");
            $prefix = $prefix_result ? $prefix_result->fetch_assoc()['setting_value'] : 'INV-';
            
            // Generate invoice number
            $invoice_number = $prefix . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Calculate total from items
            $total = 0;
            if (isset($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    $total += floatval($item['total_price']);
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO invoices (invoice_number, student_id, invoice_date, due_date, total_amount, balance_amount, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sissdddi", $invoice_number, $student_id, $invoice_date, $due_date, $total, $total, $description, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $invoice_id = $conn->insert_id;
                
                // Insert invoice items
                if (isset($_POST['items'])) {
                    $item_stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, item_description, quantity, unit_price, total_price, item_type) VALUES (?, ?, ?, ?, ?, ?)");
                    foreach ($_POST['items'] as $item) {
                        $item_stmt->bind_param("isidds", $invoice_id, $item['description'], $item['quantity'], $item['unit_price'], $item['total_price'], $item['type']);
                        $item_stmt->execute();
                    }
                    $item_stmt->close();
                }
                
                $message = "Invoice created successfully! Invoice #: " . $invoice_number;
                $message_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        $conn->close();
    }
}

// Get all invoices
$conn = getDBConnection();
$invoices = [];
$students = [];
if ($conn) {
    $result = $conn->query("SELECT i.*, s.student_number, s.first_name, s.last_name FROM invoices i LEFT JOIN students s ON i.student_id = s.student_id ORDER BY i.created_at DESC");
    if ($result) {
        $invoices = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    $result = $conn->query("SELECT student_id, student_number, first_name, last_name FROM students WHERE status = 'active' ORDER BY last_name");
    if ($result) {
        $students = $result->fetch_all(MYSQLI_ASSOC);
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoices - Finance</title>
  <link rel="stylesheet" href="../css/d_styles.css">
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
    .badge { padding: 4px 8px; border-radius: 3px; font-size: 0.85rem; }
    .badge-paid { background: #28a745; color: white; }
    .badge-overdue { background: #dc3545; color: white; }
    .badge-pending { background: #ffc107; color: #000; }
    .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; }
    .btn-primary { background: var(--primary); color: white; }
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
        <a class="menu-item active" href="invoices.php">Invoices</a>
        <a class="menu-item" href="financial_reports.php">Financial Reports</a>
      </div>
    </nav>

    <div class="content">
      <div class="main-card">
        <h1>Manage Invoices</h1>
        
        <?php if ($message): ?>
          <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="form-section">
          <h2>Create New Invoice</h2>
          <form method="POST" id="invoiceForm">
            <input type="hidden" name="action" value="create">
            <div class="form-row">
              <div>
                <label>Student *</label>
                <select name="student_id" required>
                  <option value="">Select Student</option>
                  <?php foreach ($students as $student): ?>
                    <option value="<?php echo $student['student_id']; ?>">
                      <?php echo htmlspecialchars($student['student_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label>Invoice Date *</label>
                <input type="date" name="invoice_date" value="<?php echo date('Y-m-d'); ?>" required>
              </div>
            </div>
            <div class="form-row">
              <div>
                <label>Due Date *</label>
                <input type="date" name="due_date" required>
              </div>
              <div>
                <label>Description</label>
                <textarea name="description" rows="2"></textarea>
              </div>
            </div>
            <div id="itemsContainer">
              <h3>Invoice Items</h3>
              <div class="invoice-item">
                <div class="form-row">
                  <div><input type="text" name="items[0][description]" placeholder="Item Description" required></div>
                  <div><input type="number" name="items[0][quantity]" placeholder="Quantity" value="1" min="1" required></div>
                  <div><input type="number" name="items[0][unit_price]" placeholder="Unit Price" step="0.01" required></div>
                  <div>
                    <select name="items[0][type]" required>
                      <option value="tuition">Tuition</option>
                      <option value="dormitory">Dormitory</option>
                      <option value="fee">Fee</option>
                      <option value="other">Other</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
            <button type="button" onclick="addItem()" style="margin: 10px 0; padding: 8px 15px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">+ Add Item</button>
            <button type="submit" class="btn btn-primary">Create Invoice</button>
          </form>
        </div>

        <div class="form-section">
          <h2>All Invoices</h2>
          <table>
            <thead>
              <tr>
                <th>Invoice #</th>
                <th>Student</th>
                <th>Date</th>
                <th>Due Date</th>
                <th>Amount</th>
                <th>Paid</th>
                <th>Balance</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($invoices)): ?>
                <tr><td colspan="8" style="text-align: center;">No invoices found.</td></tr>
              <?php else: ?>
                <?php foreach ($invoices as $invoice): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                    <td><?php echo htmlspecialchars(($invoice['first_name'] ?? '') . ' ' . ($invoice['last_name'] ?? '')); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($invoice['invoice_date'])); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($invoice['due_date'])); ?></td>
                    <td><?php echo number_format($invoice['total_amount'], 2); ?></td>
                    <td><?php echo number_format($invoice['paid_amount'], 2); ?></td>
                    <td><?php echo number_format($invoice['balance_amount'], 2); ?></td>
                    <td>
                      <span class="badge badge-<?php 
                        echo $invoice['status'] === 'paid' ? 'paid' : 
                            ($invoice['status'] === 'overdue' ? 'overdue' : 'pending'); 
                      ?>"><?php echo ucfirst($invoice['status']); ?></span>
                    </td>
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
    let itemCount = 1;
    function addItem() {
      const container = document.getElementById('itemsContainer');
      const newItem = document.createElement('div');
      newItem.className = 'invoice-item';
      newItem.innerHTML = `
        <div class="form-row">
          <div><input type="text" name="items[${itemCount}][description]" placeholder="Item Description" required></div>
          <div><input type="number" name="items[${itemCount}][quantity]" placeholder="Quantity" value="1" min="1" required></div>
          <div><input type="number" name="items[${itemCount}][unit_price]" placeholder="Unit Price" step="0.01" required></div>
          <div>
            <select name="items[${itemCount}][type]" required>
              <option value="tuition">Tuition</option>
              <option value="dormitory">Dormitory</option>
              <option value="fee">Fee</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
      `;
      container.appendChild(newItem);
      itemCount++;
    }
    
    // Calculate total price on input
    document.addEventListener('input', function(e) {
      if (e.target.name && e.target.name.includes('[unit_price]') || e.target.name.includes('[quantity]')) {
        const row = e.target.closest('.invoice-item') || e.target.closest('.form-row');
        const quantity = row.querySelector('input[name*="[quantity]"]')?.value || 1;
        const unitPrice = row.querySelector('input[name*="[unit_price]"]')?.value || 0;
        // You can add total calculation display here if needed
      }
    });
    
    // Calculate totals before submit
    document.getElementById('invoiceForm').addEventListener('submit', function(e) {
      const items = document.querySelectorAll('.invoice-item');
      items.forEach((item, index) => {
        const quantity = parseFloat(item.querySelector('input[name*="[quantity]"]').value) || 0;
        const unitPrice = parseFloat(item.querySelector('input[name*="[unit_price]"]').value) || 0;
        const totalPrice = quantity * unitPrice;
        
        // Add hidden input for total_price
        let totalInput = item.querySelector('input[name*="[total_price]"]');
        if (!totalInput) {
          totalInput = document.createElement('input');
          totalInput.type = 'hidden';
          totalInput.name = `items[${index}][total_price]`;
          item.appendChild(totalInput);
        }
        totalInput.value = totalPrice.toFixed(2);
      });
    });
  </script>
</body>
</html>

