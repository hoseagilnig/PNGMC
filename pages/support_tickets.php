<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'studentservices') {
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
            $student_id = $_POST['student_id'] ?? null;
            $subject = trim($_POST['subject']);
            $description = trim($_POST['description']);
            $category = $_POST['category'] ?? 'other';
            $priority = $_POST['priority'] ?? 'medium';
            
            // Get ticket prefix
            $prefix_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'ticket_prefix'");
            $prefix = $prefix_result ? $prefix_result->fetch_assoc()['setting_value'] : 'TKT-';
            $ticket_number = $prefix . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $conn->prepare("INSERT INTO support_tickets (ticket_number, student_id, submitted_by, subject, description, category, priority, assigned_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siissssi", $ticket_number, $student_id, $_SESSION['user_id'], $subject, $description, $category, $priority, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $message = "Ticket created successfully! Ticket #: " . $ticket_number;
                $message_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } elseif (isset($_POST['action']) && $_POST['action'] === 'update') {
            $ticket_id = $_POST['ticket_id'];
            $status = $_POST['status'];
            $assigned_to = $_POST['assigned_to'] ?? null;
            $resolution_notes = trim($_POST['resolution_notes'] ?? '');
            
            $resolved_at = ($status === 'resolved' || $status === 'closed') ? date('Y-m-d H:i:s') : null;
            
            $stmt = $conn->prepare("UPDATE support_tickets SET status = ?, assigned_to = ?, resolution_notes = ?, resolved_at = ? WHERE ticket_id = ?");
            $stmt->bind_param("sissi", $status, $assigned_to, $resolution_notes, $resolved_at, $ticket_id);
            
            if ($stmt->execute()) {
                $message = "Ticket updated successfully!";
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

// Get data
$conn = getDBConnection();
$students = [];
$tickets = [];
$staff = [];
if ($conn) {
    $result = $conn->query("SELECT student_id, student_number, first_name, last_name FROM students WHERE status = 'active' ORDER BY last_name");
    if ($result) {
        $students = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    $result = $conn->query("SELECT t.*, s.student_number, s.first_name, s.last_name, u1.full_name as submitted_by_name, u2.full_name as assigned_to_name FROM support_tickets t LEFT JOIN students s ON t.student_id = s.student_id LEFT JOIN users u1 ON t.submitted_by = u1.user_id LEFT JOIN users u2 ON t.assigned_to = u2.user_id ORDER BY t.created_at DESC");
    if ($result) {
        $tickets = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    $result = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'studentservices' AND status = 'active'");
    if ($result) {
        $staff = $result->fetch_all(MYSQLI_ASSOC);
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Support Tickets - Student Services</title>
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
    .badge { padding: 4px 8px; border-radius: 3px; font-size: 0.85rem; }
    .badge-open { background: #17a2b8; color: white; }
    .badge-in_progress { background: #ffc107; color: #000; }
    .badge-resolved { background: #28a745; color: white; }
    .badge-closed { background: #6c757d; color: white; }
    .badge-urgent { background: #dc3545; color: white; }
    .badge-high { background: #fd7e14; color: white; }
    .badge-medium { background: #ffc107; color: #000; }
    .badge-low { background: #6c757d; color: white; }
    .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; background: var(--primary); color: white; }
  </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="student_service_dashboard.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                <img src="../images/pnmc.png" alt="PNG Maritime College Logo" class="logo-img">
                <span style="margin-left: 10px;">Student Services Dashboard</span>
            </a>
        </div>
        <div class="user-info">
            Logged in as <?php echo htmlspecialchars($_SESSION['name']); ?>
        </div>
    </header>

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
        <a class="menu-item" href="student_records.php">Student Records</a>
        <a class="menu-item" href="advising.php">Advising</a>
        <a class="menu-item active" href="support_tickets.php">Support Tickets</a>
      </div>
    </nav>

    <div class="content">
      <div class="main-card">
        <h1>Support Tickets</h1>
        
        <?php if ($message): ?>
          <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="form-section">
          <h2>Create New Ticket</h2>
          <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-row">
              <div>
                <label>Student (Optional)</label>
                <select name="student_id">
                  <option value="">Select Student (Optional)</option>
                  <?php foreach ($students as $student): ?>
                    <option value="<?php echo $student['student_id']; ?>">
                      <?php echo htmlspecialchars($student['student_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label>Category</label>
                <select name="category">
                  <option value="academic">Academic</option>
                  <option value="financial">Financial</option>
                  <option value="dormitory">Dormitory</option>
                  <option value="welfare">Welfare</option>
                  <option value="technical">Technical</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div>
                <label>Subject *</label>
                <input type="text" name="subject" required>
              </div>
              <div>
                <label>Priority</label>
                <select name="priority">
                  <option value="low">Low</option>
                  <option value="medium" selected>Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div>
                <label>Description *</label>
                <textarea name="description" rows="4" required></textarea>
              </div>
            </div>
            <button type="submit" class="btn">Create Ticket</button>
          </form>
        </div>

        <div class="form-section">
          <h2>All Tickets</h2>
          <table>
            <thead>
              <tr>
                <th>Ticket #</th>
                <th>Subject</th>
                <th>Student</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Assigned To</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($tickets)): ?>
                <tr><td colspan="9" style="text-align: center;">No tickets found.</td></tr>
              <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($ticket['ticket_number']); ?></td>
                    <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                    <td><?php echo htmlspecialchars(($ticket['first_name'] ?? '') . ' ' . ($ticket['last_name'] ?? 'N/A')); ?></td>
                    <td><?php echo ucfirst($ticket['category']); ?></td>
                    <td><span class="badge badge-<?php echo $ticket['priority']; ?>"><?php echo ucfirst($ticket['priority']); ?></span></td>
                    <td><span class="badge badge-<?php echo str_replace(' ', '_', $ticket['status']); ?>"><?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?></span></td>
                    <td><?php echo htmlspecialchars($ticket['assigned_to_name'] ?? '-'); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($ticket['created_at'])); ?></td>
                    <td>
                      <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                        <select name="status" onchange="this.form.submit()" style="padding: 5px;">
                          <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                          <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                          <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                          <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                      </form>
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
</body>
</html>

