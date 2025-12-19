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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_reminder'])) {
        $student_id = intval($_POST['student_id']);
        $fee_id = intval($_POST['fee_id']);
        $reminder_type = $_POST['reminder_type'] ?? 'email';
        $reminder_date = $_POST['reminder_date'] ?? date('Y-m-d');
        $message_text = $_POST['message'] ?? '';
        
        // Get student email/phone
        $student_result = $conn->query("SELECT email, phone FROM students WHERE student_id = $student_id");
        $student = $student_result->fetch_assoc();
        
        if ($student_id && $fee_id) {
            $recipient_email = ($reminder_type === 'email' || $reminder_type === 'system_notification') ? $student['email'] : null;
            $recipient_phone = ($reminder_type === 'sms') ? $student['phone'] : null;
            
            $stmt = $conn->prepare("INSERT INTO payment_reminders (student_id, fee_id, reminder_type, reminder_date, message, recipient_email, recipient_phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("iisssss", $student_id, $fee_id, $reminder_type, $reminder_date, $message_text, $recipient_email, $recipient_phone);
            if ($stmt->execute()) {
                $message = "Payment reminder scheduled successfully!";
                $message_type = "success";
            } else {
                $message = "Error scheduling reminder: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['mark_sent'])) {
        $reminder_id = intval($_POST['reminder_id']);
        $stmt = $conn->prepare("UPDATE payment_reminders SET status = 'sent', sent_at = NOW() WHERE reminder_id = ?");
        $stmt->bind_param("i", $reminder_id);
        $stmt->execute();
        $stmt->close();
        $message = "Reminder marked as sent!";
        $message_type = "success";
    }
}

// Get data
$pending_reminders = [];
$sent_reminders = [];
$students_with_fees = [];

if ($conn) {
    $tables_exist = $conn->query("SHOW TABLES LIKE 'payment_reminders'")->num_rows > 0;
    
    if ($tables_exist) {
        // Get pending reminders
        $result = $conn->query("SELECT pr.*, s.first_name, s.last_name, s.student_number, s.email, s.phone, 
            sf.description as fee_description, sf.outstanding_amount, sf.due_date
            FROM payment_reminders pr
            JOIN students s ON pr.student_id = s.student_id
            JOIN student_fees sf ON pr.fee_id = sf.fee_id
            WHERE pr.status = 'pending' AND pr.reminder_date <= CURDATE()
            ORDER BY pr.reminder_date ASC");
        if ($result) {
            $pending_reminders = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        // Get sent reminders (recent)
        $result = $conn->query("SELECT pr.*, s.first_name, s.last_name, s.student_number, 
            sf.description as fee_description, sf.outstanding_amount
            FROM payment_reminders pr
            JOIN students s ON pr.student_id = s.student_id
            JOIN student_fees sf ON pr.fee_id = sf.fee_id
            WHERE pr.status = 'sent'
            ORDER BY pr.sent_at DESC
            LIMIT 20");
        if ($result) {
            $sent_reminders = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        // Get students with outstanding fees
        $result = $conn->query("SELECT DISTINCT s.student_id, s.first_name, s.last_name, s.student_number, s.email, s.phone,
            SUM(sf.outstanding_amount) as total_outstanding
            FROM students s
            JOIN student_fees sf ON s.student_id = sf.student_id
            WHERE sf.outstanding_amount > 0 AND sf.status IN ('pending', 'partial', 'overdue')
            GROUP BY s.student_id
            ORDER BY total_outstanding DESC
            LIMIT 50");
        if ($result) {
            $students_with_fees = $result->fetch_all(MYSQLI_ASSOC);
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
  <title>Payment Reminders - Finance</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <style>
    .tabs {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      border-bottom: 2px solid #ddd;
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
    .reminder-card {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 10px;
      border-left: 4px solid #ffc107;
    }
    .reminder-card.sent {
      border-left-color: #28a745;
    }
    .badge {
      padding: 4px 8px;
      border-radius: 3px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    .badge-pending {
      background: #ffc107;
      color: #000;
    }
    .badge-sent {
      background: #28a745;
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
        <a class="menu-item active" href="payment_reminders.php">Payment Reminders</a>
        <div class="menu-section">Billing & Invoices</div>
        <a class="menu-item" href="billing.php">Billing</a>
        <a class="menu-item" href="invoices.php">Invoices</a>
        <a class="menu-item" href="student_fees.php">Student Fees</a>
        <div class="menu-section">Reports</div>
        <a class="menu-item" href="financial_reports.php">Financial Reports</a>
        <a class="menu-item" href="fee_reports.php">Fee Reports & Analysis</a>
        <a class="menu-item" href="workflow_manager.php">Workflow Manager</a>
      </div>
    </nav>

    <div class="content">
      <header style="margin-bottom: 30px;">
        <h1>Payment Reminders</h1>
        <p class="small">Send preplanned nudges to various stakeholders for dues via various media (email, SMS, letter, system notification).</p>
      </header>

      <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 5px; background: <?php echo $message_type === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $message_type === 'success' ? '#155724' : '#721c24'; ?>;">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <div class="tabs">
        <button class="tab active" onclick="showTab('pending')">Pending Reminders (<?php echo count($pending_reminders); ?>)</button>
        <button class="tab" onclick="showTab('create')">Create Reminder</button>
        <button class="tab" onclick="showTab('sent')">Sent Reminders</button>
      </div>

      <!-- Pending Reminders Tab -->
      <div id="pending" class="tab-content active">
        <div class="main-card">
          <h2>Pending Reminders</h2>
          <?php if (!empty($pending_reminders)): ?>
            <?php foreach ($pending_reminders as $reminder): ?>
              <div class="reminder-card">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                  <div style="flex: 1;">
                    <h3 style="margin: 0 0 10px 0; color: #1d4e89;">
                      <?php echo htmlspecialchars($reminder['first_name'] . ' ' . $reminder['last_name']); ?>
                      <small style="color: #666;">(<?php echo htmlspecialchars($reminder['student_number']); ?>)</small>
                    </h3>
                    <p style="margin: 5px 0; color: #666;">
                      <strong>Fee:</strong> <?php echo htmlspecialchars($reminder['fee_description']); ?><br>
                      <strong>Outstanding:</strong> PGK <?php echo number_format($reminder['outstanding_amount'], 2); ?><br>
                      <strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($reminder['due_date'])); ?><br>
                      <strong>Type:</strong> <?php echo ucfirst($reminder['reminder_type']); ?><br>
                      <strong>Scheduled Date:</strong> <?php echo date('M d, Y', strtotime($reminder['reminder_date'])); ?>
                    </p>
                    <?php if ($reminder['message']): ?>
                      <p style="margin: 10px 0 0 0; padding: 10px; background: white; border-radius: 5px; font-style: italic;">
                        "<?php echo htmlspecialchars($reminder['message']); ?>"
                      </p>
                    <?php endif; ?>
                  </div>
                  <form method="POST" style="margin-left: 15px;">
                    <input type="hidden" name="reminder_id" value="<?php echo $reminder['reminder_id']; ?>">
                    <button type="submit" name="mark_sent" class="btn btn-primary">Mark as Sent</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="color: #666;">No pending reminders at this time.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Create Reminder Tab -->
      <div id="create" class="tab-content">
        <div class="main-card">
          <h2>Create Payment Reminder</h2>
          <form method="POST">
            <div class="form-grid">
              <div class="form-group">
                <label>Student *</label>
                <select name="student_id" id="student_select" required onchange="loadStudentFees()">
                  <option value="">Select Student</option>
                  <?php foreach ($students_with_fees as $student): ?>
                    <option value="<?php echo $student['student_id']; ?>" 
                            data-email="<?php echo htmlspecialchars($student['email']); ?>"
                            data-phone="<?php echo htmlspecialchars($student['phone']); ?>">
                      <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_number'] . ') - PGK ' . number_format($student['total_outstanding'], 2)); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Fee *</label>
                <select name="fee_id" id="fee_select" required>
                  <option value="">Select student first</option>
                </select>
              </div>
              <div class="form-group">
                <label>Reminder Type *</label>
                <select name="reminder_type" required>
                  <option value="email">Email</option>
                  <option value="sms">SMS</option>
                  <option value="letter">Letter</option>
                  <option value="system_notification">System Notification</option>
                </select>
              </div>
              <div class="form-group">
                <label>Reminder Date *</label>
                <input type="date" name="reminder_date" value="<?php echo date('Y-m-d'); ?>" required>
              </div>
              <div class="form-group full-width">
                <label>Message</label>
                <textarea name="message" rows="4" placeholder="Custom message for the reminder..."></textarea>
              </div>
            </div>
            <button type="submit" name="send_reminder" class="btn btn-primary">Schedule Reminder</button>
          </form>
        </div>
      </div>

      <!-- Sent Reminders Tab -->
      <div id="sent" class="tab-content">
        <div class="main-card">
          <h2>Recently Sent Reminders</h2>
          <?php if (!empty($sent_reminders)): ?>
            <?php foreach ($sent_reminders as $reminder): ?>
              <div class="reminder-card sent">
                <div>
                  <h3 style="margin: 0 0 10px 0; color: #1d4e89;">
                    <?php echo htmlspecialchars($reminder['first_name'] . ' ' . $reminder['last_name']); ?>
                    <small style="color: #666;">(<?php echo htmlspecialchars($reminder['student_number']); ?>)</small>
                  </h3>
                  <p style="margin: 5px 0; color: #666;">
                    <strong>Fee:</strong> <?php echo htmlspecialchars($reminder['fee_description']); ?><br>
                    <strong>Type:</strong> <?php echo ucfirst($reminder['reminder_type']); ?><br>
                    <strong>Sent:</strong> <?php echo date('M d, Y H:i', strtotime($reminder['sent_at'])); ?>
                  </p>
                  <span class="badge badge-sent">Sent</span>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="color: #666;">No sent reminders to display.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script>
    function showTab(tabName) {
      document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
      });
      document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
      });
      
      document.getElementById(tabName).classList.add('active');
      event.target.classList.add('active');
    }

    function loadStudentFees() {
      const studentSelect = document.getElementById('student_select');
      const feeSelect = document.getElementById('fee_select');
      const studentId = studentSelect.value;
      
      feeSelect.innerHTML = '<option value="">Loading...</option>';
      
      if (studentId) {
        // Fetch fees for this student via AJAX or populate from server
        fetch(`get_student_fees.php?student_id=${studentId}`)
          .then(response => response.json())
          .then(data => {
            feeSelect.innerHTML = '<option value="">Select Fee</option>';
            data.forEach(fee => {
              const option = document.createElement('option');
              option.value = fee.fee_id;
              option.textContent = `${fee.description} - PGK ${parseFloat(fee.outstanding_amount).toFixed(2)} (Due: ${fee.due_date})`;
              feeSelect.appendChild(option);
            });
          })
          .catch(error => {
            feeSelect.innerHTML = '<option value="">Error loading fees</option>';
          });
      } else {
        feeSelect.innerHTML = '<option value="">Select student first</option>';
      }
    }
  </script>
</body>
</html>

