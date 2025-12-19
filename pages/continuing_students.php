<?php
session_start();
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'studentservices', 'hod'])) {
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
        if (isset($_POST['check_requirements'])) {
            $application_id = intval($_POST['application_id']);
            $requirements_met = isset($_POST['requirements_met']) ? 1 : 0;
            $requirements_notes = trim($_POST['requirements_notes'] ?? '');
            $shortfalls = trim($_POST['shortfalls'] ?? '');
            
            $sql = "UPDATE applications SET requirements_met = ?, requirements_notes = ?, shortfalls_identified = ?, status = ? WHERE application_id = ?";
            $stmt = $conn->prepare($sql);
            
            if ($requirements_met) {
                $new_status = 'hod_review';
            } else {
                $new_status = 'ineligible';
            }
            
            $stmt->bind_param('isssi', $requirements_met, $requirements_notes, $shortfalls, $new_status, $application_id);
            
            if ($stmt->execute()) {
                // Update requirement records
                if (isset($_POST['requirement_status'])) {
                    foreach ($_POST['requirement_status'] as $req_id => $status) {
                        $req_sql = "UPDATE continuing_student_requirements SET status = ?, verified_by = ?, verified_date = CURDATE() WHERE requirement_id = ?";
                        $req_stmt = $conn->prepare($req_sql);
                        $req_stmt->bind_param('sii', $status, $_SESSION['user_id'], $req_id);
                        $req_stmt->execute();
                        $req_stmt->close();
                    }
                }
                
                if ($requirements_met) {
                    $message = "Requirements verified. Application forwarded to HOD for review.";
                } else {
                    $message = "Requirements not met. Candidate advised of shortfalls.";
                }
                $message_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } elseif (isset($_POST['send_correspondence'])) {
            $application_id = intval($_POST['application_id']);
            $correspondence_type = trim($_POST['correspondence_type']);
            $subject = trim($_POST['subject']);
            $message_text = trim($_POST['message']);
            
            $sql = "INSERT INTO correspondence (application_id, correspondence_type, subject, message, sent_by, sent_date, status) VALUES (?, ?, ?, ?, ?, CURDATE(), 'sent')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('isssi', $application_id, $correspondence_type, $subject, $message_text, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $conn->query("UPDATE applications SET correspondence_sent = TRUE, correspondence_date = CURDATE(), status = 'correspondence_sent' WHERE application_id = $application_id");
                $message = "Correspondence sent successfully.";
                $message_type = "success";
            } else {
                $message = "Error sending correspondence: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } elseif (isset($_POST['create_invoice'])) {
            $application_id = intval($_POST['application_id']);
            
            // Get application details
            $stmt = $conn->prepare("SELECT a.*, s.student_id, s.student_number FROM applications a LEFT JOIN students s ON a.student_id = s.student_id WHERE a.application_id = ?");
            $stmt->bind_param("i", $application_id);
            $stmt->execute();
            $app_result = $stmt->get_result();
            $app = $app_result->fetch_assoc();
            $stmt->close();
            
            if (!$app) {
                $message = "Application not found.";
                $message_type = "error";
            } else {
                // Check if proforma_invoices table exists
                $table_check = $conn->query("SHOW TABLES LIKE 'proforma_invoices'");
                if ($table_check->num_rows === 0) {
                    $message = "Proforma invoice system not available. Please set up the finance system first.";
                    $message_type = "error";
                } else {
                    // Check if application_id column exists
                    $col_check = $conn->query("SHOW COLUMNS FROM proforma_invoices LIKE 'application_id'");
                    $has_app_id = $col_check->num_rows > 0;
                    
                    // Check if invoice already exists for this application
                    $existing_pi = null;
                    if ($has_app_id) {
                        $check_stmt = $conn->prepare("SELECT pi_id FROM proforma_invoices WHERE application_id = ? LIMIT 1");
                        $check_stmt->bind_param("i", $application_id);
                        $check_stmt->execute();
                        $existing_check = $check_stmt->get_result();
                        if ($existing_check->num_rows > 0) {
                            $existing_pi = $existing_check->fetch_assoc();
                        }
                        $check_stmt->close();
                    } else {
                        // Check by student name and course if no application_id column
                        $student_name = $app['first_name'] . ' ' . $app['last_name'];
                        $existing_check = $conn->query("SELECT pi_id FROM proforma_invoices WHERE student_name = '" . $conn->real_escape_string($student_name) . "' AND remarks LIKE '%application #" . $conn->real_escape_string($app['application_number']) . "%' LIMIT 1");
                        if ($existing_check->num_rows > 0) {
                            $existing_pi = $existing_check->fetch_assoc();
                        }
                    }
                    
                    if ($existing_pi) {
                        $message = "Invoice already exists for this application. <a href='proforma_invoice_details.php?id=" . $existing_pi['pi_id'] . "'>View Invoice</a>";
                        $message_type = "warning";
                    } else {
                        // Generate PI number
                        $year = date('Y');
                        $last_pi = $conn->query("SELECT pi_number FROM proforma_invoices WHERE pi_number LIKE 'PI-$year-%' ORDER BY pi_id DESC LIMIT 1");
                        $seq = 1;
                        if ($last_pi && $last_pi->num_rows > 0) {
                            $last_num = $last_pi->fetch_assoc()['pi_number'];
                            $parts = explode('-', $last_num);
                            if (count($parts) >= 3) {
                                $seq = intval($parts[2]) + 1;
                            }
                        }
                        $pi_number = "PI-$year-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
                        
                        // Get course fee (default or from application)
                        $course_fee = floatval($_POST['course_fee'] ?? 0);
                        if ($course_fee <= 0) {
                            // Try to get from application or use default
                            $course_fee = 5000.00; // Default fee - should be configurable
                        }
                        
                        $student_name = $app['first_name'] . ' ' . $app['last_name'];
                        $course_name = $app['program_interest'] ?? $app['course_type'] ?? 'Course Fee';
                        $date = date('Y-m-d');
                        
                        // Check if application has student_id (enrolled)
                        // Note: proforma_invoices table requires student_id to be NOT NULL
                        // So we can only create invoices for enrolled students
                        if (!$app['student_id']) {
                            $message = "Cannot create invoice: Student must be enrolled first. Please enroll the student before creating an invoice.";
                            $message_type = "error";
                        } else {
                            $student_id = $app['student_id'];
                        
                        $forwarding_address = $app['address'] ?? '';
                        $telephone = $app['phone'] ?? '';
                        $mobile_number = $app['phone'] ?? '';
                        $remarks = "Generated from application #" . $app['application_number'];
                        $pi_issuing_officer = $_SESSION['user_id'];
                        $created_by = $_SESSION['user_id'];
                        
                        // Proforma invoices table does NOT have application_id column
                        // Use SQL without application_id
                        $pi_sql = "INSERT INTO proforma_invoices (
                            pi_number, date, student_id, student_name, 
                            forwarding_address, telephone, mobile_number, course_name, 
                            course_fee, balance, status, pi_issuing_officer, remarks, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'outstanding', ?, ?, ?)";
                        
                        $pi_stmt = $conn->prepare($pi_sql);
                        if (!$pi_stmt) {
                            $message = "Error preparing statement: " . $conn->error;
                            $message_type = "error";
                        } else {
                            // Bind parameters: s=string, i=integer, d=double
                            // pi_number(s), date(s), student_id(i), student_name(s), 
                            // forwarding_address(s), telephone(s), mobile_number(s), course_name(s),
                            // course_fee(d), balance(d), status is 'outstanding', 
                            // pi_issuing_officer(i), remarks(s), created_by(i)
                            $pi_stmt->bind_param("ssisssssddisi", 
                                $pi_number, $date, $student_id, $student_name,
                                $forwarding_address, $telephone, $mobile_number, $course_name,
                                $course_fee, $course_fee, $pi_issuing_officer, $remarks, $created_by
                            );
                            
                            if ($pi_stmt->execute()) {
                                $pi_id = $conn->insert_id;
                                
                                // Update application with invoice reference
                                $invoice_id_check = $conn->query("SHOW COLUMNS FROM applications LIKE 'invoice_id'");
                                if ($invoice_id_check->num_rows > 0) {
                                    $conn->query("UPDATE applications SET invoice_sent = TRUE, invoice_id = $pi_id WHERE application_id = $application_id");
                                } else {
                                    $conn->query("UPDATE applications SET invoice_sent = TRUE WHERE application_id = $application_id");
                                }
                                
                                $message = "Proforma Invoice created successfully! PI Number: $pi_number. <a href='proforma_invoice_details.php?id=$pi_id'>View Invoice</a>";
                                $message_type = "success";
                            } else {
                                $message = "Error creating invoice: " . $pi_stmt->error;
                                $message_type = "error";
                            }
                            $pi_stmt->close();
                        }
                        }
                    }
                }
            }
        }
        $conn->close();
    }
}

// Get candidates returning applications
$conn = getDBConnection();
$applications = [];
$stats = [];

if ($conn) {
    $status_filter = $_GET['status'] ?? '';
    $query = "SELECT a.*, 
              u1.full_name as assessed_by_name, 
              u2.full_name as hod_decision_by_name
              FROM applications a 
              LEFT JOIN users u1 ON a.assessed_by = u1.user_id 
              LEFT JOIN users u2 ON a.hod_decision_by = u2.user_id
              WHERE (a.application_type = 'continuing_student_solas' OR a.application_type = 'continuing_student_next_level')";
    
    if ($status_filter) {
        $query .= " AND a.status = '" . $conn->real_escape_string($status_filter) . "'";
    }
    $query .= " ORDER BY a.submitted_at DESC";
    
    $result = $conn->query($query);
    if ($result) {
        $applications = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get statistics
    $result = $conn->query("SELECT status, COUNT(*) as count FROM applications WHERE (application_type = 'continuing_student_solas' OR application_type = 'continuing_student_next_level') GROUP BY status");
    while ($row = $result->fetch_assoc()) {
        $stats[$row['status']] = $row['count'];
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Candidates Returning - Student Admin</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <style>
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
    .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .stat-card h3 { margin: 0 0 10px 0; color: #666; font-size: 0.9rem; }
    .stat-card .number { font-size: 2rem; font-weight: bold; color: #1d4e89; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    table th { background: #1d4e89; color: white; }
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; }
    .badge-submitted { background: #e3f2fd; color: #1976d2; }
    .badge-hod-review { background: #fff3e0; color: #f57c00; }
    .badge-accepted { background: #e8f5e9; color: #388e3c; }
    .badge-ineligible { background: #ffebee; color: #d32f2f; }
    .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem; }
    .btn-primary { background: #1d4e89; color: white; }
    .btn-primary:hover { background: #163c6a; }
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); overflow-y: auto; }
    .modal-content { background: white; margin: 5% auto; padding: 20px; width: 90%; max-width: 800px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); position: relative; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    .form-group input[type="checkbox"] { width: auto; margin-right: 8px; }
    .requirements-item { transition: all 0.2s; }
    .requirements-item:hover { box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  </style>
</head>
<body>
  <div class="dashboard-wrap container">
    <nav class="sidebar" aria-label="Main navigation">
      <div class="brand">
        <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] === 'hod' ? 'hod_dashboard.php' : 'student_service_dashboard.php'); ?>" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
          <img src="../images/pnmc.png" alt="logo"> 
          <strong>PNGMC</strong>
        </a>
      </div>
      <div class="menu">
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <a class="menu-item" href="admin_dashboard.php">Dashboard</a>
          <div class="menu-section">Application Management</div>
          <a class="menu-item" href="applications.php">School Leavers</a>
          <a class="menu-item <?php echo isActive('continuing_students.php'); ?>" href="continuing_students.php">Candidates Returning</a>
          <div class="menu-section">Administration</div>
          <a class="menu-item" href="manage_staff.php">Manage Staff</a>
          <a class="menu-item" href="system_settings.php">System Settings</a>
          <a class="menu-item" href="reports.php">Reports & Analytics</a>
        <?php elseif ($_SESSION['role'] === 'hod'): ?>
          <a class="menu-item" href="hod_dashboard.php">Dashboard</a>
          <div class="menu-section">Applications</div>
          <a class="menu-item" href="applications.php?status=hod_review">Pending Review</a>
          <a class="menu-item" href="workflow_manager.php">Workflow Manager</a>
          <div class="menu-section">Reports</div>
          <a class="menu-item" href="reports.php">Reports</a>
        <?php else: ?>
          <a class="menu-item" href="student_service_dashboard.php">Dashboard</a>
          <div class="menu-section">Application Processing</div>
          <a class="menu-item" href="applications.php">School Leavers</a>
          <a class="menu-item <?php echo isActive('continuing_students.php'); ?>" href="continuing_students.php">Candidates Returning</a>
          <div class="menu-section">Student Management</div>
          <a class="menu-item" href="student_records.php">Student Records</a>
          <a class="menu-item" href="advising.php">Advising</a>
          <a class="menu-item" href="support_tickets.php">Support Tickets</a>
        <?php endif; ?>
      </div>
    </nav>
  
  <div class="content">
    <h1>Candidates Returning - Applications</h1>
    
    <?php if ($message): ?>
      <div class="message <?php echo $message_type; ?>" style="padding: 15px; margin: 20px 0; border-radius: 5px;">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>
    
    <div class="stats-grid">
      <div class="stat-card">
        <h3>Submitted</h3>
        <div class="number"><?php echo $stats['submitted'] ?? 0; ?></div>
      </div>
      <div class="stat-card">
        <h3>Under Review</h3>
        <div class="number"><?php echo $stats['under_review'] ?? 0; ?></div>
      </div>
      <div class="stat-card">
        <h3>HOD Review</h3>
        <div class="number"><?php echo $stats['hod_review'] ?? 0; ?></div>
      </div>
      <div class="stat-card">
        <h3>Accepted</h3>
        <div class="number"><?php echo $stats['accepted'] ?? 0; ?></div>
      </div>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
      <table>
        <thead>
          <tr>
            <th>App #</th>
            <th>Name</th>
            <th>Type</th>
            <th>Course</th>
            <th>Status</th>
            <th>Submitted</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($applications)): ?>
            <tr><td colspan="7" style="text-align: center;">No candidate returning applications found.</td></tr>
          <?php else: ?>
            <?php foreach ($applications as $app): ?>
              <tr>
                <td><?php echo htmlspecialchars($app['application_number'] ?? 'APP-' . $app['application_id']); ?></td>
                <td><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                <td>
                  <?php 
                    if ($app['application_type'] === 'continuing_student_solas') {
                      echo 'SOLAS Refresher';
                    } elseif ($app['application_type'] === 'continuing_student_next_level') {
                      echo 'Next Level';
                    } else {
                      echo 'N/A';
                    }
                  ?>
                </td>
                <td><?php echo htmlspecialchars($app['course_type'] ?? 'N/A'); ?></td>
                <td>
                  <span class="badge badge-<?php echo str_replace('_', '-', $app['status']); ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                  </span>
                </td>
                <td><?php echo date('Y-m-d', strtotime($app['submitted_at'])); ?></td>
                <td>
                  <?php if ($app['status'] === 'submitted' || $app['status'] === 'under_review'): ?>
                    <button onclick="checkRequirements(<?php echo $app['application_id']; ?>)" class="btn btn-primary">Check Requirements</button>
                  <?php endif; ?>
                  <?php if ($app['status'] === 'accepted'): ?>
                    <button onclick="sendCorrespondence(<?php echo $app['application_id']; ?>)" class="btn btn-primary">Send Correspondence</button>
                    <button onclick="createInvoice(<?php echo $app['application_id']; ?>)" class="btn btn-primary">Create Invoice</button>
                  <?php endif; ?>
                  <a href="application_details.php?id=<?php echo $app['application_id']; ?>" class="btn btn-primary">View Details</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Check Requirements Modal -->
  <div id="requirementsModal" class="modal">
    <div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
      <span style="float: right; cursor: pointer; font-size: 24px; font-weight: bold;" onclick="document.getElementById('requirementsModal').style.display='none'">&times;</span>
      <h2>Check Requirements</h2>
      <form method="POST" id="requirementsForm">
        <input type="hidden" name="application_id" id="req_app_id">
        
        <!-- Requirements Checklist -->
        <div class="form-group" style="margin-bottom: 20px;">
          <label style="font-weight: 600; margin-bottom: 10px; display: block; color: #1d4e89;">Requirements Checklist:</label>
          <div id="requirementsList" style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
            <p style="color: #666; font-style: italic;">Loading requirements...</p>
          </div>
        </div>
        
        <!-- Overall Status -->
        <div class="form-group">
          <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
            <input type="checkbox" name="requirements_met" value="1" id="req_met" onchange="updateOverallStatus()">
            <strong>All Requirements Met</strong>
          </label>
          <small style="color: #666; display: block; margin-top: 5px;">Check this box only if ALL requirements above are met</small>
        </div>
        
        <!-- Notes -->
        <div class="form-group">
          <label>Requirements Notes</label>
          <textarea name="requirements_notes" id="req_notes" rows="4" placeholder="Add any notes about the requirements verification..."></textarea>
        </div>
        
        <!-- Shortfalls -->
        <div class="form-group">
          <label>Shortfalls Identified</label>
          <textarea name="shortfalls" id="req_shortfalls" rows="3" placeholder="List any shortfalls that need to be addressed (e.g., Missing NMSA approval, Incomplete sea service record, etc.)"></textarea>
        </div>
        
        <div style="display: flex; gap: 10px; margin-top: 20px;">
          <button type="submit" name="check_requirements" class="btn btn-primary">Submit</button>
          <button type="button" onclick="document.getElementById('requirementsModal').style.display='none'" class="btn" style="background: #ccc; color: #333;">Cancel</button>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Send Correspondence Modal -->
  <div id="correspondenceModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
      <span style="float: right; cursor: pointer; font-size: 24px; font-weight: bold;" onclick="closeModal('correspondenceModal')">&times;</span>
      <h2>Send Correspondence</h2>
      <form method="POST">
        <input type="hidden" name="application_id" id="corr_app_id">
        <input type="hidden" name="send_correspondence" value="1">
        
        <div class="form-group">
          <label>Correspondence Type *</label>
          <select name="correspondence_type" required>
            <option value="acceptance_letter">Acceptance Letter</option>
            <option value="requirements_letter">Requirements Letter</option>
            <option value="invoice">Invoice & Requirements</option>
            <option value="rejection_letter">Rejection Letter</option>
            <option value="email">Email</option>
            <option value="phone">Phone/SMS</option>
          </select>
        </div>
        
        <div class="form-group">
          <label>Subject *</label>
          <input type="text" name="subject" id="corr_subject" required placeholder="Enter subject line">
        </div>
        
        <div class="form-group">
          <label>Message *</label>
          <textarea name="message" id="corr_message" rows="8" required placeholder="Enter your message..."></textarea>
        </div>
        
        <div style="display: flex; gap: 10px; margin-top: 20px;">
          <button type="submit" class="btn btn-primary">Send Correspondence</button>
          <button type="button" onclick="closeModal('correspondenceModal')" class="btn" style="background: #ccc; color: #333;">Cancel</button>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Create Invoice Modal -->
  <div id="invoiceModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
      <span style="float: right; cursor: pointer; font-size: 24px; font-weight: bold;" onclick="closeModal('invoiceModal')">&times;</span>
      <h2>Create Proforma Invoice</h2>
      <form method="POST">
        <input type="hidden" name="application_id" id="invoice_app_id">
        <input type="hidden" name="create_invoice" value="1">
        
        <div class="form-group">
          <label>Student Name:</label>
          <div id="invoice_student_name" style="padding: 8px; background: #f5f5f5; border-radius: 4px;"></div>
        </div>
        
        <div class="form-group">
          <label>Course:</label>
          <div id="invoice_course" style="padding: 8px; background: #f5f5f5; border-radius: 4px;"></div>
        </div>
        
        <div class="form-group">
          <label>Course Fee (PGK) *</label>
          <input type="number" name="course_fee" step="0.01" min="0" required placeholder="Enter course fee amount" value="5000.00">
          <small style="color: #666;">Enter the total course fee amount</small>
        </div>
        
        <div style="display: flex; gap: 10px; margin-top: 20px;">
          <button type="submit" class="btn btn-primary">Create Invoice</button>
          <button type="button" onclick="closeModal('invoiceModal')" class="btn" style="background: #ccc; color: #333;">Cancel</button>
        </div>
      </form>
    </div>
  </div>
  </div>
  
  <script>
    function checkRequirements(appId) {
      document.getElementById('req_app_id').value = appId;
      document.getElementById('requirementsModal').style.display = 'block';
      loadRequirements(appId);
    }
    
    function loadRequirements(appId) {
      // Reset form
      document.getElementById('req_met').checked = false;
      document.getElementById('req_notes').value = '';
      document.getElementById('req_shortfalls').value = '';
      document.getElementById('requirementsList').innerHTML = '<p style="color: #666; font-style: italic;">Loading requirements...</p>';
      
      // Fetch requirements via AJAX
      fetch('get_requirements.php?application_id=' + appId, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json'
        }
      })
        .then(response => {
          if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
          }
          return response.text().then(text => {
            try {
              return JSON.parse(text);
            } catch (e) {
              console.error('Invalid JSON response:', text);
              throw new Error('Invalid response from server');
            }
          });
        })
        .then(data => {
          if (data.success) {
            if (data.requirements && data.requirements.length > 0) {
            let html = '<div style="display: flex; flex-direction: column; gap: 12px;">';
            data.requirements.forEach(function(req) {
              const statusColors = {
                'pending': '#999',
                'met': '#4caf50',
                'not_met': '#f44336',
                'shortfall_identified': '#ff9800'
              };
              const statusLabels = {
                'pending': 'Pending',
                'met': 'Met ✓',
                'not_met': 'Not Met ✗',
                'shortfall_identified': 'Shortfall ⚠'
              };
              
              html += '<div style="display: flex; align-items: center; gap: 15px; padding: 12px; background: white; border-radius: 5px; border-left: 4px solid ' + statusColors[req.status] + ';">';
              html += '<div style="flex: 1;">';
              html += '<strong style="display: block; margin-bottom: 5px;">' + escapeHtml(req.requirement_name) + '</strong>';
              html += '<small style="color: #666;">Type: ' + escapeHtml(req.requirement_type.replace(/_/g, ' ')) + '</small>';
              if (req.notes) {
                html += '<p style="margin: 5px 0 0 0; color: #666; font-size: 0.9em;">' + escapeHtml(req.notes) + '</p>';
              }
              html += '</div>';
              html += '<div style="min-width: 150px;">';
              html += '<select name="requirement_status[' + req.requirement_id + ']" style="padding: 6px; border: 1px solid #ddd; border-radius: 4px; width: 100%;" onchange="updateRequirementStatus(this)">';
              html += '<option value="pending"' + (req.status === 'pending' ? ' selected' : '') + '>Pending</option>';
              html += '<option value="met"' + (req.status === 'met' ? ' selected' : '') + '>Met</option>';
              html += '<option value="not_met"' + (req.status === 'not_met' ? ' selected' : '') + '>Not Met</option>';
              html += '<option value="shortfall_identified"' + (req.status === 'shortfall_identified' ? ' selected' : '') + '>Shortfall</option>';
              html += '</select>';
              html += '</div>';
              html += '<div style="min-width: 100px; text-align: right;">';
              html += '<span style="padding: 4px 8px; border-radius: 4px; background: ' + statusColors[req.status] + '; color: white; font-size: 0.85em;">' + statusLabels[req.status] + '</span>';
              html += '</div>';
              html += '</div>';
            });
            html += '</div>';
            document.getElementById('requirementsList').innerHTML = html;
            } else {
              document.getElementById('requirementsList').innerHTML = '<p style="color: #f44336; padding: 15px; background: #ffebee; border-radius: 5px;">No requirements found for this application. Requirements should be created when the application is submitted.</p>';
            }
          } else {
            const errorMsg = data.error || 'Unknown error';
            document.getElementById('requirementsList').innerHTML = '<p style="color: #f44336; padding: 15px; background: #ffebee; border-radius: 5px;">Error: ' + escapeHtml(errorMsg) + '</p>';
          }
        })
        .catch(error => {
          console.error('Error loading requirements:', error);
          document.getElementById('requirementsList').innerHTML = '<p style="color: #f44336; padding: 15px; background: #ffebee; border-radius: 5px;">Error loading requirements: ' + escapeHtml(error.message) + '<br><small>Check browser console for details.</small></p>';
        });
    }
    
    function updateRequirementStatus(select) {
      // Update the status badge
      const row = select.closest('div[style*="display: flex"]');
      const badge = row.querySelector('span[style*="padding: 4px"]');
      const status = select.value;
      
      const statusColors = {
        'pending': '#999',
        'met': '#4caf50',
        'not_met': '#f44336',
        'shortfall_identified': '#ff9800'
      };
      const statusLabels = {
        'pending': 'Pending',
        'met': 'Met ✓',
        'not_met': 'Not Met ✗',
        'shortfall_identified': 'Shortfall ⚠'
      };
      
      badge.textContent = statusLabels[status];
      badge.style.background = statusColors[status];
      row.style.borderLeftColor = statusColors[status];
      
      // Auto-update overall status
      updateOverallStatus();
    }
    
    function updateOverallStatus() {
      // Check if all requirements are met
      const selects = document.querySelectorAll('select[name^="requirement_status"]');
      let allMet = true;
      
      selects.forEach(function(select) {
        if (select.value !== 'met') {
          allMet = false;
        }
      });
      
      // Auto-check "Requirements Met" if all are met
      if (selects.length > 0) {
        document.getElementById('req_met').checked = allMet;
      }
    }
    
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
    
    function sendCorrespondence(appId) {
      document.getElementById('corr_app_id').value = appId;
      document.getElementById('correspondenceModal').style.display = 'block';
      
      // Pre-fill subject and message based on application status
      fetch('get_application_info.php?application_id=' + appId)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const app = data.application;
            const subjectField = document.getElementById('corr_subject');
            const messageField = document.getElementById('corr_message');
            
            // Pre-fill acceptance letter content
            if (app.status === 'accepted') {
              subjectField.value = 'Acceptance Letter - ' + app.application_number;
              messageField.value = 'Dear ' + app.first_name + ' ' + app.last_name + ',\n\n' +
                'Congratulations! Your application #' + app.application_number + ' has been accepted.\n\n' +
                'Please find attached:\n' +
                '- Acceptance Letter\n' +
                '- School Fee Invoice\n' +
                '- Requirements List\n\n' +
                'Next Steps:\n' +
                '1. Review the acceptance letter and invoice\n' +
                '2. Complete all mandatory requirements\n' +
                '3. Submit required documents\n' +
                '4. Make payment as per invoice\n\n' +
                'If you have any questions, please contact us.\n\n' +
                'Best regards,\n' +
                'PNG Maritime College';
            }
          }
        })
        .catch(error => console.error('Error loading application info:', error));
    }
    
    function createInvoice(appId) {
      document.getElementById('invoice_app_id').value = appId;
      document.getElementById('invoiceModal').style.display = 'block';
      
      // Load application info for pre-filling
      fetch('get_application_info.php?application_id=' + appId)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const app = data.application;
            document.getElementById('invoice_student_name').textContent = app.first_name + ' ' + app.last_name;
            document.getElementById('invoice_course').textContent = app.program_interest || app.course_type || 'Course Fee';
          }
        })
        .catch(error => console.error('Error loading application info:', error));
    }
    
    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
    }
  </script>
</body>
</html>

