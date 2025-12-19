<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'finance') {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';
require_once 'includes/workflow_helper.php';

$message = '';
$message_type = '';
$conn = getDBConnection();
$transfers = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $tables_exist = $conn->query("SHOW TABLES LIKE 'finance_to_sas_transfers'")->num_rows > 0;
    
    if ($tables_exist) {
        if (isset($_POST['transfer_data'])) {
            $receipt_number = $_POST['receipt_number'] ?? '';
            $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
            $student_id = !empty($_POST['student_id']) ? intval($_POST['student_id']) : null;
            $amount = floatval($_POST['amount'] ?? 0);
            $ar_record_summary = $_POST['ar_record_summary'] ?? '';
            $ar_record_individual = $_POST['ar_record_individual'] ?? '';
            $transferred_by = $_SESSION['user_id'];
            
            if (empty($receipt_number)) {
                $message = "❌ Payment Receipt Number is required.";
                $message_type = "error";
            } elseif ($amount <= 0) {
                $message = "❌ Amount must be greater than 0.";
                $message_type = "error";
            } else {
                $stmt = $conn->prepare("INSERT INTO finance_to_sas_transfers (
                    receipt_number, payment_date, student_id, amount, ar_record_summary, ar_record_individual, transferred_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                if (!$stmt) {
                    $message = "❌ Database error: " . $conn->error;
                    $message_type = "error";
                } else {
                    $stmt->bind_param("ssidssi", $receipt_number, $payment_date, $student_id, $amount, $ar_record_summary, $ar_record_individual, $transferred_by);
                    
                    if ($stmt->execute()) {
                        $transfer_id = $conn->insert_id;
                        
                        // Get student name if student_id provided
                        $student_name = '';
                        if ($student_id) {
                            $student_result = $conn->query("SELECT first_name, last_name, student_number FROM students WHERE student_id = $student_id");
                            if ($student_result && $student_result->num_rows > 0) {
                                $student = $student_result->fetch_assoc();
                                $student_name = " for " . $student['first_name'] . " " . $student['last_name'] . " (" . $student['student_number'] . ")";
                            }
                        }
                        
                        // Create workflow notification for SAS
                        $notification_title = "💰 Payment Data Received from Finance";
                        $notification_message = "Payment Receipt: {$receipt_number}\nPayment Date: " . date('M d, Y', strtotime($payment_date)) . "\nAmount: PGK " . number_format($amount, 2) . $student_name . "\n\nClick to view full details and AR records.";
                        
                        // Create notification (using application_id = 0 for non-application related transfers)
                        $notification_result = createWorkflowNotification(
                            0, // application_id (0 for non-application transfers)
                            'finance',
                            'studentservices',
                            $notification_title,
                            $notification_message,
                            'information',
                            'sas_received_data.php',
                            $_SESSION['user_id']
                        );
                        
                        if ($notification_result) {
                            $message = "✅ Data transferred to SAS successfully!<br><strong>Receipt Number:</strong> {$receipt_number}<br><strong>Amount:</strong> PGK " . number_format($amount, 2) . "<br><br>SAS has been notified and can view this data in 'Received from Finance'.";
                        } else {
                            $message = "⚠️ Data transferred to database, but notification failed. Receipt Number: {$receipt_number}, Amount: PGK " . number_format($amount, 2);
                        }
                        $message_type = "success";
                    } else {
                        $message = "❌ Error transferring data: " . $stmt->error;
                        $message_type = "error";
                    }
                    $stmt->close();
                }
            }
        }
    } else {
        $message = "❌ Database table 'finance_to_sas_transfers' does not exist.<br><br>Please run the database setup script: <a href='../database/create_finance_sas_workflow_tables.php' style='color: #1d4e89; font-weight: bold; text-decoration: underline;' target='_blank'>Create Finance-SAS Workflow Tables</a>";
        $message_type = "error";
    }
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $message = "❌ Database connection failed. Please contact administrator.";
        $message_type = "error";
    }
}

// Get transfers (use a new connection to avoid closing the one used for form processing)
$conn_for_display = getDBConnection();
if ($conn_for_display) {
    $tables_exist = $conn_for_display->query("SHOW TABLES LIKE 'finance_to_sas_transfers'")->num_rows > 0;
    
    if ($tables_exist) {
        $result = $conn_for_display->query("SELECT ft.*, s.first_name, s.last_name, s.student_number 
            FROM finance_to_sas_transfers ft
            LEFT JOIN students s ON ft.student_id = s.student_id
            ORDER BY ft.transferred_at DESC 
            LIMIT 100");
        if ($result) {
            $transfers = $result->fetch_all(MYSQLI_ASSOC);
        }
    }
    
    // Get students
    $result = $conn_for_display->query("SELECT * FROM students WHERE status = 'active' ORDER BY first_name, last_name");
    $students = [];
    if ($result) {
        $students = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    $conn_for_display->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Finance to SAS Data Transfer - Finance</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <style>
    .form-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 20px;
    }
    @media (min-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr 1fr;
      }
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 600;
      color: #333;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 14px;
    }
    .form-group.full-width {
      grid-column: 1 / -1;
    }
    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 600;
    }
    .btn-primary {
      background: #1d4e89;
      color: white;
    }
    .info-box {
      background: #e7f3ff;
      border-left: 4px solid #1d4e89;
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 5px;
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
        <div class="menu-section">Workflow</div>
        <a class="menu-item" href="proforma_invoices.php">Proforma Invoices</a>
        <a class="menu-item" href="student_schedules.php">Student Schedules</a>
        <a class="menu-item" href="fees_monitor.php">Fees Monitor</a>
        <a class="menu-item" href="red_green_days.php">Red & Green Days</a>
        <a class="menu-item active" href="finance_to_sas.php">Finance to SAS</a>
      </div>
    </nav>

    <div class="content">
      <header style="margin-bottom: 30px;">
        <h1>Finance to SAS Data Transfer</h1>
        <p class="small">Transfer payment data from Finance to Student Admin Services: Payment Receipt Numbers (PNGMC receipt book), Payment Date (per bank statement), and AR Records (MYOB - Summary & Individual).</p>
      </header>

      <?php if (!$tables_exist): ?>
        <div class="info-box" style="background: #fff3cd; border-left: 4px solid #ffc107; margin-bottom: 20px;">
          <strong>⚠️ Database Setup Required</strong>
          <p style="margin: 10px 0 0 0;">The database table 'finance_to_sas_transfers' does not exist. Please run the setup script first:</p>
          <p style="margin: 10px 0 0 0;">
            <a href="../database/create_finance_sas_workflow_tables.php" style="color: #1d4e89; font-weight: bold; text-decoration: underline; font-size: 1.1rem;" target="_blank">→ Create Finance-SAS Workflow Tables</a>
          </p>
        </div>
      <?php endif; ?>

      <div class="info-box">
        <strong>📋 Data Transfer from Finance to SAS:</strong>
        <ul style="margin: 10px 0 0 20px; padding: 0;">
          <li><strong>Payment Receipt Numbers:</strong> PNGMC receipt book numbers</li>
          <li><strong>Payment Date:</strong> Per bank statement</li>
          <li><strong>AR Records:</strong> MYOB - Summary & Individual records</li>
        </ul>
        <p style="margin: 15px 0 0 0; padding: 10px; background: white; border-radius: 5px; border-left: 3px solid #1d4e89;">
          <strong>Workflow:</strong> Finance transfers payment receipt data to Student Admin Services. SAS receives notifications and can view all transferred data including receipt numbers, payment dates, and MYOB AR records.
        </p>
      </div>

      <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>" style="padding: 20px; margin-bottom: 20px; border-radius: 8px; background: <?php echo $message_type === 'success' ? 'linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%)' : '#f8d7da'; ?>; color: <?php echo $message_type === 'success' ? '#155724' : '#721c24'; ?>; border: 2px solid <?php echo $message_type === 'success' ? '#28a745' : '#dc3545'; ?>; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
          <div style="display: flex; align-items: start; gap: 15px;">
            <div style="font-size: 2.5rem;"><?php echo $message_type === 'success' ? '✅' : '❌'; ?></div>
            <div style="flex: 1; font-size: 1.05rem;">
              <?php echo $message; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!$tables_exist): ?>
        <div class="info-box" style="background: #fff3cd; border-left: 4px solid #ffc107; margin-bottom: 20px;">
          <strong>⚠️ Database Setup Required</strong>
          <p style="margin: 10px 0 0 0;">The database table 'finance_to_sas_transfers' does not exist. Please run the setup script first:</p>
          <p style="margin: 10px 0 0 0;">
            <a href="../database/create_finance_sas_workflow_tables.php" style="color: #1d4e89; font-weight: bold; text-decoration: underline; font-size: 1.1rem;" target="_blank">→ Create Finance-SAS Workflow Tables</a>
          </p>
        </div>
      <?php endif; ?>

      <div class="main-card" style="margin-bottom: 30px;">
        <h2>Transfer Data to SAS</h2>
        <form method="POST" <?php echo !$tables_exist ? 'onsubmit="alert(\'Please run the database setup script first!\'); return false;"' : ''; ?>>
          <div class="form-grid">
            <div class="form-group">
              <label>Payment Receipt Number (PNGMC) *</label>
              <input type="text" name="receipt_number" required placeholder="PNGMC receipt number">
            </div>
            <div class="form-group">
              <label>Payment Date (Per Bank Statement) *</label>
              <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
              <label>Student (Optional)</label>
              <select name="student_id">
                <option value="">Select Student (Optional)</option>
                <?php foreach ($students as $student): ?>
                  <option value="<?php echo $student['student_id']; ?>">
                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_number'] . ')'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Amount (PGK) *</label>
              <input type="number" name="amount" step="0.01" required placeholder="0.00">
            </div>
            <div class="form-group full-width">
              <label>AR Record - Summary (MYOB)</label>
              <textarea name="ar_record_summary" rows="4" placeholder="MYOB AR Summary record..."></textarea>
            </div>
            <div class="form-group full-width">
              <label>AR Record - Individual (MYOB)</label>
              <textarea name="ar_record_individual" rows="4" placeholder="MYOB AR Individual record..."></textarea>
            </div>
          </div>
          <button type="submit" name="transfer_data" class="btn btn-primary">Transfer to SAS</button>
        </form>
      </div>

      <div class="main-card">
        <h2>Recent Transfers</h2>
        <?php if (!empty($transfers)): ?>
          <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
              <tr style="background: #1d4e89; color: white;">
                <th style="padding: 12px; text-align: left;">Receipt Number</th>
                <th style="padding: 12px; text-align: left;">Payment Date</th>
                <th style="padding: 12px; text-align: left;">Student</th>
                <th style="padding: 12px; text-align: right;">Amount</th>
                <th style="padding: 12px; text-align: left;">AR Summary</th>
                <th style="padding: 12px; text-align: left;">AR Individual</th>
                <th style="padding: 12px; text-align: left;">Transferred At</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($transfers as $transfer): ?>
                <tr style="border-bottom: 1px solid #ddd;">
                  <td style="padding: 12px;"><strong><?php echo htmlspecialchars($transfer['receipt_number']); ?></strong></td>
                  <td style="padding: 12px;"><?php echo date('M d, Y', strtotime($transfer['payment_date'])); ?></td>
                  <td style="padding: 12px;">
                    <?php if ($transfer['student_id']): ?>
                      <?php echo htmlspecialchars($transfer['first_name'] . ' ' . $transfer['last_name']); ?><br>
                      <small style="color: #666;"><?php echo htmlspecialchars($transfer['student_number']); ?></small>
                    <?php else: ?>
                      N/A
                    <?php endif; ?>
                  </td>
                  <td style="padding: 12px; text-align: right; font-weight: bold;">PGK <?php echo number_format($transfer['amount'], 2); ?></td>
                  <td style="padding: 12px;">
                    <?php if ($transfer['ar_record_summary']): ?>
                      <small style="color: #666;"><?php echo htmlspecialchars(substr($transfer['ar_record_summary'], 0, 50)); ?>...</small>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                  <td style="padding: 12px;">
                    <?php if ($transfer['ar_record_individual']): ?>
                      <small style="color: #666;"><?php echo htmlspecialchars(substr($transfer['ar_record_individual'], 0, 50)); ?>...</small>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                  <td style="padding: 12px;"><?php echo date('M d, Y H:i', strtotime($transfer['transferred_at'])); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p style="color: #666; margin-top: 20px;">No transfers found.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>


