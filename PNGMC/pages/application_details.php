<?php
session_start();
// Allow admin, studentservices, hod, and finance roles to view application details
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'studentservices', 'hod', 'finance'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';
require_once 'includes/workflow_helper.php';

$application_id = intval($_GET['id'] ?? 0);
$conn = getDBConnection();
$application = null;
$documents = [];
$checks = [];
$correspondence = [];
$notes = [];
$requirements = [];

if ($conn && $application_id) {
    $stmt = $conn->prepare("SELECT a.*, u1.full_name as assessed_by_name, u2.full_name as hod_decision_by_name, s.student_number FROM applications a LEFT JOIN users u1 ON a.assessed_by = u1.user_id LEFT JOIN users u2 ON a.hod_decision_by = u2.user_id LEFT JOIN students s ON a.student_id = s.student_id WHERE a.application_id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $application = $result->fetch_assoc();
    }
    $stmt->close();
    
    if ($application) {
        $stmt = $conn->prepare("SELECT * FROM application_documents WHERE application_id = ? ORDER BY uploaded_at DESC");
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $documents = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        $stmt = $conn->prepare("SELECT * FROM mandatory_checks WHERE application_id = ? ORDER BY created_at");
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $checks = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        $stmt = $conn->prepare("SELECT c.*, u.full_name as sent_by_name FROM correspondence c LEFT JOIN users u ON c.sent_by = u.user_id WHERE c.application_id = ? ORDER BY c.sent_at DESC");
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $correspondence = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        $stmt = $conn->prepare("SELECT n.*, u.full_name as user_name FROM application_notes n LEFT JOIN users u ON n.user_id = u.user_id WHERE n.application_id = ? ORDER BY n.created_at DESC");
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $notes = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Get requirements for all application types (continuing students and school leavers)
        $table_check = $conn->query("SHOW TABLES LIKE 'continuing_student_requirements'");
        if ($table_check->num_rows > 0) {
            $result = $conn->query("SELECT csr.*, u.full_name as verified_by_name 
                                     FROM continuing_student_requirements csr 
                                     LEFT JOIN users u ON csr.verified_by = u.user_id 
                                     WHERE csr.application_id = $application_id 
                                     ORDER BY csr.requirement_id ASC");
            if ($result) {
                $requirements = $result->fetch_all(MYSQLI_ASSOC);
            }
        }
    }
    $conn->close();
}

// Get workflow history
$workflow_history = getWorkflowHistory($application_id);

if (!$application) {
    header('Location: applications.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Application Details - Admin</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <style>
    .detail-section { background: var(--card-bg); padding: 20px; border-radius: 10px; margin-bottom: 20px; }
    .detail-row { display: grid; grid-template-columns: 1fr 2fr; gap: 15px; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
    .detail-label { font-weight: 600; color: var(--primary); }
    .badge { padding: 4px 8px; border-radius: 3px; font-size: 0.85rem; }
    .badge-pending { background: #ffc107; color: #000; }
    .badge-completed { background: #28a745; color: white; }
    .badge-failed { background: #dc3545; color: white; }
    .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; }
    .btn-primary { background: var(--primary); color: white; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: var(--primary); color: white; }
    .modal { 
      display: none; 
      position: fixed; 
      top: 0; 
      left: 0; 
      width: 100%; 
      height: 100%; 
      background: rgba(0,0,0,0.5); 
      z-index: 10000; 
      overflow-y: auto;
    }
    .modal-content { 
      background: white; 
      margin: 50px auto; 
      padding: 25px; 
      width: 90%; 
      max-width: 600px; 
      border-radius: 10px; 
      max-height: 80vh; 
      overflow-y: auto;
      position: relative;
    }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: var(--primary); }
    .form-group input, .form-group select, .form-group textarea { 
      width: 100%; 
      padding: 10px; 
      border: 1px solid #ddd; 
      border-radius: 5px; 
      box-sizing: border-box;
    }
  </style>
</head>
<body>
    <div class="dashboard-wrap container">
    <nav class="sidebar" aria-label="Main navigation">
      <div class="brand">
        <a href="<?php 
          if ($_SESSION['role'] === 'admin') echo 'admin_dashboard.php';
          elseif ($_SESSION['role'] === 'hod') echo 'hod_dashboard.php';
          elseif ($_SESSION['role'] === 'finance') echo 'finance_dashboard.php';
          else echo 'student_service_dashboard.php';
        ?>" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
          <img src="../images/pnmc.png" alt="logo"> 
          <strong>PNGMC</strong>
        </a>
      </div>
      <div class="menu">
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <a class="menu-item" href="admin_dashboard.php">Dashboard</a>
          <a class="menu-item active" href="applications.php">Applications</a>
          <a class="menu-item" href="manage_staff.php">Manage Staff</a>
          <a class="menu-item" href="system_settings.php">System Settings</a>
          <a class="menu-item" href="reports.php">Reports</a>
        <?php elseif ($_SESSION['role'] === 'hod'): ?>
          <a class="menu-item" href="hod_dashboard.php">Dashboard</a>
          <a class="menu-item active" href="applications.php?status=hod_review">Pending Review</a>
          <a class="menu-item" href="workflow_manager.php">Workflow Manager</a>
          <a class="menu-item" href="reports.php">Reports</a>
        <?php elseif ($_SESSION['role'] === 'finance'): ?>
          <a class="menu-item" href="finance_dashboard.php">Dashboard</a>
          <a class="menu-item active" href="workflow_manager.php">Workflow Manager</a>
          <a class="menu-item" href="billing.php">Billing</a>
          <a class="menu-item" href="invoices.php">Invoices</a>
        <?php else: ?>
          <a class="menu-item" href="student_service_dashboard.php">Dashboard</a>
          <a class="menu-item" href="applications.php">School Leavers</a>
          <a class="menu-item active" href="continuing_students.php">Candidates Returning</a>
          <a class="menu-item" href="student_records.php">Student Records</a>
          <a class="menu-item" href="advising.php">Advising</a>
          <a class="menu-item" href="support_tickets.php">Support Tickets</a>
        <?php endif; ?>
      </div>
    </nav>

    <div class="content">
      <div class="main-card">
        <h1>Application Details</h1>
        <a href="<?php 
          if ($_SESSION['role'] === 'admin') {
              echo 'applications.php';
          } elseif ($_SESSION['role'] === 'hod') {
              echo 'applications.php?status=hod_review';
          } elseif ($_SESSION['role'] === 'finance') {
              echo 'workflow_manager.php';
          } else {
              echo (isset($application['application_type']) && ($application['application_type'] === 'continuing_student_solas' || $application['application_type'] === 'continuing_student_next_level') ? 'continuing_students.php' : 'applications.php');
          }
        ?>" style="color: var(--primary); text-decoration: none;">← Back</a>

        <?php
        // Determine application type
        $is_continuing = isset($application['application_type']) && ($application['application_type'] === 'continuing_student_solas' || $application['application_type'] === 'continuing_student_next_level');
        $is_new_student = isset($application['application_type']) && ($application['application_type'] === 'new_student' || $application['application_type'] === NULL);
        ?>

        <div class="detail-section">
          <h2>Application Information</h2>
          <div class="detail-row">
            <div class="detail-label">Application Number:</div>
            <div><?php echo htmlspecialchars($application['application_number']); ?></div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Application Type:</div>
            <div>
              <?php 
              if ($is_continuing) {
                  echo '<span class="badge" style="background: #17a2b8; color: white;">Returning Student (Continuing Student)</span>';
                  if ($application['application_type'] === 'continuing_student_solas') {
                      echo ' <small style="color: #666;">(SOLAS)</small>';
                  } elseif ($application['application_type'] === 'continuing_student_next_level') {
                      echo ' <small style="color: #666;">(Next Level)</small>';
                  }
              } else {
                  echo '<span class="badge" style="background: #28a745; color: white;">School Leaver (New Student)</span>';
              }
              ?>
            </div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Name:</div>
            <div><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Email:</div>
            <div><?php echo htmlspecialchars($application['email'] ?? 'N/A'); ?></div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Phone:</div>
            <div><?php echo htmlspecialchars($application['phone'] ?? 'N/A'); ?></div>
          </div>
          <?php if ($application['address']): ?>
          <div class="detail-row">
            <div class="detail-label">Address:</div>
            <div><?php echo htmlspecialchars($application['address']); ?></div>
          </div>
          <?php endif; ?>
          <div class="detail-row">
            <div class="detail-label">Program Interest:</div>
            <div><?php echo htmlspecialchars($application['program_interest']); ?></div>
          </div>
          <?php if ($is_continuing && !empty($application['course_type'])): ?>
          <div class="detail-row">
            <div class="detail-label">Course Type:</div>
            <div><?php echo htmlspecialchars($application['course_type']); ?></div>
          </div>
          <?php endif; ?>
          <div class="detail-row">
            <div class="detail-label">Status:</div>
            <div><span class="badge"><?php echo ucfirst(str_replace('_', ' ', $application['status'])); ?></span></div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Submitted:</div>
            <div><?php echo date('Y-m-d H:i', strtotime($application['submitted_at'])); ?></div>
          </div>
        </div>

        <?php if ($is_new_student): ?>
        <!-- School Leaver (New Student) Specific Information -->
        <div class="detail-section">
          <h2>Academic Information (School Leaver)</h2>
          <?php if (isset($application['education_level'])): ?>
          <div class="detail-row">
            <div class="detail-label">Education Level:</div>
            <div><?php echo htmlspecialchars($application['education_level']); ?></div>
          </div>
          <?php endif; ?>
          <?php if (isset($application['school_name'])): ?>
          <div class="detail-row">
            <div class="detail-label">School Name:</div>
            <div><?php echo htmlspecialchars($application['school_name']); ?></div>
          </div>
          <?php endif; ?>
          <?php if (isset($application['year_completed'])): ?>
          <div class="detail-row">
            <div class="detail-label">Year Completed:</div>
            <div><?php echo htmlspecialchars($application['year_completed']); ?></div>
          </div>
          <?php endif; ?>
          <div class="detail-row">
            <div class="detail-label">Grade 12 Passed:</div>
            <div><?php echo $application['grade_12_passed'] ? 'Yes' : 'No'; ?></div>
          </div>
          <?php if (isset($application['maths_grade'])): ?>
          <div class="detail-row">
            <div class="detail-label">Mathematics Grade:</div>
            <div><?php echo htmlspecialchars($application['maths_grade']); ?></div>
          </div>
          <?php endif; ?>
          <?php if (isset($application['physics_grade'])): ?>
          <div class="detail-row">
            <div class="detail-label">Physics Grade:</div>
            <div><?php echo htmlspecialchars($application['physics_grade']); ?></div>
          </div>
          <?php endif; ?>
          <?php if (isset($application['english_grade'])): ?>
          <div class="detail-row">
            <div class="detail-label">English Grade:</div>
            <div><?php echo htmlspecialchars($application['english_grade']); ?></div>
          </div>
          <?php endif; ?>
          <?php if (isset($application['chemistry_grade'])): ?>
          <div class="detail-row">
            <div class="detail-label">Chemistry Grade:</div>
            <div><?php echo htmlspecialchars($application['chemistry_grade']); ?></div>
          </div>
          <?php endif; ?>
          <?php if (isset($application['overall_gpa'])): ?>
          <div class="detail-row">
            <div class="detail-label">Overall GPA:</div>
            <div><?php echo htmlspecialchars($application['overall_gpa']); ?></div>
          </div>
          <?php endif; ?>
          <?php if (isset($application['place_of_birth'])): ?>
          <div class="detail-row">
            <div class="detail-label">Place of Birth:</div>
            <div><?php echo htmlspecialchars($application['place_of_birth']); ?></div>
          </div>
          <?php endif; ?>
          <?php if (isset($application['nationality'])): ?>
          <div class="detail-row">
            <div class="detail-label">Nationality:</div>
            <div><?php echo htmlspecialchars($application['nationality']); ?></div>
          </div>
          <?php endif; ?>
          <?php if (isset($application['nid_number'])): ?>
          <div class="detail-row">
            <div class="detail-label">NID Number:</div>
            <div><?php echo htmlspecialchars($application['nid_number']); ?></div>
          </div>
          <?php endif; ?>
          <?php if (isset($application['next_of_kin_name'])): ?>
          <div class="detail-row">
            <div class="detail-label">Next of Kin Name:</div>
            <div><?php echo htmlspecialchars($application['next_of_kin_name']); ?></div>
          </div>
          <?php endif; ?>
          <?php if (isset($application['next_of_kin_relationship'])): ?>
          <div class="detail-row">
            <div class="detail-label">Next of Kin Relationship:</div>
            <div><?php echo htmlspecialchars($application['next_of_kin_relationship']); ?></div>
          </div>
          <?php endif; ?>
          <?php if (isset($application['next_of_kin_phone'])): ?>
          <div class="detail-row">
            <div class="detail-label">Next of Kin Phone:</div>
            <div><?php echo htmlspecialchars($application['next_of_kin_phone']); ?></div>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($is_continuing): ?>
        <!-- Continuing Student (Returning Student) Specific Information -->
        <div class="detail-section">
          <h2>Continuing Student Information (Returning Student)</h2>
          <div class="detail-row">
            <div class="detail-label">Application Type:</div>
            <div>
              <?php 
              if ($application['application_type'] === 'continuing_student_solas') {
                  echo 'SOLAS (Safety of Life at Sea)';
              } elseif ($application['application_type'] === 'continuing_student_next_level') {
                  echo 'Next Level Course';
              } else {
                  echo htmlspecialchars($application['application_type']);
              }
              ?>
            </div>
          </div>
          <?php if (!empty($application['course_type'])): ?>
          <div class="detail-row">
            <div class="detail-label">Course Type:</div>
            <div><?php echo htmlspecialchars($application['course_type']); ?> (<?php echo $application['course_type'] === 'Nautical' ? 'Deck/Mates' : 'Engine Room'; ?>)</div>
          </div>
          <?php endif; ?>
          <?php if (!empty($application['previous_student_id'])): ?>
          <div class="detail-row">
            <div class="detail-label">Previous Student ID:</div>
            <div><?php echo htmlspecialchars($application['previous_student_id']); ?></div>
          </div>
          <?php endif; ?>
          <?php if (!empty($application['coc_number'])): ?>
          <div class="detail-row">
            <div class="detail-label">COC Number:</div>
            <div><?php echo htmlspecialchars($application['coc_number']); ?></div>
          </div>
          <?php endif; ?>
          <?php if (!empty($application['coc_expiry_date'])): ?>
          <div class="detail-row">
            <div class="detail-label">COC Expiry Date:</div>
            <div><?php echo date('Y-m-d', strtotime($application['coc_expiry_date'])); ?></div>
          </div>
          <?php endif; ?>
          <?php if (!empty($application['nmsa_approval_letter_path'])): ?>
          <div class="detail-row">
            <div class="detail-label">NMSA Approval Letter:</div>
            <div>
              <?php if (file_exists($application['nmsa_approval_letter_path'])): ?>
                <a href="<?php echo htmlspecialchars($application['nmsa_approval_letter_path']); ?>" target="_blank" style="color: var(--primary);">View Document</a>
              <?php else: ?>
                <span style="color: #999;">File path: <?php echo htmlspecialchars($application['nmsa_approval_letter_path']); ?></span>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
          <?php if (!empty($application['sea_service_record_path'])): ?>
          <div class="detail-row">
            <div class="detail-label">Sea Service Record:</div>
            <div>
              <?php if (file_exists($application['sea_service_record_path'])): ?>
                <a href="<?php echo htmlspecialchars($application['sea_service_record_path']); ?>" target="_blank" style="color: var(--primary);">View Document</a>
              <?php else: ?>
                <span style="color: #999;">File path: <?php echo htmlspecialchars($application['sea_service_record_path']); ?></span>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="detail-section">
          <h2>Workflow Status</h2>
          <div class="detail-row">
            <div class="detail-label">Assessed By:</div>
            <div><?php echo htmlspecialchars($application['assessed_by_name'] ?? 'Not assessed'); ?></div>
          </div>
          <div class="detail-row">
            <div class="detail-label">HOD Decision:</div>
            <div><?php echo ucfirst($application['hod_decision'] ?? 'Pending'); ?></div>
          </div>
          <div class="detail-row">
            <div class="detail-label">HOD Decision By:</div>
            <div><?php echo htmlspecialchars($application['hod_decision_by_name'] ?? 'N/A'); ?></div>
          </div>
          <?php if (!empty($application['hod_decision_notes'])): ?>
          <div class="detail-row">
            <div class="detail-label">HOD Notes/Shortfall Details:</div>
            <div style="background: #f5f5f5; padding: 10px; border-radius: 4px; margin-top: 5px;">
              <?php echo nl2br(htmlspecialchars($application['hod_decision_notes'])); ?>
            </div>
          </div>
          <?php endif; ?>
          <?php if ($application['student_number']): ?>
          <div class="detail-row">
            <div class="detail-label">Student Number:</div>
            <div><?php echo htmlspecialchars($application['student_number']); ?></div>
          </div>
          <?php endif; ?>
        </div>

        <?php 
        // Show requirements section for all applications (continuing students and school leavers)
        // Variables $is_continuing and $is_new_student are already defined above
        if (!empty($requirements) || $is_continuing || $is_new_student): 
        ?>
        <div class="detail-section" id="requirementsSection">
          <h2>Requirements Checklist
            <?php if ($_SESSION['role'] === 'hod' && ($application['status'] === 'hod_review' || $application['current_department'] === 'hod')): ?>
              <span style="font-size: 0.8rem; color: #ff9800; margin-left: 10px;">⚠️ Review Required</span>
            <?php endif; ?>
          </h2>
          <?php if (empty($requirements)): ?>
            <p style="color: #666; text-align: center; padding: 20px;">No requirements have been created for this application yet.</p>
            <p style="color: #999; font-size: 0.85rem; text-align: center;">Requirements are typically created when the application is submitted.</p>
          <?php else: ?>
            <div style="margin-top: 15px;">
              <?php 
              $statusColors = [
                  'pending' => '#999',
                  'met' => '#28a745',
                  'not_met' => '#dc3545',
                  'shortfall_identified' => '#ff9800'
              ];
              $statusLabels = [
                  'pending' => 'Pending',
                  'met' => 'Met ✓',
                  'not_met' => 'Not Met ✗',
                  'shortfall_identified' => 'Shortfall Identified ⚠'
              ];
              $statusBadges = [
                  'pending' => 'badge-pending',
                  'met' => 'badge-completed',
                  'not_met' => 'badge-failed',
                  'shortfall_identified' => 'badge-pending'
              ];
              
              foreach ($requirements as $req): 
                $status = $req['status'];
                $badgeClass = $statusBadges[$status] ?? 'badge-pending';
              ?>
                <div style="display: flex; align-items: center; gap: 15px; padding: 15px; margin-bottom: 10px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid <?php echo $statusColors[$status] ?? '#999'; ?>;">
                  <div style="flex: 1;">
                    <strong style="display: block; margin-bottom: 5px; color: #333;"><?php echo htmlspecialchars($req['requirement_name']); ?></strong>
                    <small style="color: #666;">Type: <?php echo ucfirst(str_replace('_', ' ', $req['requirement_type'])); ?></small>
                    <?php if (!empty($req['notes'])): ?>
                      <p style="margin: 8px 0 0 0; color: #666; font-size: 0.9em; padding: 8px; background: white; border-radius: 4px;">
                        <?php echo nl2br(htmlspecialchars($req['notes'])); ?>
                      </p>
                    <?php endif; ?>
                    <?php if ($req['verified_date']): ?>
                      <small style="display: block; margin-top: 5px; color: #999;">
                        Verified: <?php echo date('Y-m-d', strtotime($req['verified_date'])); ?>
                        <?php if ($req['verified_by_name']): ?>
                          by <?php echo htmlspecialchars($req['verified_by_name']); ?>
                        <?php endif; ?>
                      </small>
                    <?php endif; ?>
                  </div>
                  <div>
                    <span class="badge <?php echo $badgeClass; ?>" style="min-width: 120px; text-align: center;">
                      <?php echo $statusLabels[$status] ?? 'Pending'; ?>
                    </span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            
            <?php if (!empty($application['requirements_notes'])): ?>
              <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 5px;">
                <strong style="display: block; margin-bottom: 8px; color: #1976d2;">Requirements Notes:</strong>
                <p style="margin: 0; color: #333;"><?php echo nl2br(htmlspecialchars($application['requirements_notes'])); ?></p>
              </div>
            <?php endif; ?>
            
            <?php if (!empty($application['shortfalls_identified'])): ?>
              <div style="margin-top: 15px; padding: 15px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ff9800;">
                <strong style="display: block; margin-bottom: 8px; color: #856404;">Shortfalls Identified:</strong>
                <p style="margin: 0; color: #333;"><?php echo nl2br(htmlspecialchars($application['shortfalls_identified'])); ?></p>
              </div>
            <?php endif; ?>
            
            <div style="margin-top: 15px; padding: 10px; background: <?php echo $application['requirements_met'] ? '#d4edda' : '#f8d7da'; ?>; border-radius: 5px;">
              <strong style="color: <?php echo $application['requirements_met'] ? '#155724' : '#721c24'; ?>;">
                Overall Status: <?php echo $application['requirements_met'] ? '✓ All Requirements Met' : '✗ Requirements Not Met'; ?>
              </strong>
            </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="detail-section" id="documentsSection">
          <h2>Submitted Documents
            <?php if ($_SESSION['role'] === 'hod' && ($application['status'] === 'hod_review' || $application['current_department'] === 'hod')): ?>
              <span style="font-size: 0.8rem; color: #ff9800; margin-left: 10px;">⚠️ Review Required</span>
            <?php endif; ?>
          </h2>
          <?php 
          // Debug: Show document count
          if (empty($documents)): 
          ?>
            <p style="color: #666; text-align: center; padding: 20px;">No documents uploaded yet.</p>
            <p style="color: #999; font-size: 0.85rem; text-align: center;">Documents will appear here once the applicant uploads required documents (Medical Certificate, Police Clearance, etc.) after receiving the acceptance letter.</p>
          <?php else: ?>
            <p style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Total documents: <?php echo count($documents); ?></p>
            <table>
              <thead>
                <tr>
                  <th>Document Type</th>
                  <th>Document Name</th>
                  <th>Uploaded Date</th>
                  <th>Verified</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($documents as $doc): ?>
                  <tr>
                    <td><?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?></td>
                    <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($doc['uploaded_at'])); ?></td>
                    <td>
                      <?php if ($doc['verified']): ?>
                        <span class="badge badge-completed">✓ Verified</span>
                      <?php else: ?>
                        <span class="badge badge-pending">Not Verified</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php 
                      // Check if file exists (try multiple path resolutions)
                      $file_exists = false;
                      $file_path_check = $doc['file_path'];
                      if (!empty($file_path_check)) {
                          // Try direct path
                          if (file_exists($file_path_check)) {
                              $file_exists = true;
                          } else {
                              // Try relative to project root
                              $project_root = dirname(__DIR__);
                              $absolute_path = $project_root . '/' . ltrim($file_path_check, '/');
                              if (file_exists($absolute_path)) {
                                  $file_exists = true;
                              }
                          }
                      }
                      ?>
                      <?php if ($file_exists): ?>
                        <a href="view_document.php?id=<?php echo $doc['document_id']; ?>" target="_blank" class="btn btn-primary" style="text-decoration: none; padding: 5px 10px; font-size: 0.85rem;">📄 View</a>
                        <a href="view_document.php?id=<?php echo $doc['document_id']; ?>&download=1" class="btn btn-primary" style="text-decoration: none; padding: 5px 10px; font-size: 0.85rem; background: #28a745;">⬇️ Download</a>
                      <?php else: ?>
                        <span style="color: #999; font-size: 0.85rem;">File not found</span>
                        <?php if (!empty($doc['file_path'])): ?>
                          <br><small style="color: #999; font-size: 0.75rem;">Path: <?php echo htmlspecialchars($doc['file_path']); ?></small>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>

        <div class="detail-section">
          <h2>Mandatory Checks</h2>
          <table>
            <thead>
              <tr>
                <th>Check Type</th>
                <th>Check Name</th>
                <th>Status</th>
                <th>Completed Date</th>
                <th>View Documents</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($checks)): ?>
                <tr><td colspan="6" style="text-align: center;">No mandatory checks assigned yet.</td></tr>
              <?php else: ?>
                <?php foreach ($checks as $check): ?>
                  <?php
                  // Find related documents for this check type
                  $related_docs = [];
                  $check_type_map = [
                    'medical' => 'medical_certificate',
                    'police_clearance' => 'police_clearance',
                    'academic_verification' => ['grade_12_certificate', 'transcript'],
                    'identity_verification' => ['birth_certificate', 'passport_photo'],
                    'financial_clearance' => 'other'
                  ];
                  
                  $doc_types = $check_type_map[$check['check_type']] ?? [];
                  if (is_string($doc_types)) {
                    $doc_types = [$doc_types];
                  }
                  
                  foreach ($documents as $doc) {
                    if (in_array($doc['document_type'], $doc_types)) {
                      $related_docs[] = $doc;
                    }
                  }
                  ?>
                  <tr>
                    <td><?php echo ucfirst(str_replace('_', ' ', $check['check_type'])); ?></td>
                    <td><?php echo htmlspecialchars($check['check_name']); ?></td>
                    <td><span class="badge badge-<?php echo $check['status']; ?>"><?php echo ucfirst($check['status']); ?></span></td>
                    <td><?php echo $check['completed_date'] ? date('Y-m-d', strtotime($check['completed_date'])) : 'N/A'; ?></td>
                    <td>
                      <?php 
                      $has_related_docs = false;
                      if (!empty($related_docs)): 
                        foreach ($related_docs as $rdoc): 
                          // Check if file exists (try multiple path resolutions)
                          $file_exists = false;
                          $file_path_check = $rdoc['file_path'] ?? '';
                          if (!empty($file_path_check)) {
                              // Try direct path
                              if (file_exists($file_path_check)) {
                                  $file_exists = true;
                              } else {
                                  // Try relative to project root
                                  $project_root = dirname(__DIR__);
                                  $absolute_path = $project_root . '/' . ltrim($file_path_check, '/');
                                  if (file_exists($absolute_path)) {
                                      $file_exists = true;
                                  }
                              }
                          }
                          if ($file_exists):
                            $has_related_docs = true;
                      ?>
                            <a href="view_document.php?id=<?php echo $rdoc['document_id']; ?>" target="_blank" style="margin-right: 5px; padding: 3px 8px; background: #1d4e89; color: white; text-decoration: none; border-radius: 3px; font-size: 0.8rem; display: inline-block; margin-bottom: 3px;">📄 View <?php echo htmlspecialchars($rdoc['document_name']); ?></a>
                      <?php 
                          endif;
                        endforeach;
                      endif;
                      if (!$has_related_docs):
                      ?>
                        <span style="color: #999; font-size: 0.85rem;">No documents uploaded</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($check['status'] !== 'completed'): ?>
                        <form method="POST" action="applications.php" style="display: inline;">
                          <input type="hidden" name="action" value="complete_check">
                          <input type="hidden" name="check_id" value="<?php echo $check['check_id']; ?>">
                          <select name="check_status" onchange="this.form.submit()" style="padding: 5px;">
                            <option value="pending" <?php echo $check['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $check['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $check['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="failed" <?php echo $check['status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                          </select>
                        </form>
                      <?php else: ?>
                        <span style="color: #28a745;">✓ Completed</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="detail-section">
          <h2>Correspondence History</h2>
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Subject</th>
                <th>Sent By</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($correspondence)): ?>
                <tr><td colspan="4" style="text-align: center;">No correspondence sent yet.</td></tr>
              <?php else: ?>
                <?php foreach ($correspondence as $corr): ?>
                  <tr>
                    <td><?php echo date('Y-m-d', strtotime($corr['sent_date'])); ?></td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $corr['correspondence_type'])); ?></td>
                    <td><?php echo htmlspecialchars($corr['subject']); ?></td>
                    <td><?php echo htmlspecialchars($corr['sent_by_name'] ?? 'N/A'); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Workflow Actions Section -->
        <div class="detail-section">
          <h2>Workflow Actions</h2>
          <div style="margin-bottom: 20px;">
            <strong>Current Department:</strong> 
            <span class="badge"><?php echo ucfirst(str_replace('studentservices', 'Student Services', $application['current_department'] ?? 'N/A')); ?></span>
            <br><br>
            <strong>Workflow Stage:</strong> 
            <span class="badge"><?php echo htmlspecialchars($application['workflow_stage'] ?? 'submitted'); ?></span>
          </div>
          
          <?php if (in_array($_SESSION['role'], ['studentservices', 'hod', 'finance'])): ?>
          <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px;">
            <?php if ($_SESSION['role'] === 'studentservices' && $application['status'] === 'accepted'): ?>
              <button onclick="forwardToFinance(<?php echo $application_id; ?>)" class="btn btn-primary">Forward to Finance</button>
            <?php endif; ?>
            
            <?php if ($_SESSION['role'] === 'studentservices' && ($application['status'] === 'under_review' || $application['status'] === 'submitted')): ?>
              <button onclick="forwardToHOD(<?php echo $application_id; ?>)" class="btn btn-primary">Forward to HOD</button>
            <?php endif; ?>
            
            <?php if ($_SESSION['role'] === 'finance' && $application['current_department'] === 'finance'): ?>
              <button onclick="financeApprove(<?php echo $application_id; ?>)" class="btn btn-primary">Approve</button>
              <button onclick="financeReject(<?php echo $application_id; ?>)" class="btn btn-primary" style="background: #dc3545;">Reject</button>
            <?php endif; ?>
            
            <?php if ($_SESSION['role'] === 'hod' && ($application['status'] === 'hod_review' || $application['current_department'] === 'hod')): ?>
              <div style="margin-top: 20px; padding: 20px; background: #fff3cd; border-left: 5px solid #ff9800; border-radius: 5px;">
                <h3 style="margin-top: 0; color: #856404;">⚠️ Review Checklist Before Decision</h3>
                <p style="margin-bottom: 15px; color: #856404;">Please review the following before making your decision:</p>
                <ul style="margin: 0; padding-left: 20px; color: #856404;">
                  <li>✓ <strong>Requirements Checklist</strong> - Check all requirements status below</li>
                  <li>✓ <strong>Submitted Documents</strong> - Review all uploaded documents</li>
                  <li>✓ <strong>Academic Information</strong> - Verify grades and qualifications</li>
                  <li>✓ <strong>Application Details</strong> - Review all application information</li>
                </ul>
              </div>
              <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button onclick="hodApprove(<?php echo $application_id; ?>)" class="btn btn-primary" style="flex: 1; padding: 12px; font-size: 1rem;">✓ Approve Application</button>
                <button onclick="hodReject(<?php echo $application_id; ?>)" class="btn btn-primary" style="flex: 1; padding: 12px; font-size: 1rem; background: #dc3545;">✗ Reject Application</button>
              </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          
          <?php if ($_SESSION['role'] === 'admin'): ?>
          <div style="padding: 15px; background: #e3f2fd; border-left: 4px solid #1d4e89; border-radius: 4px; margin-bottom: 20px;">
            <strong>📊 Admin View Mode:</strong> You are viewing this application in read-only mode. Use the <a href="workflow_monitor.php" style="color: #1d4e89; font-weight: 600;">Workflow Monitor</a> to track overall progress.
          </div>
          <?php endif; ?>
          
          <!-- Document Generation Section -->
          <?php if ($application['status'] === 'accepted' || $application['status'] === 'correspondence_sent'): ?>
          <div style="margin-top: 20px; padding: 15px; background: #f0f7ff; border: 2px solid #1d4e89; border-radius: 5px;">
            <h3 style="margin-top: 0; color: #1d4e89;">📄 Generate Documents</h3>
            <p style="margin-bottom: 15px; color: #666;">Generate and print official documents for the applicant:</p>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
              <a href="print_acceptance_letter.php?id=<?php echo $application_id; ?>" target="_blank" class="btn btn-primary" style="background: #28a745;">
                📝 Print Acceptance Letter
              </a>
              <a href="print_application_invoice.php?id=<?php echo $application_id; ?>" target="_blank" class="btn btn-primary" style="background: #17a2b8;">
                💰 Print Proforma Invoice
              </a>
            </div>
            <p style="margin-top: 15px; font-size: 0.9rem; color: #666;">
              <strong>Note:</strong> These documents should be sent together to the applicant. The acceptance letter includes all requirements and payment instructions.
            </p>
          </div>
          <?php endif; ?>
          
          <!-- Send Notification to Student (if enrolled) -->
          <?php if ($application['student_id'] && in_array($_SESSION['role'], ['admin', 'finance', 'studentservices'])): ?>
          <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 5px;">
            <h3 style="margin-top: 0; color: #856404;">🔔 Send Notification to Student</h3>
            <p style="margin-bottom: 15px; color: #666;">Send a notification to this student's account:</p>
            <a href="send_student_notification.php?student_id=<?php echo $application['student_id']; ?>" class="btn btn-primary" style="background: #ffc107; color: #000;">
              Send Notification
            </a>
          </div>
          <?php endif; ?>

          <!-- Workflow History -->
          <h3 style="margin-top: 30px; margin-bottom: 15px;">Workflow History</h3>
          <table>
            <thead>
              <tr>
                <th>Date & Time</th>
                <th>Action</th>
                <th>Performed By</th>
                <th>Department</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($workflow_history)): ?>
                <tr><td colspan="5" style="text-align: center;">No workflow history available.</td></tr>
              <?php else: ?>
                <?php foreach ($workflow_history as $action): ?>
                  <tr>
                    <td><?php echo date('Y-m-d H:i', strtotime($action['created_at'])); ?></td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $action['action_type'])); ?></td>
                    <td><?php echo htmlspecialchars($action['performed_by_name'] ?? 'System'); ?></td>
                    <td><?php echo ucfirst(str_replace('studentservices', 'Student Services', $action['performed_by_department'])); ?></td>
                    <td><?php echo htmlspecialchars($action['action_description']); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Workflow Action Modals -->
  <div id="workflowModal" class="modal" style="display: none;">
    <div class="modal-content">
      <h2 id="modalTitle">Workflow Action</h2>
      <form method="POST" action="workflow_manager.php" id="workflowForm">
        <input type="hidden" name="workflow_action" id="workflowAction">
        <input type="hidden" name="application_id" id="workflowAppId">
        <div class="form-group">
          <label>Notes (Optional)</label>
          <textarea name="notes" id="workflowNotes" rows="4" placeholder="Add any notes or comments..."></textarea>
        </div>
        <div style="display: flex; gap: 10px; margin-top: 20px;">
          <button type="submit" class="btn btn-primary">Confirm</button>
          <button type="button" onclick="closeWorkflowModal()" class="btn" style="background: #6c757d; color: white;">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- HOD Decision Modal -->
  <div id="hodDecisionModal" class="modal" style="display: none;">
    <div class="modal-content">
      <h2 id="hodModalTitle">HOD Decision</h2>
      <form method="POST" action="applications.php" id="hodDecisionForm">
        <input type="hidden" name="action" value="hod_decision">
        <input type="hidden" name="application_id" id="hodAppId">
        <input type="hidden" name="decision" id="hodDecision">
        <div class="form-group">
          <label>HOD Notes (Optional)</label>
          <textarea name="hod_notes" id="hodNotes" rows="4" placeholder="Add any notes or comments..."></textarea>
        </div>
        <div style="display: flex; gap: 10px; margin-top: 20px;">
          <button type="submit" class="btn btn-primary" id="hodSubmitBtn">Confirm</button>
          <button type="button" onclick="closeHODModal()" class="btn" style="background: #6c757d; color: white;">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function forwardToFinance(appId) {
      document.getElementById('modalTitle').textContent = 'Forward to Finance';
      document.getElementById('workflowAction').value = 'forward_to_finance';
      document.getElementById('workflowAppId').value = appId;
      document.getElementById('workflowModal').style.display = 'block';
    }
    
    function forwardToHOD(appId) {
      document.getElementById('modalTitle').textContent = 'Forward to HOD';
      document.getElementById('workflowAction').value = 'forward_to_hod';
      document.getElementById('workflowAppId').value = appId;
      document.getElementById('workflowModal').style.display = 'block';
    }
    
    function financeApprove(appId) {
      document.getElementById('modalTitle').textContent = 'Finance Approval';
      document.getElementById('workflowAction').value = 'finance_approve';
      document.getElementById('workflowAppId').value = appId;
      document.getElementById('workflowNotes').name = 'approval_notes';
      document.getElementById('workflowModal').style.display = 'block';
    }
    
    function financeReject(appId) {
      document.getElementById('modalTitle').textContent = 'Finance Rejection';
      document.getElementById('workflowAction').value = 'finance_reject';
      document.getElementById('workflowAppId').value = appId;
      document.getElementById('workflowNotes').name = 'rejection_notes';
      document.getElementById('workflowModal').style.display = 'block';
    }
    
    function hodApprove(appId) {
      document.getElementById('hodModalTitle').textContent = 'HOD Approval';
      document.getElementById('hodAppId').value = appId;
      document.getElementById('hodDecision').value = 'approved';
      document.getElementById('hodSubmitBtn').textContent = 'Approve';
      document.getElementById('hodSubmitBtn').style.background = 'var(--primary)';
      document.getElementById('hodNotes').value = '';
      document.getElementById('hodDecisionModal').style.display = 'block';
    }
    
    function hodReject(appId) {
      document.getElementById('hodModalTitle').textContent = 'HOD Rejection';
      document.getElementById('hodAppId').value = appId;
      document.getElementById('hodDecision').value = 'rejected';
      document.getElementById('hodSubmitBtn').textContent = 'Reject';
      document.getElementById('hodSubmitBtn').style.background = '#dc3545';
      document.getElementById('hodNotes').value = '';
      document.getElementById('hodDecisionModal').style.display = 'block';
    }
    
    function closeHODModal() {
      document.getElementById('hodDecisionModal').style.display = 'none';
      document.getElementById('hodNotes').value = '';
    }
    
    function closeWorkflowModal() {
      document.getElementById('workflowModal').style.display = 'none';
      document.getElementById('workflowNotes').value = '';
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
      const workflowModal = document.getElementById('workflowModal');
      const hodModal = document.getElementById('hodDecisionModal');
      if (event.target === workflowModal) {
        closeWorkflowModal();
      }
      if (event.target === hodModal) {
        closeHODModal();
      }
    }
  </script>
</body>
</html>

