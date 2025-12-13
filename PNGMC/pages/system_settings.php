<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $conn = getDBConnection();
    if ($conn) {
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?");
            $stmt->bind_param("ssisi", $key, $value, $_SESSION['user_id'], $value, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
        }
        $message = "Settings updated successfully!";
        $message_type = "success";
        $conn->close();
    }
}

// Get all settings
$conn = getDBConnection();
$settings = [];
if ($conn) {
    $result = $conn->query("SELECT setting_key, setting_value, description FROM system_settings");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row;
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
  <title>System Settings - Admin</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <style>
    .message { padding: 12px; margin: 10px 0; border-radius: 5px; }
    .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .form-section { background: var(--card-bg); padding: 20px; border-radius: 10px; margin-bottom: 20px; }
    .form-row { display: grid; grid-template-columns: 1fr 2fr; gap: 15px; margin-bottom: 15px; align-items: center; }
    label { font-weight: 600; color: var(--primary); }
    input, select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
    .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; background: var(--primary); color: white; }
    .description { font-size: 0.85rem; color: #666; margin-top: 5px; }
  </style>
</head>
<body>
    <div class="dashboard-wrap container">
    <nav class="sidebar" aria-label="Main navigation">
      <div class="brand">
        <a href="admin_dashboard.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
          <img src="../images/pnmc.png" alt="logo"> 
          <strong>PNGMC</strong>
        </a>
      </div>
      <div class="menu">
        <a class="menu-item" href="admin_dashboard.php">Dashboard</a>
        <a class="menu-item" href="manage_staff.php">Manage Staff</a>
        <a class="menu-item active" href="system_settings.php">System Settings</a>
        <a class="menu-item" href="reports.php">Reports</a>
      </div>
    </nav>

    <div class="content">
      <div class="main-card">
        <h1>System Settings</h1>
        
        <?php if ($message): ?>
          <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST">
          <div class="form-section">
            <h2>General Settings</h2>
            
            <div class="form-row">
              <label>College Name</label>
              <div>
                <input type="text" name="settings[college_name]" value="<?php echo htmlspecialchars($settings['college_name']['setting_value'] ?? 'PNG Maritime College'); ?>">
                <div class="description"><?php echo htmlspecialchars($settings['college_name']['description'] ?? 'Name of the institution'); ?></div>
              </div>
            </div>
            
            <div class="form-row">
              <label>Academic Year</label>
              <div>
                <input type="text" name="settings[academic_year]" value="<?php echo htmlspecialchars($settings['academic_year']['setting_value'] ?? '2025'); ?>">
                <div class="description">Current academic year</div>
              </div>
            </div>
            
            <div class="form-row">
              <label>Semester</label>
              <div>
                <input type="text" name="settings[semester]" value="<?php echo htmlspecialchars($settings['semester']['setting_value'] ?? '1'); ?>">
                <div class="description">Current semester</div>
              </div>
            </div>
            
            <div class="form-row">
              <label>Currency</label>
              <div>
                <input type="text" name="settings[currency]" value="<?php echo htmlspecialchars($settings['currency']['setting_value'] ?? 'PGK'); ?>">
                <div class="description">Currency code (e.g., PGK, USD)</div>
              </div>
            </div>
          </div>

          <div class="form-section">
            <h2>Invoice & Payment Settings</h2>
            
            <div class="form-row">
              <label>Invoice Prefix</label>
              <div>
                <input type="text" name="settings[invoice_prefix]" value="<?php echo htmlspecialchars($settings['invoice_prefix']['setting_value'] ?? 'INV-'); ?>">
                <div class="description">Prefix for invoice numbers</div>
              </div>
            </div>
            
            <div class="form-row">
              <label>Payment Prefix</label>
              <div>
                <input type="text" name="settings[payment_prefix]" value="<?php echo htmlspecialchars($settings['payment_prefix']['setting_value'] ?? 'PAY-'); ?>">
                <div class="description">Prefix for payment numbers</div>
              </div>
            </div>
            
            <div class="form-row">
              <label>Ticket Prefix</label>
              <div>
                <input type="text" name="settings[ticket_prefix]" value="<?php echo htmlspecialchars($settings['ticket_prefix']['setting_value'] ?? 'TKT-'); ?>">
                <div class="description">Prefix for support ticket numbers</div>
              </div>
            </div>
          </div>

          <button type="submit" name="update_settings" class="btn">Save Settings</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>

