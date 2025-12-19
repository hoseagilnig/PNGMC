<?php
session_start();
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['finance', 'studentservices', 'admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';

$pi_id = intval($_GET['id'] ?? 0);
$conn = getDBConnection();
$pi = null;
$payments = [];

if ($conn && $pi_id) {
    $tables_exist = $conn->query("SHOW TABLES LIKE 'proforma_invoices'")->num_rows > 0;
    
    if ($tables_exist) {
        // Get PI details
        $result = $conn->query("SELECT pi.*, 
            u1.full_name as issuing_officer_name,
            u2.full_name as registrar_name
            FROM proforma_invoices pi
            LEFT JOIN users u1 ON pi.pi_issuing_officer = u1.user_id
            LEFT JOIN users u2 ON pi.approval_by_registrar = u2.user_id
            WHERE pi.pi_id = $pi_id");
        if ($result) {
            $pi = $result->fetch_assoc();
        }
        
        // Get payments
        if ($pi) {
            $result = $conn->query("SELECT * FROM proforma_invoice_payments WHERE pi_id = $pi_id ORDER BY payment_date DESC");
            if ($result) {
                $payments = $result->fetch_all(MYSQLI_ASSOC);
            }
        }
    }
    
    $conn->close();
}

if (!$pi) {
    header('Location: proforma_invoices.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Proforma Invoice Details - <?php echo htmlspecialchars($pi['pi_number']); ?></title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <style>
    .detail-section {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    .detail-row {
      display: grid;
      grid-template-columns: 200px 1fr;
      gap: 15px;
      padding: 10px 0;
      border-bottom: 1px solid #ddd;
    }
    .detail-row:last-child {
      border-bottom: none;
    }
    .detail-label {
      font-weight: 600;
      color: #666;
    }
    .detail-value {
      color: #333;
    }
    .payment-item {
      background: white;
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 10px;
      border-left: 4px solid #28a745;
    }
  </style>
</head>
<body>
    <div class="dashboard-wrap container">
    <nav class="sidebar" aria-label="Main navigation">
      <div class="brand">
        <a href="<?php echo $_SESSION['role'] === 'finance' ? 'finance_dashboard.php' : 'student_service_dashboard.php'; ?>" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
          <img src="../images/pnmc.png" alt="logo"> 
          <strong>PNGMC</strong>
        </a>
      </div>
      <div class="menu">
        <?php if ($_SESSION['role'] === 'finance'): ?>
          <a class="menu-item" href="finance_dashboard.php">Dashboard</a>
          <div class="menu-section">Workflow</div>
          <a class="menu-item active" href="proforma_invoices.php">Proforma Invoices</a>
          <a class="menu-item" href="student_schedules.php">Student Schedules</a>
          <a class="menu-item" href="fees_monitor.php">Fees Monitor</a>
          <a class="menu-item" href="red_green_days.php">Red & Green Days</a>
          <a class="menu-item" href="finance_to_sas.php">Finance to SAS</a>
        <?php else: ?>
          <a class="menu-item" href="student_service_dashboard.php">Dashboard</a>
          <div class="menu-section">Workflow</div>
          <a class="menu-item active" href="proforma_invoices.php">Proforma Invoices</a>
          <a class="menu-item" href="withdrawal_advice.php">Withdrawal Advice</a>
          <a class="menu-item" href="disciplinary_advice.php">Disciplinary Advice</a>
          <a class="menu-item" href="student_schedules.php">Student Schedules</a>
        <?php endif; ?>
      </div>
    </nav>

    <div class="content">
      <header style="margin-bottom: 30px;">
        <h1>Proforma Invoice Details</h1>
        <p class="small"><a href="proforma_invoices.php" style="color: #1d4e89; text-decoration: none;">← Back to Proforma Invoices</a></p>
      </header>

      <div class="main-card" style="margin-bottom: 30px;">
        <h2>Invoice Information</h2>
        <div class="detail-section">
          <div class="detail-row">
            <div class="detail-label">PI Number:</div>
            <div class="detail-value"><strong><?php echo htmlspecialchars($pi['pi_number']); ?></strong></div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Date:</div>
            <div class="detail-value"><?php echo date('M d, Y', strtotime($pi['date'])); ?></div>
          </div>
          <?php if ($pi['revised_pi_number']): ?>
          <div class="detail-row">
            <div class="detail-label">Revised PI Number:</div>
            <div class="detail-value"><strong><?php echo htmlspecialchars($pi['revised_pi_number']); ?></strong></div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Revised Date:</div>
            <div class="detail-value"><?php echo date('M d, Y', strtotime($pi['revised_date'])); ?></div>
          </div>
          <?php endif; ?>
          <div class="detail-row">
            <div class="detail-label">Student Name:</div>
            <div class="detail-value"><?php echo htmlspecialchars($pi['student_name']); ?></div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Forwarding Address:</div>
            <div class="detail-value"><?php echo htmlspecialchars($pi['forwarding_address'] ?? 'N/A'); ?></div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Telephone/Mobile:</div>
            <div class="detail-value"><?php echo htmlspecialchars($pi['telephone'] ?? 'N/A'); ?> / <?php echo htmlspecialchars($pi['mobile_number'] ?? 'N/A'); ?></div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Course Name:</div>
            <div class="detail-value"><?php echo htmlspecialchars($pi['course_name']); ?></div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Course Fee:</div>
            <div class="detail-value"><strong>PGK <?php echo number_format($pi['course_fee'], 2); ?></strong></div>
          </div>
          <?php if ($pi['amendment_amount'] != 0): ?>
          <div class="detail-row">
            <div class="detail-label">Amendment:</div>
            <div class="detail-value">
              <strong style="color: <?php echo $pi['amendment_amount'] > 0 ? '#dc3545' : '#28a745'; ?>;">
                <?php echo $pi['amendment_amount'] > 0 ? '+' : ''; ?>PGK <?php echo number_format($pi['amendment_amount'], 2); ?>
              </strong>
              (<?php echo ucfirst($pi['amendment_reason']); ?>)
            </div>
          </div>
          <?php endif; ?>
          <div class="detail-row">
            <div class="detail-label">Total Payments:</div>
            <div class="detail-value" style="color: #28a745; font-weight: 600;">
              PGK <?php echo number_format($pi['total_payments'], 2); ?>
              <?php if (!empty($payments)): ?>
                <br><small style="color: #666; font-weight: normal; margin-top: 5px; display: block;">
                  Payment Methods: <?php 
                    $methods = array_unique(array_map(function($p) { 
                      return ucfirst(str_replace('_', ' ', $p['payment_method'])); 
                    }, $payments));
                    echo implode(', ', $methods);
                  ?>
                </small>
              <?php endif; ?>
            </div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Balance:</div>
            <div class="detail-value" style="font-weight: bold; color: <?php echo $pi['balance'] > 0 ? '#dc3545' : '#28a745'; ?>;">
              PGK <?php echo number_format($pi['balance'], 2); ?>
            </div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Balance Status:</div>
            <div class="detail-value">
              <?php if ($pi['balance'] > 0): ?>
                <span style="padding: 4px 8px; border-radius: 3px; background: #dc3545; color: white; font-weight: 600;">Outstanding</span>
              <?php else: ?>
                <span style="padding: 4px 8px; border-radius: 3px; background: #28a745; color: white; font-weight: 600;">Refund Due</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="detail-row">
            <div class="detail-label">PI Issuing Officer:</div>
            <div class="detail-value"><?php echo htmlspecialchars($pi['issuing_officer_name'] ?? 'N/A'); ?></div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Date (Issued):</div>
            <div class="detail-value"><?php echo date('M d, Y', strtotime($pi['date'])); ?></div>
          </div>
          <?php if ($pi['approval_by_registrar']): ?>
          <div class="detail-row">
            <div class="detail-label">Approval by Registrar:</div>
            <div class="detail-value"><?php echo htmlspecialchars($pi['registrar_name']); ?> (<?php echo date('M d, Y', strtotime($pi['approval_date'])); ?>)</div>
          </div>
          <?php elseif ($_SESSION['role'] === 'finance' || $_SESSION['role'] === 'admin'): ?>
          <div class="detail-row">
            <div class="detail-label">Approval:</div>
            <div class="detail-value">
              <form method="POST" action="proforma_invoices.php" style="display: inline;">
                <input type="hidden" name="pi_id" value="<?php echo $pi['pi_id']; ?>">
                <button type="submit" name="approve_pi" class="btn" style="background: #28a745; color: white; padding: 6px 15px; border: none; border-radius: 5px; cursor: pointer;">Approve by Registrar</button>
              </form>
            </div>
          </div>
          <?php endif; ?>
          <?php if ($pi['remarks']): ?>
          <div class="detail-row">
            <div class="detail-label">Remarks:</div>
            <div class="detail-value"><?php echo htmlspecialchars($pi['remarks']); ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="main-card" style="margin-bottom: 30px;">
        <h2>Payments</h2>
        <p style="color: #666; margin-bottom: 15px; font-style: italic;">
          <strong>Note:</strong> Payments should include receipt numbers and dates. Multiple payments can be recorded for this invoice.
        </p>
        <?php if (!empty($payments)): ?>
          <table style="width: 100%; border-collapse: collapse;">
            <thead>
              <tr style="background: #1d4e89; color: white;">
                <th style="padding: 12px; text-align: left;">Receipt Number</th>
                <th style="padding: 12px; text-align: left;">Payment Date</th>
                <th style="padding: 12px; text-align: left;">Payment Method</th>
                <th style="padding: 12px; text-align: right;">Amount (PGK)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($payments as $payment): ?>
                <tr style="border-bottom: 1px solid #ddd;">
                  <td style="padding: 12px;"><strong><?php echo htmlspecialchars($payment['receipt_number']); ?></strong></td>
                  <td style="padding: 12px;"><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                  <td style="padding: 12px;"><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                  <td style="padding: 12px; text-align: right; font-weight: bold; color: #28a745;">PGK <?php echo number_format($payment['amount'], 2); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p style="color: #666;">No payments recorded yet.</p>
        <?php endif; ?>
      </div>
      
      <div class="main-card" style="background: #e7f3ff; border-left: 4px solid #1d4e89; padding: 20px; margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
          <div>
            <h3 style="margin-top: 0; color: #1d4e89;">MYOB Integration</h3>
            <p style="color: #666; margin: 0 0 15px 0;">
              <strong>Important:</strong> This Proforma Invoice should be reflected in MYOB accounting system. 
              The invoice represents an updated record of the student throughout the year and must be synchronized with MYOB for accurate financial reporting.
            </p>
          </div>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
          <a href="export_myob.php?pi_id=<?php echo $pi['pi_id']; ?>&format=csv" 
             class="btn" 
             style="background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;"
             onmouseover="this.style.background='#218838'; this.style.transform='translateY(-2px)';"
             onmouseout="this.style.background='#28a745'; this.style.transform='';">
            📥 Export to CSV (MYOB Import)
          </a>
          <button onclick="copyMyOBData()" 
                  class="btn" 
                  style="background: #17a2b8; color: white; padding: 10px 20px; border-radius: 5px; border: none; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;"
                  onmouseover="this.style.background='#138496'; this.style.transform='translateY(-2px)';"
                  onmouseout="this.style.background='#17a2b8'; this.style.transform='';">
            📋 Copy MYOB Data
          </button>
          <a href="print_proforma_invoice.php?id=<?php echo $pi['pi_id']; ?>" 
             target="_blank"
             class="btn" 
             style="background: #6c757d; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;"
             onmouseover="this.style.background='#5a6268'; this.style.transform='translateY(-2px)';"
             onmouseout="this.style.background='#6c757d'; this.style.transform='';">
            🖨️ Print Invoice
          </a>
        </div>
        <div id="myobData" style="display: none; margin-top: 15px; padding: 15px; background: white; border-radius: 5px; border: 1px solid #ddd;">
          <strong style="color: #1d4e89; display: block; margin-bottom: 10px;">MYOB Import Data:</strong>
          <pre style="margin: 0; font-size: 0.85rem; color: #333; white-space: pre-wrap; word-wrap: break-word;"><?php
            // Generate MYOB-compatible data format
            $myob_data = [];
            $myob_data[] = "Invoice Number: " . $pi['pi_number'];
            $myob_data[] = "Date: " . date('d/m/Y', strtotime($pi['date']));
            $myob_data[] = "Student: " . $pi['student_name'];
            $myob_data[] = "Course: " . $pi['course_name'];
            $myob_data[] = "Course Fee: PGK " . number_format($pi['course_fee'], 2);
            $myob_data[] = "Total Payments: PGK " . number_format($pi['total_payments'], 2);
            if ($pi['amendment_amount'] != 0) {
              $myob_data[] = "Amendment: PGK " . number_format($pi['amendment_amount'], 2) . " (" . ucfirst($pi['amendment_reason']) . ")";
            }
            $myob_data[] = "Balance: PGK " . number_format($pi['balance'], 2);
            $myob_data[] = "Status: " . ucfirst(str_replace('_', ' ', $pi['status']));
            if (!empty($payments)) {
              $myob_data[] = "";
              $myob_data[] = "Payments:";
              foreach ($payments as $payment) {
                $myob_data[] = "  - Receipt: " . $payment['receipt_number'] . ", Date: " . date('d/m/Y', strtotime($payment['payment_date'])) . ", Amount: PGK " . number_format($payment['amount'], 2) . ", Method: " . ucfirst(str_replace('_', ' ', $payment['payment_method']));
              }
            }
            echo htmlspecialchars(implode("\n", $myob_data));
          ?></pre>
        </div>
      </div>
      
      <script>
        function copyMyOBData() {
          const dataDiv = document.getElementById('myobData');
          if (dataDiv.style.display === 'none') {
            dataDiv.style.display = 'block';
          }
          
          const text = dataDiv.querySelector('pre').textContent;
          navigator.clipboard.writeText(text).then(function() {
            alert('MYOB data copied to clipboard! You can now paste it into MYOB.');
          }, function(err) {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('MYOB data copied to clipboard! You can now paste it into MYOB.');
          });
        }
      </script>
    </div>
  </div>
</body>
</html>

