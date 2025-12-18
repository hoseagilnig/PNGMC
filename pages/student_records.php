<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'studentservices') {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';
require_once 'includes/archive_helper.php';
require_once 'includes/security_helper.php';

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = 'Invalid security token. Please refresh the page and try again.';
        $message_type = "error";
    } else {
        $conn = getDBConnection();
        if ($conn) {
            if (isset($_POST['action']) && $_POST['action'] === 'archive') {
                // Only admin can archive
                if ($_SESSION['role'] === 'admin') {
                    $student_id = intval($_POST['student_id']);
                    $reason = trim($_POST['archive_reason'] ?? 'Manual archive');
                    $notes = trim($_POST['archive_notes'] ?? '');
                    
                    $result = archiveStudent($student_id, $_SESSION['user_id'], $reason, $notes);
                    
                    if ($result['success']) {
                        $message = $result['message'];
                        $message_type = "success";
                    } else {
                        $message = $result['message'];
                        $message_type = "error";
                    }
                } else {
                    $message = "Only administrators can archive students.";
                    $message_type = "error";
                }
            } elseif (isset($_POST['action']) && $_POST['action'] === 'add') {
                $student_number = trim($_POST['student_number']);
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $middle_name = trim($_POST['middle_name'] ?? '');
                $date_of_birth = $_POST['date_of_birth'] ?? null;
                $gender = $_POST['gender'] ?? null;
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $address = trim($_POST['address'] ?? '');
                $program_id = $_POST['program_id'] ?? null;
                $enrollment_date = $_POST['enrollment_date'] ?? date('Y-m-d');
                
                // Check if student_number already exists
                $check_stmt = $conn->prepare("SELECT student_id FROM students WHERE student_number = ?");
                $check_stmt->bind_param("s", $student_number);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $message = "Error: Student number '$student_number' already exists. Please use a different student number.";
                    $message_type = "error";
                    $check_stmt->close();
                } else {
                    $check_stmt->close();
                    
                    $stmt = $conn->prepare("INSERT INTO students (student_number, first_name, last_name, middle_name, date_of_birth, gender, email, phone, address, program_id, enrollment_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                    $stmt->bind_param("sssssssssis", $student_number, $first_name, $last_name, $middle_name, $date_of_birth, $gender, $email, $phone, $address, $program_id, $enrollment_date);
                    
                    if ($stmt->execute()) {
                        $student_id = $conn->insert_id;
                        // Create enrollment
                        if ($program_id) {
                            $enroll_stmt = $conn->prepare("INSERT INTO enrollments (student_id, program_id, enrollment_date, status) VALUES (?, ?, ?, 'enrolled')");
                            $enroll_stmt->bind_param("iis", $student_id, $program_id, $enrollment_date);
                            $enroll_stmt->execute();
                            $enroll_stmt->close();
                        }
                        $message = "Student added successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Error: " . $stmt->error;
                        $message_type = "error";
                    }
                    $stmt->close();
                }
            }
            $conn->close();
        }
    }
}

// Get all students
$conn = getDBConnection();
$students = [];
$programs = [];
if ($conn) {
    $result = $conn->query("SELECT s.*, p.program_name FROM students s LEFT JOIN programs p ON s.program_id = p.program_id ORDER BY s.created_at DESC");
    if ($result) {
        $students = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    $result = $conn->query("SELECT program_id, program_code, program_name FROM programs WHERE status = 'active' ORDER BY program_name");
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
  <title>Student Records - Student Services</title>
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
    .badge-active { background: #28a745; color: white; }
    .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; background: var(--primary); color: white; }
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
    .modal-content { background: white; margin: 15% auto; padding: 20px; border-radius: 10px; width: 500px; max-width: 90%; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .close { font-size: 28px; font-weight: bold; cursor: pointer; }
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
        <a class="menu-item active" href="student_records.php">Student Records</a>
        <a class="menu-item" href="advising.php">Advising</a>
        <a class="menu-item" href="support_tickets.php">Support Tickets</a>
      </div>
    </nav>

    <div class="content">
      <div class="main-card">
        <h1>Student Records</h1>
        
        <?php if ($message): ?>
          <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="form-section">
          <h2>Add New Student</h2>
          <form method="POST">
            <?php echo generateCSRFTokenInput(); ?>
            <input type="hidden" name="action" value="add">
            <div class="form-row">
              <div>
                <label>Student Number *</label>
                <input type="text" name="student_number" required>
              </div>
              <div>
                <label>Program *</label>
                <select name="program_id" required>
                  <option value="">Select Program</option>
                  <?php foreach ($programs as $program): ?>
                    <option value="<?php echo $program['program_id']; ?>">
                      <?php echo htmlspecialchars($program['program_code'] . ' - ' . $program['program_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div>
                <label>First Name *</label>
                <input type="text" name="first_name" required>
              </div>
              <div>
                <label>Last Name *</label>
                <input type="text" name="last_name" required>
              </div>
            </div>
            <div class="form-row">
              <div>
                <label>Middle Name</label>
                <input type="text" name="middle_name">
              </div>
              <div>
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth">
              </div>
            </div>
            <div class="form-row">
              <div>
                <label>Gender</label>
                <select name="gender">
                  <option value="">Select</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div>
                <label>Enrollment Date *</label>
                <input type="date" name="enrollment_date" value="<?php echo date('Y-m-d'); ?>" required>
              </div>
            </div>
            <div class="form-row">
              <div>
                <label>Email</label>
                <input type="email" name="email">
              </div>
              <div>
                <label>Phone</label>
                <input type="text" name="phone">
              </div>
            </div>
            <div class="form-row">
              <div style="grid-column: 1 / -1;">
                <label>Address</label>
                <textarea name="address" rows="2"></textarea>
              </div>
            </div>
            <button type="submit" class="btn">Add Student</button>
          </form>
        </div>

        <div class="form-section">
          <h2>All Students</h2>
          <table>
            <thead>
              <tr>
                <th>Student #</th>
                <th>Name</th>
                <th>Program</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Enrollment Date</th>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                  <th>Actions</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($students)): ?>
                <tr><td colspan="<?php echo $_SESSION['role'] === 'admin' ? '8' : '7'; ?>" style="text-align: center;">No students found.</td></tr>
              <?php else: ?>
                <?php foreach ($students as $student): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['program_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($student['email'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($student['phone'] ?? '-'); ?></td>
                    <td><span class="badge badge-<?php echo $student['status'] === 'active' ? 'active' : 'inactive'; ?>"><?php echo ucfirst($student['status']); ?></span></td>
                    <td><?php echo $student['enrollment_date'] ? date('Y-m-d', strtotime($student['enrollment_date'])) : '-'; ?></td>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                      <td>
                        <button onclick="archiveStudent(<?php echo $student['student_id']; ?>)" class="btn" style="background: #6c757d; font-size: 0.85rem; padding: 6px 12px;">Archive</button>
                      </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Archive Modal -->
  <div id="archiveModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Archive Student</h2>
        <span class="close" onclick="closeArchiveModal()">&times;</span>
      </div>
      <form method="POST" id="archiveForm">
        <?php echo generateCSRFToken(); ?>
        <input type="hidden" name="action" value="archive">
        <input type="hidden" name="student_id" id="archive_student_id">
        <div style="margin-bottom: 15px;">
          <label>Archive Reason *</label>
          <input type="text" name="archive_reason" required placeholder="e.g., Graduated, Withdrawn, Inactive">
        </div>
        <div style="margin-bottom: 15px;">
          <label>Notes</label>
          <textarea name="archive_notes" rows="3" placeholder="Additional notes..."></textarea>
        </div>
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
          <button type="button" onclick="closeArchiveModal()" class="btn" style="background: #6c757d;">Cancel</button>
          <button type="submit" class="btn" style="background: #dc3545;">Archive</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function archiveStudent(studentId) {
      document.getElementById('archive_student_id').value = studentId;
      document.getElementById('archiveModal').style.display = 'block';
    }
    
    function closeArchiveModal() {
      document.getElementById('archiveModal').style.display = 'none';
    }
    
    window.onclick = function(event) {
      const modal = document.getElementById('archiveModal');
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    }
  </script>
</body>
</html>
