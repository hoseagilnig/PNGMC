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
            $student_id = $_POST['student_id'];
            $advisor_id = $_SESSION['user_id'];
            $appointment_date = $_POST['appointment_date'];
            $appointment_time = $_POST['appointment_time'];
            $duration_minutes = $_POST['duration_minutes'] ?? 30;
            $subject = trim($_POST['subject'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            
            $stmt = $conn->prepare("INSERT INTO advising_appointments (student_id, advisor_id, appointment_date, appointment_time, duration_minutes, subject, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iississ", $student_id, $advisor_id, $appointment_date, $appointment_time, $duration_minutes, $subject, $notes);
            
            if ($stmt->execute()) {
                $message = "Appointment scheduled successfully!";
                $message_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } elseif (isset($_POST['action']) && $_POST['action'] === 'update_status') {
            $appointment_id = $_POST['appointment_id'];
            $status = $_POST['status'];
            
            $stmt = $conn->prepare("UPDATE advising_appointments SET status = ? WHERE appointment_id = ?");
            $stmt->bind_param("si", $status, $appointment_id);
            
            if ($stmt->execute()) {
                $message = "Appointment status updated!";
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
$appointments = [];
if ($conn) {
    $result = $conn->query("SELECT student_id, student_number, first_name, last_name FROM students WHERE status = 'active' ORDER BY last_name");
    if ($result) {
        $students = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    $result = $conn->query("SELECT a.*, s.student_number, s.first_name, s.last_name, u.full_name as advisor_name FROM advising_appointments a LEFT JOIN students s ON a.student_id = s.student_id LEFT JOIN users u ON a.advisor_id = u.user_id ORDER BY a.appointment_date DESC, a.appointment_time DESC");
    if ($result) {
        $appointments = $result->fetch_all(MYSQLI_ASSOC);
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Advising - Student Services</title>
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
    .badge-scheduled { background: #17a2b8; color: white; }
    .badge-completed { background: #28a745; color: white; }
    .badge-cancelled { background: #dc3545; color: white; }
    .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; background: var(--primary); color: white; }
    .btn-small { padding: 5px 10px; font-size: 0.9rem; }
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
        <a class="menu-item active" href="advising.php">Advising</a>
        <a class="menu-item" href="support_tickets.php">Support Tickets</a>
      </div>
    </nav>

    <div class="content">
      <div class="main-card">
        <h1>Advising Appointments</h1>
        
        <?php if ($message): ?>
          <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="form-section">
          <h2>Schedule New Appointment</h2>
          <form method="POST">
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
                <label>Appointment Date *</label>
                <input type="date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
              </div>
            </div>
            <div class="form-row">
              <div>
                <label>Appointment Time *</label>
                <input type="time" name="appointment_time" required>
              </div>
              <div>
                <label>Duration (minutes)</label>
                <input type="number" name="duration_minutes" value="30" min="15" step="15">
              </div>
            </div>
            <div class="form-row">
              <div>
                <label>Subject</label>
                <input type="text" name="subject" placeholder="Appointment subject">
              </div>
            </div>
            <div class="form-row">
              <div>
                <label>Notes</label>
                <textarea name="notes" rows="3"></textarea>
              </div>
            </div>
            <button type="submit" class="btn">Schedule Appointment</button>
          </form>
        </div>

        <div class="form-section">
          <h2>All Appointments</h2>
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Student</th>
                <th>Advisor</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($appointments)): ?>
                <tr><td colspan="7" style="text-align: center;">No appointments found.</td></tr>
              <?php else: ?>
                <?php foreach ($appointments as $apt): ?>
                  <tr>
                    <td><?php echo date('Y-m-d', strtotime($apt['appointment_date'])); ?></td>
                    <td><?php echo date('H:i', strtotime($apt['appointment_time'])); ?></td>
                    <td><?php echo htmlspecialchars(($apt['first_name'] ?? '') . ' ' . ($apt['last_name'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars($apt['advisor_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($apt['subject'] ?? '-'); ?></td>
                    <td>
                      <span class="badge badge-<?php echo $apt['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $apt['status'])); ?></span>
                    </td>
                    <td>
                      <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="appointment_id" value="<?php echo $apt['appointment_id']; ?>">
                        <select name="status" onchange="this.form.submit()" style="padding: 5px;">
                          <option value="scheduled" <?php echo $apt['status'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                          <option value="completed" <?php echo $apt['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                          <option value="cancelled" <?php echo $apt['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
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

