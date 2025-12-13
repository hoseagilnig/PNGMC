<?php
session_start();
// Allow studentservices, admin, and finance roles
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['studentservices', 'admin', 'finance'])) {
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
$withdrawal_advices = [];
$students = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $tables_exist = $conn->query("SHOW TABLES LIKE 'withdrawal_advice'")->num_rows > 0;
    
    if ($tables_exist) {
        if (isset($_POST['create_advice'])) {
            $student_id = intval($_POST['student_id']);
            $program_course_name = $_POST['program_course_name'] ?? '';
            $program_course_fee = floatval($_POST['program_course_fee'] ?? 0);
            $reason = $_POST['reason'] ?? 'other';
            $reason_details = $_POST['reason_details'] ?? '';
            $action_taken = $_POST['action_taken'] ?? 'none';
            $fee_amendment = floatval($_POST['fee_amendment'] ?? 0);
            $amendment_type = $_POST['amendment_type'] ?? 'reduction';
            $start_date = $_POST['start_date'] ?? date('Y-m-d');
            $ending_date = $_POST['ending_date'] ?? null;
            $paid_fees = floatval($_POST['paid_fees'] ?? 0);
            $remarks = $_POST['remarks'] ?? '';
            
            // Get student details
            $student_result = $conn->query("SELECT * FROM students WHERE student_id = $student_id");
            $student = $student_result->fetch_assoc();
            
            if ($student) {
                // Generate advice number
                $year = date('Y');
                $prefix = "WA-{$year}-";
                $result = $conn->query("SELECT advice_number FROM withdrawal_advice WHERE advice_number LIKE '{$prefix}%' ORDER BY withdrawal_id DESC LIMIT 1");
                if ($result && $result->num_rows > 0) {
                    $last_number = $result->fetch_assoc()['advice_number'];
                    $last_seq = intval(substr($last_number, strrpos($last_number, '-') + 1));
                    $new_seq = str_pad($last_seq + 1, 4, '0', STR_PAD_LEFT);
                } else {
                    $new_seq = '0001';
                }
                $advice_number = $prefix . $new_seq;
                
                $balance = $program_course_fee - $paid_fees;
                if ($amendment_type === 'reduction') {
                    $balance -= $fee_amendment;
                } else {
                    $balance += $fee_amendment;
                }
                
                $student_name = $student['first_name'] . ' ' . $student['last_name'];
                $created_by = $_SESSION['user_id'];
                
                $stmt = $conn->prepare("INSERT INTO withdrawal_advice (
                    advice_number, student_id, student_name, program_course_name, program_course_fee,
                    reason, reason_details, action_taken, fee_amendment, amendment_type,
                    start_date, ending_date, paid_fees, balance, remarks, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->bind_param("sissssssdsssddssi",
                    $advice_number, $student_id, $student_name, $program_course_name, $program_course_fee,
                    $reason, $reason_details, $action_taken, $fee_amendment, $amendment_type,
                    $start_date, $ending_date, $paid_fees, $balance, $remarks, $created_by
                );
                
                if ($stmt->execute()) {
                    $advice_id = $conn->insert_id;
                    
                    // Auto-create revised Proforma Invoice if PI exists
                    $pi_result = $conn->query("SELECT pi_id FROM proforma_invoices WHERE student_id = $student_id ORDER BY pi_id DESC LIMIT 1");
                    if ($pi_result && $pi_result->num_rows > 0) {
                        $pi = $pi_result->fetch_assoc();
                        $revised_pi_number = generatePINumber();
                        $conn->query("UPDATE proforma_invoices SET 
                            revised_pi_number = '$revised_pi_number',
                            revised_date = CURDATE(),
                            amendment_amount = $fee_amendment,
                            amendment_reason = 'withdrawal',
                            balance = balance + $fee_amendment
                            WHERE pi_id = {$pi['pi_id']}");
                    }
                    
                    // If created by Student Admin Service, notify Finance
                    if ($_SESSION['role'] === 'studentservices' || $_SESSION['role'] === 'admin') {
                        $notification_title = "📋 Withdrawal Advice from Student Admin Service";
                        $notification_message = "A new Withdrawal Advice has been issued:\n\nAdvice Number: {$advice_number}\nStudent: {$student_name}\nReason: " . ucfirst($reason) . "\nAction Taken: " . ucfirst(str_replace('_', ' ', $action_taken)) . "\nFee Amendment: PGK " . number_format($fee_amendment, 2) . " (" . ucfirst($amendment_type) . ")\n\nPlease review and update financial records accordingly.";
                        
                        createWorkflowNotification(
                            0, // Not application-related
                            'studentservices',
                            'finance',
                            $notification_title,
                            $notification_message,
                            'action_required',
                            'withdrawal_advice.php',
                            $_SESSION['user_id']
                        );
                    }
                    
                    $message = "Withdrawal Advice created successfully! Advice Number: $advice_number" . 
                        ($_SESSION['role'] === 'studentservices' || $_SESSION['role'] === 'admin' ? "<br><br>✅ Finance has been notified." : "");
                    $message_type = "success";
                } else {
                    $message = "Error creating Withdrawal Advice: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        }
    }
}

// Get data
if ($conn) {
    $tables_exist = $conn->query("SHOW TABLES LIKE 'withdrawal_advice'")->num_rows > 0;
    
    if ($tables_exist) {
        $result = $conn->query("SELECT * FROM withdrawal_advice ORDER BY created_at DESC LIMIT 100");
        if ($result) {
            $withdrawal_advices = $result->fetch_all(MYSQLI_ASSOC);
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
  <title>Withdrawal Advice - Student Admin Services</title>
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
        <a class="menu-item active" href="withdrawal_advice.php">Withdrawal Advice</a>
        <a class="menu-item" href="disciplinary_advice.php">Disciplinary Advice</a>
        <a class="menu-item" href="student_schedules.php">Student Schedules</a>
      </div>
    </nav>

    <div class="content">
      <header style="margin-bottom: 30px;">
        <h1>Withdrawal Advice</h1>
        <p class="small">
          <?php if ($_SESSION['role'] === 'studentservices' || $_SESSION['role'] === 'admin'): ?>
            Issue withdrawal advice for students and track fee amendments. Finance will be notified when advice is created.
          <?php else: ?>
            View withdrawal advice issued by Student Admin Service. These are sent to Finance for review and financial record updates.
          <?php endif; ?>
        </p>
      </header>

      <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 5px; background: <?php echo $message_type === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $message_type === 'success' ? '#155724' : '#721c24'; ?>;">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <div class="main-card" style="margin-bottom: 30px;">
        <h2>Create Withdrawal Advice</h2>
        <form method="POST">
          <div class="form-grid">
            <div class="form-group">
              <label>Student *</label>
              <select name="student_id" id="student_select" required onchange="loadStudentInfo()">
                <option value="">Select Student</option>
                <?php foreach ($students as $student): ?>
                  <option value="<?php echo $student['student_id']; ?>" 
                          data-program="<?php echo htmlspecialchars($student['program_name'] ?? ''); ?>">
                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_number'] . ')'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Program/Course Name *</label>
              <input type="text" name="program_course_name" id="program_course_name" required>
            </div>
            <div class="form-group">
              <label>Program/Course Fee (PGK) *</label>
              <input type="number" name="program_course_fee" step="0.01" required placeholder="0.00">
            </div>
            <div class="form-group">
              <label>Reason for Withdrawal *</label>
              <select name="reason" required>
                <option value="disciplinary">Disciplinary</option>
                <option value="incomplete_fees">Incomplete Fees</option>
                <option value="sickness">Sickness</option>
                <option value="death">Death</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="form-group full-width">
              <label>Reason Details</label>
              <textarea name="reason_details" rows="3" placeholder="Provide details about the withdrawal..."></textarea>
            </div>
            <div class="form-group">
              <label>Action Taken *</label>
              <select name="action_taken" required>
                <option value="returning">Returning</option>
                <option value="charged">Charged</option>
                <option value="black_list">Black-List</option>
                <option value="none">None</option>
              </select>
            </div>
            <div class="form-group">
              <label>Fee Amendment Amount (PGK)</label>
              <input type="number" name="fee_amendment" step="0.01" value="0" placeholder="0.00">
            </div>
            <div class="form-group">
              <label>Amendment Type</label>
              <select name="amendment_type">
                <option value="reduction">Reduction</option>
                <option value="addition">Addition</option>
              </select>
            </div>
            <div class="form-group">
              <label>Start Date *</label>
              <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
              <label>Ending Date</label>
              <input type="date" name="ending_date">
            </div>
            <div class="form-group">
              <label>Paid Fees (PGK)</label>
              <input type="number" name="paid_fees" step="0.01" value="0" placeholder="0.00">
            </div>
            <div class="form-group">
              <label>Remarks</label>
              <textarea name="remarks" rows="3" placeholder="Additional remarks..."></textarea>
            </div>
          </div>
          <button type="submit" name="create_advice" class="btn btn-primary">Create Withdrawal Advice</button>
        </form>
      </div>

      <div class="main-card">
        <h2>Withdrawal Advices</h2>
        <?php if (!empty($withdrawal_advices)): ?>
          <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
              <tr style="background: #1d4e89; color: white;">
                <th style="padding: 12px; text-align: left;">Advice Number</th>
                <th style="padding: 12px; text-align: left;">Student Name</th>
                <th style="padding: 12px; text-align: left;">Program/Course</th>
                <th style="padding: 12px; text-align: left;">Reason</th>
                <th style="padding: 12px; text-align: left;">Action Taken</th>
                <th style="padding: 12px; text-align: right;">Balance</th>
                <th style="padding: 12px; text-align: left;">Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($withdrawal_advices as $advice): ?>
                <tr style="border-bottom: 1px solid #ddd;">
                  <td style="padding: 12px;"><strong><?php echo htmlspecialchars($advice['advice_number']); ?></strong></td>
                  <td style="padding: 12px;"><?php echo htmlspecialchars($advice['student_name']); ?></td>
                  <td style="padding: 12px;"><?php echo htmlspecialchars($advice['program_course_name']); ?></td>
                  <td style="padding: 12px;"><?php echo ucfirst(str_replace('_', ' ', $advice['reason'])); ?></td>
                  <td style="padding: 12px;"><?php echo ucfirst(str_replace('_', ' ', $advice['action_taken'])); ?></td>
                  <td style="padding: 12px; text-align: right; font-weight: bold;">PGK <?php echo number_format($advice['balance'], 2); ?></td>
                  <td style="padding: 12px;"><?php echo date('M d, Y', strtotime($advice['created_at'])); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p style="color: #666; margin-top: 20px;">No withdrawal advices found.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    function loadStudentInfo() {
      const select = document.getElementById('student_select');
      const selectedOption = select.options[select.selectedIndex];
      const program = selectedOption.getAttribute('data-program');
      
      if (program) {
        document.getElementById('program_course_name').value = program;
      }
    }
  </script>
</body>
</html>

