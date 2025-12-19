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

// Check if fee management tables exist
$tables_exist = false;
if ($conn) {
    $tables_exist = $conn->query("SHOW TABLES LIKE 'fee_plans'")->num_rows > 0;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tables_exist) {
    if (isset($_POST['create_plan'])) {
        // Create new fee plan
        $plan_name = $_POST['plan_name'] ?? '';
        $plan_type = $_POST['plan_type'] ?? '';
        $description = $_POST['description'] ?? '';
        
        if ($plan_name && $plan_type) {
            $stmt = $conn->prepare("INSERT INTO fee_plans (plan_name, plan_type, description) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $plan_name, $plan_type, $description);
            if ($stmt->execute()) {
                $plan_id = $conn->insert_id;
                $message = "Fee plan created successfully! Plan ID: $plan_id";
                $message_type = "success";
            } else {
                $message = "Error creating fee plan: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['add_item'])) {
        // Add fee item to plan
        $plan_id = intval($_POST['plan_id']);
        $item_name = $_POST['item_name'] ?? '';
        $item_type = $_POST['item_type'] ?? '';
        $amount = floatval($_POST['amount']);
        $is_percentage = isset($_POST['is_percentage']) ? 1 : 0;
        $applies_to = $_POST['applies_to'] ?? 'all';
        $program_id = !empty($_POST['program_id']) ? intval($_POST['program_id']) : null;
        $due_date_offset = intval($_POST['due_date_offset'] ?? 0);
        
        if ($plan_id && $item_name && $item_type) {
            $stmt = $conn->prepare("INSERT INTO fee_items (plan_id, item_name, item_type, amount, is_percentage, applies_to, program_id, due_date_offset) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issdssii", $plan_id, $item_name, $item_type, $amount, $is_percentage, $applies_to, $program_id, $due_date_offset);
            if ($stmt->execute()) {
                $message = "Fee item added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding fee item: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['link_program'])) {
        // Link fee plan to program
        $program_id = intval($_POST['program_id']);
        $plan_id = intval($_POST['plan_id']);
        $batch_year = !empty($_POST['batch_year']) ? intval($_POST['batch_year']) : null;
        
        if ($program_id && $plan_id) {
            $stmt = $conn->prepare("INSERT INTO program_fee_plans (program_id, plan_id, batch_year) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE is_active = TRUE");
            $stmt->bind_param("iii", $program_id, $plan_id, $batch_year);
            if ($stmt->execute()) {
                $message = "Fee plan linked to program successfully!";
                $message_type = "success";
            } else {
                $message = "Error linking fee plan: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}

// Get all fee plans
$fee_plans = [];
$fee_items = [];
$programs = [];
$linked_plans = [];

if ($conn && $tables_exist) {
        // Get fee plans
        $result = $conn->query("SELECT * FROM fee_plans ORDER BY plan_type, plan_name");
        if ($result) {
            $fee_plans = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        // Get fee items for selected plan
        if (isset($_GET['plan_id'])) {
            $plan_id = intval($_GET['plan_id']);
            $result = $conn->query("SELECT * FROM fee_items WHERE plan_id = $plan_id ORDER BY sort_order, item_name");
            if ($result) {
                $fee_items = $result->fetch_all(MYSQLI_ASSOC);
            }
        }
        
        // Get programs
        $result = $conn->query("SELECT * FROM programs ORDER BY program_name");
        if ($result) {
            $programs = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        // Get linked plans
        $result = $conn->query("SELECT pfp.*, p.program_name, fp.plan_name FROM program_fee_plans pfp 
            JOIN programs p ON pfp.program_id = p.program_id 
            JOIN fee_plans fp ON pfp.plan_id = fp.plan_id 
            WHERE pfp.is_active = TRUE 
            ORDER BY p.program_name, pfp.batch_year");
        if ($result) {
            $linked_plans = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        $conn->close();
    } elseif ($conn) {
        // Get programs even if fee tables don't exist (for display purposes)
        $result = $conn->query("SELECT * FROM programs ORDER BY program_name");
        if ($result) {
            $programs = $result->fetch_all(MYSQLI_ASSOC);
        }
        $conn->close();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fee Management Setup - Finance</title>
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
    .btn-primary:hover {
      background: #163c6a;
    }
    .items-list {
      margin-top: 20px;
    }
    .item-card {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
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
        <a class="menu-item active" href="fee_management.php">Fee Plans Setup</a>
        <a class="menu-item" href="automated_triggers.php">Automated Triggers</a>
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
        <h1>Fee Management Setup</h1>
        <p class="small">Customize fee plans for various functions: admission, programs, services, exams, lodging, etc.</p>
      </header>

      <?php if (!$tables_exist): ?>
        <div class="main-card" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; margin-bottom: 20px; border-radius: 5px;">
          <h3 style="margin: 0 0 10px 0; color: #856404;">⚠️ Fee Management Tables Not Found</h3>
          <p style="margin: 0 0 15px 0; color: #856404;">
            The fee management database tables have not been created yet. Please run the database setup script to create the required tables.
          </p>
          <a href="../database/create_fee_management_tables.php" style="display: inline-block; padding: 10px 20px; background: #1d4e89; color: white; text-decoration: none; border-radius: 5px; font-weight: 600;">
            Create Fee Management Tables →
          </a>
        </div>
      <?php endif; ?>

      <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 5px; background: <?php echo $message_type === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $message_type === 'success' ? '#155724' : '#721c24'; ?>;">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <div class="tabs">
        <button class="tab active" onclick="showTab('plans')">Fee Plans</button>
        <button class="tab" onclick="showTab('items')">Fee Items</button>
        <button class="tab" onclick="showTab('link')">Link to Programs</button>
        <button class="tab" onclick="showTab('view')">View Linked Plans</button>
      </div>

      <!-- Fee Plans Tab -->
      <div id="plans" class="tab-content active">
        <div class="main-card">
          <h2>Create Fee Plan</h2>
          <?php if (!$tables_exist): ?>
            <p style="color: #666; padding: 20px; background: #f8f9fa; border-radius: 5px;">
              Please create the fee management tables first using the link above.
            </p>
          <?php else: ?>
          <form method="POST">
            <div class="form-grid">
              <div class="form-group">
                <label>Plan Name *</label>
                <input type="text" name="plan_name" required placeholder="e.g., Admission Fee Plan 2025">
              </div>
              <div class="form-group">
                <label>Plan Type *</label>
                <select name="plan_type" required>
                  <option value="">Select Type</option>
                  <option value="admission">Admission</option>
                  <option value="program">Program</option>
                  <option value="service">Service</option>
                  <option value="exam">Exam</option>
                  <option value="lodging">Lodging</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div class="form-group full-width">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Describe this fee plan..."></textarea>
              </div>
            </div>
            <button type="submit" name="create_plan" class="btn btn-primary">Create Fee Plan</button>
          </form>
          <?php endif; ?>

          <div style="margin-top: 40px;">
            <h3>Existing Fee Plans</h3>
            <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
              <?php foreach ($fee_plans as $plan): ?>
                <div class="main-card">
                  <h4 style="margin: 0 0 10px 0; color: #1d4e89;"><?php echo htmlspecialchars($plan['plan_name']); ?></h4>
                  <p style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem;">
                    <strong>Type:</strong> <?php echo ucfirst($plan['plan_type']); ?><br>
                    <strong>Status:</strong> <?php echo $plan['is_active'] ? 'Active' : 'Inactive'; ?>
                  </p>
                  <p style="margin: 0 0 15px 0; color: #555; font-size: 0.85rem;"><?php echo htmlspecialchars($plan['description'] ?? 'No description'); ?></p>
                  <a href="fee_management.php?plan_id=<?php echo $plan['plan_id']; ?>&tab=items" class="btn btn-primary" style="text-decoration: none; display: inline-block;">Manage Items</a>
                </div>
              <?php endforeach; ?>
              <?php if (empty($fee_plans)): ?>
                <p style="color: #666;">No fee plans created yet.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Fee Items Tab -->
      <div id="items" class="tab-content">
        <div class="main-card">
          <h2>Add Fee Item</h2>
          <?php if (!$tables_exist): ?>
            <p style="color: #666; padding: 20px; background: #f8f9fa; border-radius: 5px;">
              Please create the fee management tables first.
            </p>
          <?php else: ?>
          <form method="POST">
            <div class="form-grid">
              <div class="form-group">
                <label>Fee Plan *</label>
                <select name="plan_id" required>
                  <option value="">Select Plan</option>
                  <?php foreach ($fee_plans as $plan): ?>
                    <option value="<?php echo $plan['plan_id']; ?>" <?php echo (isset($_GET['plan_id']) && $_GET['plan_id'] == $plan['plan_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($plan['plan_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Item Name *</label>
                <input type="text" name="item_name" required placeholder="e.g., Tuition Fee, Admission Fee">
              </div>
              <div class="form-group">
                <label>Item Type *</label>
                <select name="item_type" required>
                  <option value="">Select Type</option>
                  <option value="tuition">Tuition</option>
                  <option value="admission_fee">Admission Fee</option>
                  <option value="admin_fee">Admin Fee</option>
                  <option value="exam_fee">Exam Fee</option>
                  <option value="lodging_fee">Lodging Fee</option>
                  <option value="service_fee">Service Fee</option>
                  <option value="penalty">Late Fee Penalty</option>
                  <option value="discount">Discount</option>
                  <option value="scholarship">Scholarship</option>
                  <option value="sponsorship">Sponsorship</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div class="form-group">
                <label>Amount *</label>
                <input type="number" name="amount" step="0.01" required placeholder="0.00">
              </div>
              <div class="form-group">
                <label>
                  <input type="checkbox" name="is_percentage" value="1"> Is Percentage?
                </label>
              </div>
              <div class="form-group">
                <label>Applies To</label>
                <select name="applies_to">
                  <option value="all">All</option>
                  <option value="program">Specific Program</option>
                  <option value="batch">Specific Batch</option>
                  <option value="student">Specific Student</option>
                </select>
              </div>
              <div class="form-group">
                <label>Program (if applicable)</label>
                <select name="program_id">
                  <option value="">Select Program</option>
                  <?php foreach ($programs as $program): ?>
                    <option value="<?php echo $program['program_id']; ?>"><?php echo htmlspecialchars($program['program_name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Due Date Offset (days)</label>
                <input type="number" name="due_date_offset" value="0" placeholder="Days from trigger point">
              </div>
            </div>
            <button type="submit" name="add_item" class="btn btn-primary">Add Fee Item</button>
          </form>
          <?php endif; ?>

          <?php if (!empty($fee_items)): ?>
          <div class="items-list">
            <h3>Fee Items in Selected Plan</h3>
            <?php foreach ($fee_items as $item): ?>
              <div class="item-card">
                <div>
                  <strong><?php echo htmlspecialchars($item['item_name']); ?></strong><br>
                  <small style="color: #666;">
                    Type: <?php echo ucfirst(str_replace('_', ' ', $item['item_type'])); ?> | 
                    Amount: PGK <?php echo number_format($item['amount'], 2); ?>
                    <?php if ($item['is_percentage']): ?> (Percentage)<?php endif; ?>
                  </small>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Link to Programs Tab -->
      <div id="link" class="tab-content">
        <div class="main-card">
          <h2>Link Fee Plan to Program</h2>
          <?php if (!$tables_exist): ?>
            <p style="color: #666; padding: 20px; background: #f8f9fa; border-radius: 5px;">
              Please create the fee management tables first.
            </p>
          <?php else: ?>
          <form method="POST">
            <div class="form-grid">
              <div class="form-group">
                <label>Program *</label>
                <select name="program_id" required>
                  <option value="">Select Program</option>
                  <?php foreach ($programs as $program): ?>
                    <option value="<?php echo $program['program_id']; ?>"><?php echo htmlspecialchars($program['program_name']); ?></option>
                  <?php endforeach; ?>
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
                <label>Batch Year (Optional)</label>
                <input type="number" name="batch_year" min="2020" max="2100" placeholder="e.g., 2025">
              </div>
            </div>
            <button type="submit" name="link_program" class="btn btn-primary">Link Fee Plan</button>
          </form>
          <?php endif; ?>
        </div>
      </div>

      <!-- View Linked Plans Tab -->
      <div id="view" class="tab-content">
        <div class="main-card">
          <h2>Linked Fee Plans</h2>
          <?php if (!empty($linked_plans)): ?>
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
              <thead>
                <tr style="background: #1d4e89; color: white;">
                  <th style="padding: 12px; text-align: left;">Program</th>
                  <th style="padding: 12px; text-align: left;">Fee Plan</th>
                  <th style="padding: 12px; text-align: left;">Batch Year</th>
                  <th style="padding: 12px; text-align: left;">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($linked_plans as $link): ?>
                  <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 12px;"><?php echo htmlspecialchars($link['program_name']); ?></td>
                    <td style="padding: 12px;"><?php echo htmlspecialchars($link['plan_name']); ?></td>
                    <td style="padding: 12px;"><?php echo $link['batch_year'] ?? 'All Batches'; ?></td>
                    <td style="padding: 12px;">
                      <span style="padding: 4px 8px; border-radius: 3px; background: #28a745; color: white; font-size: 0.85rem;">
                        Active
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p style="color: #666; margin-top: 20px;">No fee plans linked to programs yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script>
    function showTab(tabName) {
      // Hide all tabs
      document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
      });
      document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
      });
      
      // Show selected tab
      document.getElementById(tabName).classList.add('active');
      event.target.classList.add('active');
    }

    // Check if tab parameter is in URL
    <?php if (isset($_GET['tab'])): ?>
      window.addEventListener('DOMContentLoaded', function() {
        showTab('<?php echo htmlspecialchars($_GET['tab']); ?>');
      });
    <?php endif; ?>
  </script>
</body>
</html>

