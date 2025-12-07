<?php
session_start();
// Allow both finance and studentservices roles
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['finance', 'studentservices', 'admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';
require_once 'includes/finance_sas_helper.php';
require_once 'includes/workflow_helper.php';

$message = '';
$message_type = '';
$conn = getDBConnection();
$proforma_invoices = [];
$students = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $tables_exist = $conn->query("SHOW TABLES LIKE 'proforma_invoices'")->num_rows > 0;
    
    if ($tables_exist) {
        if (isset($_POST['create_pi'])) {
            $student_id = intval($_POST['student_id']);
            $date = $_POST['date'] ?? date('Y-m-d');
            $course_name = $_POST['course_name'] ?? '';
            $course_fee = floatval($_POST['course_fee'] ?? 0);
            
            // Get student details
            $student_result = $conn->query("SELECT * FROM students WHERE student_id = $student_id");
            $student = $student_result->fetch_assoc();
            
            if ($student) {
                $pi_number = generatePINumber();
                $balance = $course_fee;
                
                $remarks = $_POST['remarks'] ?? '';
                
                $stmt = $conn->prepare("INSERT INTO proforma_invoices (
                    pi_number, date, student_id, student_name, forwarding_address,
                    telephone, mobile_number, course_name, course_fee, balance, status,
                    pi_issuing_officer, remarks, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'outstanding', ?, ?, ?)");
                
                $student_name = $student['first_name'] . ' ' . $student['last_name'];
                $forwarding_address = $student['address'] ?? '';
                $telephone = $student['phone'] ?? '';
                $mobile_number = $student['phone'] ?? '';
                $pi_issuing_officer = $_SESSION['user_id'];
                $created_by = $_SESSION['user_id'];
                
                // Type string: s=string, i=integer, d=double
                // Parameters: pi_number(s), date(s), student_id(i), student_name(s), forwarding_address(s),
                //             telephone(s), mobile_number(s), course_name(s), course_fee(d), balance(d),
                //             pi_issuing_officer(i), remarks(s), created_by(i)
                // Total: 13 parameters
                $stmt->bind_param("ssisssssddisi",
                    $pi_number,           // s
                    $date,                // s
                    $student_id,           // i
                    $student_name,         // s
                    $forwarding_address,   // s
                    $telephone,           // s
                    $mobile_number,        // s
                    $course_name,          // s
                    $course_fee,          // d
                    $balance,             // d
                    $pi_issuing_officer,   // i
                    $remarks,             // s
                    $created_by           // i
                );
                
                if ($stmt->execute()) {
                    $pi_id = $conn->insert_id;
                    
                    // If created by Student Admin Service, notify Finance
                    if ($_SESSION['role'] === 'studentservices' || $_SESSION['role'] === 'admin') {
                        $notification_title = "📋 New Proforma Invoice from Student Admin Service";
                        $notification_message = "A new Proforma Invoice has been created:\n\nPI Number: {$pi_number}\nStudent: {$student_name}\nCourse: {$course_name}\nCourse Fee: PGK " . number_format($course_fee, 2) . "\n\nThis invoice should be reflected in MYOB. Please review and approve.";
                        
                        createWorkflowNotification(
                            0, // Not application-related
                            'studentservices',
                            'finance',
                            $notification_title,
                            $notification_message,
                            'action_required',
                            'proforma_invoice_details.php?id=' . $pi_id,
                            $_SESSION['user_id']
                        );
                    }
                    
                    $message = "Proforma Invoice created successfully! PI Number: $pi_number" . 
                        ($_SESSION['role'] === 'studentservices' || $_SESSION['role'] === 'admin' ? "<br><br>✅ Finance has been notified and will review this invoice." : "");
                    $message_type = "success";
                } else {
                    $message = "Error creating Proforma Invoice: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        } elseif (isset($_POST['add_payment'])) {
            $pi_id = intval($_POST['pi_id']);
            $receipt_number = $_POST['receipt_number'] ?? '';
            $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
            $amount = floatval($_POST['amount'] ?? 0);
            $payment_method = $_POST['payment_method'] ?? 'self';
            
            // Add payment
            $stmt = $conn->prepare("INSERT INTO proforma_invoice_payments (pi_id, receipt_number, payment_date, amount, payment_method) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issds", $pi_id, $receipt_number, $payment_date, $amount, $payment_method);
            
            if ($stmt->execute()) {
                // Update PI balance
                $conn->query("UPDATE proforma_invoices SET 
                    total_payments = total_payments + $amount,
                    balance = balance - $amount,
                    status = CASE 
                        WHEN (balance - $amount) <= 0 THEN 'refund_due'
                        ELSE 'outstanding'
                    END
                    WHERE pi_id = $pi_id");
                
                $message = "Payment added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding payment: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } elseif (isset($_POST['revise_pi'])) {
            $pi_id = intval($_POST['pi_id']);
            $revised_pi_number = $_POST['revised_pi_number'] ?? '';
            $revised_date = $_POST['revised_date'] ?? date('Y-m-d');
            $amendment_amount = floatval($_POST['amendment_amount'] ?? 0);
            $amendment_reason = $_POST['amendment_reason'] ?? 'other';
            
            $stmt = $conn->prepare("UPDATE proforma_invoices SET 
                revised_pi_number = ?,
                revised_date = ?,
                amendment_amount = ?,
                amendment_reason = ?,
                balance = balance + ?,
                course_fee = course_fee + ?
                WHERE pi_id = ?");
            
            $stmt->bind_param("ssdsddi", $revised_pi_number, $revised_date, $amendment_amount, $amendment_reason, $amendment_amount, $amendment_amount, $pi_id);
            
            if ($stmt->execute()) {
                $message = "Proforma Invoice revised successfully!";
                $message_type = "success";
            } else {
                $message = "Error revising PI: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}

// Get data
if ($conn) {
    $tables_exist = $conn->query("SHOW TABLES LIKE 'proforma_invoices'")->num_rows > 0;
    
    if ($tables_exist) {
        // Get proforma invoices
        $result = $conn->query("SELECT pi.*, 
            (SELECT COUNT(*) FROM proforma_invoice_payments WHERE pi_id = pi.pi_id) as payment_count,
            (SELECT GROUP_CONCAT(CONCAT(receipt_number, ' (', payment_date, ')') SEPARATOR ', ') 
             FROM proforma_invoice_payments WHERE pi_id = pi.pi_id) as payment_details
            FROM proforma_invoices pi 
            ORDER BY pi.date DESC, pi.pi_id DESC 
            LIMIT 100");
        if ($result) {
            $proforma_invoices = $result->fetch_all(MYSQLI_ASSOC);
        }
    }
    
    // Get students
    $result = $conn->query("SELECT s.*, p.program_name FROM students s 
        LEFT JOIN enrollments e ON s.student_id = e.student_id 
        LEFT JOIN programs p ON e.program_id = p.program_id 
        WHERE s.status = 'active' 
        ORDER BY s.first_name, s.last_name");
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
  <title>Proforma Invoices - Finance/SAS</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <style>
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
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
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
    }
    .modal-content {
      background: white;
      margin: 5% auto;
      padding: 30px;
      border-radius: 10px;
      width: 90%;
      max-width: 800px;
      max-height: 90vh;
      overflow-y: auto;
    }
    .badge {
      padding: 4px 8px;
      border-radius: 3px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    .badge-outstanding {
      background: #dc3545;
      color: white;
    }
    .badge-refund {
      background: #28a745;
      color: white;
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
        <h1>Proforma Invoices</h1>
        <p class="small">Manage Proforma Invoices - Updated record of students throughout the year. Reflects in MYOB.</p>
      </header>

      <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 5px; background: <?php echo $message_type === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $message_type === 'success' ? '#155724' : '#721c24'; ?>;">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <?php if ($_SESSION['role'] === 'studentservices' || $_SESSION['role'] === 'admin'): ?>
      <div class="main-card" style="margin-bottom: 30px;">
        <h2>Create New Proforma Invoice</h2>
        <p style="color: #666; margin-bottom: 20px; padding: 15px; background: #e7f3ff; border-left: 4px solid #1d4e89; border-radius: 5px;">
          <strong>Note:</strong> The Proforma Invoice is an updated record of the Student throughout the year. This invoice will be sent to Finance for review and should be reflected in MYOB.
        </p>
        <form method="POST">
          <div class="form-grid">
            <div class="form-group">
              <label>Student *</label>
              <select name="student_id" required>
                <option value="">Select Student</option>
                <?php foreach ($students as $student): ?>
                  <option value="<?php echo $student['student_id']; ?>">
                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_number'] . ')'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Date *</label>
              <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
              <label>Course Name *</label>
              <input type="text" name="course_name" required placeholder="e.g., Marine Engineering">
            </div>
            <div class="form-group">
              <label>Course Fee (PGK) *</label>
              <input type="number" name="course_fee" step="0.01" required placeholder="0.00">
            </div>
            <div class="form-group full-width">
              <label>Remarks</label>
              <textarea name="remarks" rows="3" placeholder="Any additional notes or comments"></textarea>
            </div>
          </div>
          <button type="submit" name="create_pi" class="btn btn-primary">Create Proforma Invoice & Send to Finance</button>
        </form>
      </div>
      <?php else: ?>
      <div class="main-card" style="margin-bottom: 30px; padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107;">
        <h2>Proforma Invoice Management</h2>
        <p style="color: #856404; margin: 0;">
          <strong>Note:</strong> Proforma Invoices are created by Student Admin Service and sent to Finance for review and approval. 
          Finance can add payments, approve invoices, and manage the financial records. All invoices should be reflected in MYOB.
        </p>
      </div>
      <?php endif; ?>

      <div class="main-card">
        <h2>Proforma Invoices</h2>
        <?php if (!empty($proforma_invoices)): ?>
          <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
              <tr style="background: #1d4e89; color: white;">
                <th style="padding: 12px; text-align: left;">PI Number</th>
                <th style="padding: 12px; text-align: left;">Date</th>
                <th style="padding: 12px; text-align: left;">Student Name</th>
                <th style="padding: 12px; text-align: left;">Course</th>
                <th style="padding: 12px; text-align: right;">Course Fee</th>
                <th style="padding: 12px; text-align: right;">Payments</th>
                <th style="padding: 12px; text-align: right;">Balance</th>
                <th style="padding: 12px; text-align: left;">Status</th>
                <th style="padding: 12px; text-align: left;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($proforma_invoices as $pi): ?>
                <tr style="border-bottom: 1px solid #ddd;">
                  <td style="padding: 12px;">
                    <strong><?php echo htmlspecialchars($pi['pi_number']); ?></strong>
                    <?php if ($pi['revised_pi_number']): ?>
                      <br><small style="color: #666;">Revised: <?php echo htmlspecialchars($pi['revised_pi_number']); ?></small>
                    <?php endif; ?>
                  </td>
                  <td style="padding: 12px;"><?php echo date('M d, Y', strtotime($pi['date'])); ?></td>
                  <td style="padding: 12px;"><?php echo htmlspecialchars($pi['student_name']); ?></td>
                  <td style="padding: 12px;"><?php echo htmlspecialchars($pi['course_name']); ?></td>
                  <td style="padding: 12px; text-align: right;">PGK <?php echo number_format($pi['course_fee'], 2); ?></td>
                  <td style="padding: 12px; text-align: right;">
                    PGK <?php echo number_format($pi['total_payments'], 2); ?>
                    <?php if ($pi['payment_count'] > 0): ?>
                      <br><small style="color: #666;">(<?php echo $pi['payment_count']; ?> payment(s))</small>
                    <?php endif; ?>
                  </td>
                  <td style="padding: 12px; text-align: right; font-weight: bold; color: <?php echo $pi['balance'] > 0 ? '#dc3545' : '#28a745'; ?>;">
                    PGK <?php echo number_format($pi['balance'], 2); ?>
                  </td>
                  <td style="padding: 12px;">
                    <span class="badge badge-<?php echo $pi['status'] === 'outstanding' ? 'outstanding' : 'refund'; ?>">
                      <?php echo ucfirst(str_replace('_', ' ', $pi['status'])); ?>
                    </span>
                  </td>
                  <td style="padding: 12px;">
                    <button onclick="viewPI(<?php echo $pi['pi_id']; ?>)" class="btn btn-primary" style="font-size: 12px; padding: 6px 12px;">View</button>
                    <button onclick="addPayment(<?php echo $pi['pi_id']; ?>)" class="btn btn-primary" style="font-size: 12px; padding: 6px 12px; margin-left: 5px;">Add Payment</button>
                    <?php if ($_SESSION['role'] === 'studentservices' || $_SESSION['role'] === 'admin'): ?>
                      <button onclick="revisePI(<?php echo $pi['pi_id']; ?>)" class="btn" style="font-size: 12px; padding: 6px 12px; margin-left: 5px; background: #ffc107; color: #000;">Revise</button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p style="color: #666; margin-top: 20px;">No Proforma Invoices found.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Add Payment Modal -->
  <div id="paymentModal" class="modal">
    <div class="modal-content">
      <h2>Add Payment</h2>
      <form method="POST">
        <input type="hidden" name="pi_id" id="payment_pi_id">
        <div class="form-grid">
          <div class="form-group">
            <label>Receipt Number *</label>
            <input type="text" name="receipt_number" required placeholder="PNGMC receipt number">
          </div>
          <div class="form-group">
            <label>Payment Date *</label>
            <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
          </div>
          <div class="form-group">
            <label>Amount (PGK) *</label>
            <input type="number" name="amount" step="0.01" required placeholder="0.00">
          </div>
          <div class="form-group">
            <label>Payment Method *</label>
            <select name="payment_method" required>
              <option value="self">Self</option>
              <option value="govt_funding">Govt Funding</option>
              <option value="shipping_company">Shipping Company</option>
              <option value="diversion">Diversion</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
        <button type="submit" name="add_payment" class="btn btn-primary">Add Payment</button>
        <button type="button" onclick="closeModal('paymentModal')" class="btn" style="background: #6c757d; color: white; margin-left: 10px;">Cancel</button>
      </form>
    </div>
  </div>

  <!-- Revise PI Modal -->
  <div id="reviseModal" class="modal">
    <div class="modal-content">
      <h2>Revise Proforma Invoice</h2>
      <form method="POST">
        <input type="hidden" name="pi_id" id="revise_pi_id">
        <div class="form-grid">
          <div class="form-group">
            <label>Revised PI Number *</label>
            <input type="text" name="revised_pi_number" required placeholder="MCSA-YYYY-XXXX">
          </div>
          <div class="form-group">
            <label>Revised Date *</label>
            <input type="date" name="revised_date" value="<?php echo date('Y-m-d'); ?>" required>
          </div>
          <div class="form-group">
            <label>Amendment Amount (PGK) *</label>
            <input type="number" name="amendment_amount" step="0.01" required placeholder="0.00 (use negative for reduction)">
          </div>
          <div class="form-group">
            <label>Amendment Reason *</label>
            <select name="amendment_reason" required>
              <option value="withdrawal">Withdrawal</option>
              <option value="disciplinary">Disciplinary</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
        <button type="submit" name="revise_pi" class="btn btn-primary">Revise PI</button>
        <button type="button" onclick="closeModal('reviseModal')" class="btn" style="background: #6c757d; color: white; margin-left: 10px;">Cancel</button>
      </form>
    </div>
  </div>

  <script>
    function addPayment(piId) {
      document.getElementById('payment_pi_id').value = piId;
      document.getElementById('paymentModal').style.display = 'block';
    }

    function revisePI(piId) {
      document.getElementById('revise_pi_id').value = piId;
      document.getElementById('reviseModal').style.display = 'block';
    }

    function viewPI(piId) {
      window.location.href = 'proforma_invoice_details.php?id=' + piId;
    }

    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
    }

    window.onclick = function(event) {
      if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
      }
    }
  </script>
</body>
</html>

