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
    if (isset($_POST['create_trigger'])) {
        $trigger_name = $_POST['trigger_name'] ?? '';
        $trigger_point = $_POST['trigger_point'] ?? '';
        $plan_id = intval($_POST['plan_id']);
        $days_before = intval($_POST['days_before'] ?? 0);
        $days_after = intval($_POST['days_after'] ?? 0);
        $red_day = intval($_POST['red_day'] ?? 0);
        $green_day = intval($_POST['green_day'] ?? 0);
        
        if ($trigger_name && $trigger_point && $plan_id) {
            $stmt = $conn->prepare("INSERT INTO automated_triggers (trigger_name, trigger_point, plan_id, days_before, days_after, red_day, green_day) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiiiii", $trigger_name, $trigger_point, $plan_id, $days_before, $days_after, $red_day, $green_day);
            if ($stmt->execute()) {
                $message = "Automated trigger created successfully!";
                $message_type = "success";
            } else {
                $message = "Error creating trigger: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['update_trigger'])) {
        $trigger_id = intval($_POST['trigger_id']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE automated_triggers SET is_active = ? WHERE trigger_id = ?");
        $stmt->bind_param("ii", $is_active, $trigger_id);
        if ($stmt->execute()) {
            $message = "Trigger updated successfully!";
            $message_type = "success";
        }
        $stmt->close();
    }
}

// Get data
$triggers = [];
$fee_plans = [];

if ($conn) {
    $tables_exist = $conn->query("SHOW TABLES LIKE 'automated_triggers'")->num_rows > 0;
    
    if ($tables_exist) {
        // Get triggers
        $result = $conn->query("SELECT at.*, fp.plan_name FROM automated_triggers at 
            JOIN fee_plans fp ON at.plan_id = fp.plan_id 
            ORDER BY at.trigger_point, at.trigger_name");
        if ($result) {
            $triggers = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        // Get fee plans
        $result = $conn->query("SELECT * FROM fee_plans WHERE is_active = TRUE ORDER BY plan_name");
        if ($result) {
            $fee_plans = $result->fetch_all(MYSQLI_ASSOC);
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
  <title>Automated Invoice Triggers - Finance</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <link rel="stylesheet" href="../css/responsive.css">
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
    .form-group select {
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
    .trigger-card {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 5px;
      margin-bottom: 15px;
      border-left: 4px solid #1d4e89;
    }
    .badge {
      padding: 4px 8px;
      border-radius: 3px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    .badge-red {
      background: #dc3545;
      color: white;
    }
    .badge-green {
      background: #28a745;
      color: white;
    }
    .badge-inactive {
      background: #6c757d;
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
        <a class="menu-item active" href="automated_triggers.php">Automated Triggers</a>
        <a class="menu-item" href="payment_reminders.php">Payment Reminders</a>
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
        <h1>Automated Invoice Triggers</h1>
        <p class="small">Establish automated invoice trigger points (RED/GREEN DAYS) at each student life-cycle stage.</p>
      </header>

      <div class="info-box">
        <strong>📋 About Automated Triggers:</strong>
        <ul style="margin: 10px 0 0 20px; padding: 0;">
          <li><strong>Trigger Points:</strong> Define when invoices should be generated (admission, enrollment, semester start/end, exam registration, graduation)</li>
          <li><strong>RED DAY:</strong> Days before due date for critical alert (e.g., 7 days before due)</li>
          <li><strong>GREEN DAY:</strong> Days before due date for early notification (e.g., 30 days before due)</li>
          <li><strong>Days Before/After:</strong> Control when invoice is generated relative to trigger point</li>
        </ul>
      </div>

      <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 5px; background: <?php echo $message_type === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $message_type === 'success' ? '#155724' : '#721c24'; ?>;">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <div class="main-card" style="margin-bottom: 30px;">
        <h2>Create New Trigger</h2>
        <form method="POST">
          <div class="form-grid">
            <div class="form-group">
              <label>Trigger Name *</label>
              <input type="text" name="trigger_name" required placeholder="e.g., Admission Invoice Trigger">
            </div>
            <div class="form-group">
              <label>Trigger Point *</label>
              <select name="trigger_point" required>
                <option value="">Select Trigger Point</option>
                <option value="admission">Admission</option>
                <option value="enrollment">Enrollment</option>
                <option value="semester_start">Semester Start</option>
                <option value="semester_end">Semester End</option>
                <option value="exam_registration">Exam Registration</option>
                <option value="graduation">Graduation</option>
                <option value="custom">Custom</option>
              </select>
            </div>
            <div class="form-group">
              <label>Fee Plan *</label>
              <select name="plan_id" required>
                <option value="">Select Fee Plan</option>
                <?php foreach ($fee_plans as $plan): ?>
                  <option value="<?php echo $plan['plan_id']; ?>"><?php echo htmlspecialchars($plan['plan_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Days Before Trigger Point</label>
              <input type="number" name="days_before" value="0" min="0" placeholder="Generate invoice X days before">
            </div>
            <div class="form-group">
              <label>Days After Trigger Point</label>
              <input type="number" name="days_after" value="0" min="0" placeholder="Generate invoice X days after">
            </div>
            <div class="form-group">
              <label>RED DAY (Critical Alert)</label>
              <input type="number" name="red_day" value="7" min="0" placeholder="Days before due date">
            </div>
            <div class="form-group">
              <label>GREEN DAY (Early Notification)</label>
              <input type="number" name="green_day" value="30" min="0" placeholder="Days before due date">
            </div>
          </div>
          <button type="submit" name="create_trigger" class="btn btn-primary">Create Trigger</button>
        </form>
      </div>

      <div class="main-card">
        <h2>Existing Triggers</h2>
        <?php if (!empty($triggers)): ?>
          <?php foreach ($triggers as $trigger): ?>
            <div class="trigger-card">
              <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                <div>
                  <h3 style="margin: 0 0 5px 0; color: #1d4e89;"><?php echo htmlspecialchars($trigger['trigger_name']); ?></h3>
                  <p style="margin: 0; color: #666; font-size: 0.9rem;">
                    <strong>Plan:</strong> <?php echo htmlspecialchars($trigger['plan_name']); ?> | 
                    <strong>Trigger Point:</strong> <?php echo ucfirst(str_replace('_', ' ', $trigger['trigger_point'])); ?>
                  </p>
                </div>
                <form method="POST" style="display: inline-block;">
                  <input type="hidden" name="trigger_id" value="<?php echo $trigger['trigger_id']; ?>">
                  <input type="hidden" name="is_active" value="<?php echo $trigger['is_active'] ? '0' : '1'; ?>">
                  <button type="submit" name="update_trigger" class="btn" style="background: <?php echo $trigger['is_active'] ? '#dc3545' : '#28a745'; ?>; color: white;">
                    <?php echo $trigger['is_active'] ? 'Deactivate' : 'Activate'; ?>
                  </button>
                </form>
              </div>
              <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">
                <div>
                  <strong>Days Before:</strong> <?php echo $trigger['days_before']; ?>
                </div>
                <div>
                  <strong>Days After:</strong> <?php echo $trigger['days_after']; ?>
                </div>
                <div>
                  <span class="badge badge-red">RED DAY: <?php echo $trigger['red_day']; ?> days</span>
                </div>
                <div>
                  <span class="badge badge-green">GREEN DAY: <?php echo $trigger['green_day']; ?> days</span>
                </div>
                <div>
                  <span class="badge <?php echo $trigger['is_active'] ? 'badge-green' : 'badge-inactive'; ?>">
                    <?php echo $trigger['is_active'] ? 'Active' : 'Inactive'; ?>
                  </span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color: #666;">No automated triggers configured yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>

