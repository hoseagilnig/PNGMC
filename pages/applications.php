<?php
session_start();
// Allow admin, studentservices, and hod roles to access applications
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'studentservices', 'hod'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';
require_once 'includes/workflow_helper.php';
require_once 'includes/security_helper.php';
require_once 'includes/archive_helper.php';

$message = '';
$message_type = '';

// Handle form submissions
// NOTE: Admin role is READ-ONLY - only Student Admin Service (studentservices) can perform actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = 'Invalid security token. Please refresh the page and try again.';
        $message_type = "error";
    } elseif ($_SESSION['role'] === 'admin') {
        // Block admin from performing any actions
        $message = "Access Denied: Administration role is read-only. Only Student Admin Service can perform actions on applications.";
        $message_type = "error";
    } else {
        $conn = getDBConnection();
        if ($conn) {
            if (isset($_POST['action'])) {
                if ($_POST['action'] === 'assess') {
                    $application_id = intval($_POST['application_id']);
                    $status = $_POST['status'];
                    $notes = trim($_POST['assessment_notes'] ?? '');
                    
                    $stmt = $conn->prepare("UPDATE applications SET status = ?, assessed_by = ?, assessment_date = CURDATE(), assessment_notes = ? WHERE application_id = ?");
                    $stmt->bind_param("sisi", $status, $_SESSION['user_id'], $notes, $application_id);
                    
                    if ($stmt->execute()) {
                        // If accepted, move to HOD review
                        if ($status === 'accepted') {
                            $update_stmt = $conn->prepare("UPDATE applications SET status = 'hod_review' WHERE application_id = ?");
                            $update_stmt->bind_param("i", $application_id);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                        $message = "Application assessed successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Error: " . $stmt->error;
                        $message_type = "error";
                    }
                    $stmt->close();
                } elseif ($_POST['action'] === 'forward_to_hod') {
                    $application_id = intval($_POST['application_id']);
                    $hod_user_id = intval($_POST['hod_user_id'] ?? 0) ?: null;
                    $notes = trim($_POST['forward_notes'] ?? '');
                    
                    // Get application details
                    $result = $conn->query("SELECT application_number, first_name, last_name, program_interest FROM applications WHERE application_id = $application_id");
                    $app = $result->fetch_assoc();
                    
                    if ($app) {
                        // Forward to HOD department
                        $title = "Application Requires HOD Review";
                        $message = "Application #{$app['application_number']} from {$app['first_name']} {$app['last_name']} for {$app['program_interest']} has been forwarded to Head of Department for review.";
                        if ($notes) {
                            $message .= "\n\nNotes: " . $notes;
                        }
                        
                        forwardToDepartment($application_id, 'hod', $title, $message, 'hod_review', 'hod_review');
                        
                        $message = "Application forwarded to HOD successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Application not found!";
                        $message_type = "error";
                    }
                } elseif ($_POST['action'] === 'hod_decision') {
                    $application_id = intval($_POST['application_id']);
                    $decision = $_POST['decision'];
                    $notes = trim($_POST['hod_notes'] ?? '');
                    
                    // Get application details
                    $stmt = $conn->prepare("SELECT application_number, first_name, last_name, program_interest FROM applications WHERE application_id = ?");
                    $stmt->bind_param("i", $application_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $app = $result->fetch_assoc();
                    $stmt->close();
                    
                    $new_status = $decision === 'approved' ? 'accepted' : 'rejected';
                    
                    $stmt = $conn->prepare("UPDATE applications SET hod_decision = ?, hod_decision_by = ?, hod_decision_date = CURDATE(), hod_decision_notes = ?, status = ?, current_department = 'studentservices', last_action_at = CURRENT_TIMESTAMP WHERE application_id = ?");
                    $stmt->bind_param("sissi", $decision, $_SESSION['user_id'], $notes, $new_status, $application_id);
                    
                    if ($stmt->execute()) {
                        $decision_text = $decision === 'approved' ? 'approved' : 'rejected';
                        
                        if ($decision === 'approved') {
                            // Application approved - trigger automated workflow
                            processAcceptedApplication($application_id, $notes);
                            
                            // Log workflow action
                            logWorkflowAction($application_id, 'hod_decision', "HOD decision: approved - Requirements generation triggered", 'hod_review', 'accepted', $notes);
                            
                            $message = "Application approved! Finance and Student Admin Service have been notified. Requirements will be automatically generated.";
                            $message_type = "success";
                        } else {
                            // Application rejected
                            $reject_stmt = $conn->prepare("UPDATE applications SET status = 'ineligible' WHERE application_id = ?");
                            $reject_stmt->bind_param("i", $application_id);
                            $reject_stmt->execute();
                            $reject_stmt->close();
                            $new_status = 'ineligible';
                            
                            // Notify Student Admin Service to generate rejection letter with shortfall details
                            $title = "Application Rejected - Generate Rejection Letter with Shortfall Details";
                            $message_text = "Application #{$app['application_number']} from {$app['first_name']} {$app['last_name']} has been rejected by Head of Department.\n\nAction Required:\n- Generate Rejection Letter for the applicant\n- Include shortfall details and HOD comments\n- Notify applicant via email or phone with rejection letter";
                            if ($notes) {
                                $message_text .= "\n\nHOD Comments/Shortfall Details: " . $notes;
                            }
                            
                            forwardToDepartment($application_id, 'studentservices', $title, $message_text, $new_status, 'hod_decision_complete');
                            
                            // Log workflow action
                            logWorkflowAction($application_id, 'hod_decision', "HOD decision: rejected - Rejection letter generation required", 'hod_review', $new_status, $notes);
                            
                            $message = "Application rejected. Student Admin Service has been notified to generate rejection letter with shortfall details.";
                            $message_type = "success";
                        }
                    } else {
                        $message = "Error: " . $stmt->error;
                        $message_type = "error";
                    }
                    $stmt->close();
                } elseif ($_POST['action'] === 'check_requirements') {
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
                } elseif ($_POST['action'] === 'send_correspondence') {
                    $application_id = intval($_POST['application_id']);
                    $type = $_POST['correspondence_type'];
                    $subject = trim($_POST['subject']);
                    $message_text = trim($_POST['message']);
                    
                    $stmt = $conn->prepare("INSERT INTO correspondence (application_id, correspondence_type, subject, message, sent_by, sent_date, status) VALUES (?, ?, ?, ?, ?, CURDATE(), 'sent')");
                    $stmt->bind_param("isssi", $application_id, $type, $subject, $message_text, $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        // Update application status
                        if ($type === 'invoice') {
                            $update_stmt = $conn->prepare("UPDATE applications SET correspondence_sent = TRUE, invoice_sent = TRUE, correspondence_date = CURDATE(), status = 'correspondence_sent' WHERE application_id = ?");
                            $update_stmt->bind_param("i", $application_id);
                            $update_stmt->execute();
                            $update_stmt->close();
                        } else {
                            $update_stmt = $conn->prepare("UPDATE applications SET correspondence_sent = TRUE, correspondence_date = CURDATE() WHERE application_id = ?");
                            $update_stmt->bind_param("i", $application_id);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                        $message = "Correspondence sent successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Error: " . $stmt->error;
                        $message_type = "error";
                    }
                    $stmt->close();
                } elseif ($_POST['action'] === 'update_checks') {
                    $application_id = intval($_POST['application_id']);
                    $checks = $_POST['checks'] ?? [];
                    
                    // Delete existing checks
                    $delete_stmt = $conn->prepare("DELETE FROM mandatory_checks WHERE application_id = ?");
                    $delete_stmt->bind_param("i", $application_id);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                    
                    // Insert new checks
                    $stmt = $conn->prepare("INSERT INTO mandatory_checks (application_id, check_type, check_name, status) VALUES (?, ?, ?, 'pending')");
                    foreach ($checks as $check) {
                        $check_type = $check['type'];
                        $check_name = $check['name'];
                        $stmt->bind_param("iss", $application_id, $check_type, $check_name);
                        $stmt->execute();
                    }
                    $stmt->close();
                    
                    // Update application status
                    $update_stmt = $conn->prepare("UPDATE applications SET status = 'checks_pending' WHERE application_id = ?");
                    $update_stmt->bind_param("i", $application_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    $message = "Mandatory checks updated!";
                    $message_type = "success";
                } elseif ($_POST['action'] === 'complete_check') {
                    $check_id = $_POST['check_id'];
                    $status = $_POST['check_status'];
                    
                    $stmt = $conn->prepare("UPDATE mandatory_checks SET status = ?, completed_date = CURDATE(), verified_by = ? WHERE check_id = ?");
                    $stmt->bind_param("sii", $status, $_SESSION['user_id'], $check_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Check if all checks are completed
                    $check_stmt = $conn->prepare("SELECT application_id FROM mandatory_checks WHERE check_id = ?");
                    $check_stmt->bind_param("i", $check_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    $check_data = $check_result->fetch_assoc();
                    $app_id = $check_data['application_id'] ?? null;
                    $check_stmt->close();
                    
                    if ($app_id) {
                        $count_stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed FROM mandatory_checks WHERE application_id = ?");
                        $count_stmt->bind_param("i", $app_id);
                        $count_stmt->execute();
                        $count_result = $count_stmt->get_result();
                        $row = $count_result->fetch_assoc();
                        $count_stmt->close();
                        
                        if ($row['total'] > 0 && $row['completed'] == $row['total']) {
                            $update_stmt = $conn->prepare("UPDATE applications SET status = 'checks_completed', enrollment_ready = TRUE WHERE application_id = ?");
                            $update_stmt->bind_param("i", $app_id);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                    }
                    
                    $message = "Check status updated!";
                    $message_type = "success";
                } elseif ($_POST['action'] === 'enroll') {
                    $application_id = intval($_POST['application_id']);
                    
                    // Get application data
                    $stmt = $conn->prepare("SELECT * FROM applications WHERE application_id = ?");
                    $stmt->bind_param("i", $application_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $app = $result->fetch_assoc();
                    $stmt->close();
                    
                    // Check if student already exists (for continuing students)
                    $existing_student_id = null;
                    if (!empty($app['previous_student_id'])) {
                        $existing_student_id = $app['previous_student_id'];
                    } elseif (!empty($app['student_id'])) {
                        $existing_student_id = $app['student_id'];
                    }
                    
                    if ($existing_student_id) {
                        // Reactivate existing student account
                        require_once 'includes/student_account_helper.php';
                        activateStudentAccount($existing_student_id);
                        $student_id = $existing_student_id;
                        
                        // Get student number
                        $student_stmt = $conn->prepare("SELECT student_number FROM students WHERE student_id = ?");
                        $student_stmt->bind_param("i", $student_id);
                        $student_stmt->execute();
                        $student_result = $student_stmt->get_result();
                        $student_data = $student_result->fetch_assoc();
                        $student_stmt->close();
                        $student_number = $student_data['student_number'];
                        
                        // Get account info for username
                        $account_info = getStudentAccount($student_id);
                    } else {
                        // Create new student record
                        $student_number = 'STU-' . date('Y') . '-' . str_pad($application_id, 4, '0', STR_PAD_LEFT);
                        
                        // Get course end date from application or calculate (default 9 months from enrollment)
                        $course_end_date = $app['completion_date'] ?? date('Y-m-d', strtotime('+9 months'));
                        
                        $stmt = $conn->prepare("INSERT INTO students (student_number, first_name, last_name, middle_name, date_of_birth, gender, email, phone, address, city, province, enrollment_date, course_end_date, status, account_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, 'active', 'active')");
                        $stmt->bind_param("ssssssssssss", $student_number, $app['first_name'], $app['last_name'], $app['middle_name'], $app['date_of_birth'], $app['gender'], $app['email'], $app['phone'], $app['address'], $app['city'], $app['province'], $course_end_date);
                        $stmt->execute();
                        $student_id = $conn->insert_id;
                        $stmt->close();
                        
                        // Create student account
                        require_once 'includes/student_account_helper.php';
                        createStudentAccount($student_id, $student_number, $app['email'], $app['phone']);
                        
                        // Get account info for username
                        $account_info = getStudentAccount($student_id);
                        
                        // Add to course history
                        $course_type = $app['course_type'] ?? null;
                        addStudentCourseHistory($student_id, $student_number, $app['program_interest'], date('Y-m-d'), $course_end_date, $course_type, $application_id, 'enrolled');
                    }
                    
                    // Update application
                    $update_stmt = $conn->prepare("UPDATE applications SET enrolled = TRUE, enrollment_date = CURDATE(), student_id = ?, status = 'enrolled' WHERE application_id = ?");
                    $update_stmt->bind_param("ii", $student_id, $application_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    // Generate default password for display
                    $default_password = $student_number . substr(str_pad($student_id, 4, '0', STR_PAD_LEFT), -4);
                    $username = $account_info['username'] ?? $student_number;
                    
                    $message = "Student enrolled successfully! Student Number: " . $student_number . ($existing_student_id ? " (Account Reactivated)" : " (New Account Created)");
                    $message .= "<br><br><strong>Login Credentials:</strong><br>";
                    $message .= "Username: <strong>" . $username . "</strong><br>";
                    $message .= "Default Password: <strong>" . $default_password . "</strong><br><br>";
                    $message .= "<a href='../student_credentials.php?student_id=" . $student_id . "' target='_blank' style='color: #1d4e89; font-weight: bold;'>View Full Credentials →</a>";
                    $message_type = "success";
                } elseif ($_POST['action'] === 'archive') {
                    // Only admin can archive
                    if ($_SESSION['role'] === 'admin') {
                        $application_id = intval($_POST['application_id']);
                        $reason = trim($_POST['archive_reason'] ?? 'Manual archive');
                        $notes = trim($_POST['archive_notes'] ?? '');
                        
                        $result = archiveApplication($application_id, $_SESSION['user_id'], $reason, $notes);
                        
                        if ($result['success']) {
                            $message = $result['message'];
                            $message_type = "success";
                        } else {
                            $message = $result['message'];
                            $message_type = "error";
                        }
                    } else {
                        $message = "Only administrators can archive applications.";
                        $message_type = "error";
                    }
                } elseif ($_POST['action'] === 'notify_applicant') {
                    $application_id = intval($_POST['application_id']);
                    $subject = trim($_POST['notification_subject'] ?? '');
                    $message_text = trim($_POST['notification_message'] ?? '');
                    $correspondence_type = $_POST['correspondence_type'] ?? 'email';
                    $notification_method = $_POST['notification_method'] ?? 'email';
                    
                    // Get application details for placeholder replacement
                    $stmt = $conn->prepare("SELECT first_name, last_name, application_number, program_interest, hod_decision_notes, hod_decision, status FROM applications WHERE application_id = ?");
                    $stmt->bind_param("i", $application_id);
                    $stmt->execute();
                    $app_result = $stmt->get_result();
                    $app_data = $app_result->fetch_assoc();
                    $stmt->close();
                    
                    if ($app_data) {
                        // Replace placeholders in subject and message
                        $subject = str_replace(['{name}', '{application_number}', '{program}'], 
                            [$app_data['first_name'] . ' ' . $app_data['last_name'], $app_data['application_number'], $app_data['program_interest']], 
                            $subject);
                        $message_text = str_replace(['{name}', '{application_number}', '{program}'], 
                            [$app_data['first_name'] . ' ' . $app_data['last_name'], $app_data['application_number'], $app_data['program_interest']], 
                            $message_text);
                        
                        // If rejection letter and HOD notes exist, replace {shortfall} placeholder
                        if ($correspondence_type === 'rejection_letter' && !empty($app_data['hod_decision_notes'])) {
                            $message_text = str_replace('{shortfall}', $app_data['hod_decision_notes'], $message_text);
                            // If {shortfall} placeholder not found, append it to the message
                            if (strpos($message_text, 'SHORTFALL DETAILS:') !== false && strpos($message_text, $app_data['hod_decision_notes']) === false) {
                                $message_text = str_replace('[Please include the specific shortfall details and comments from HOD decision here]', $app_data['hod_decision_notes'], $message_text);
                            }
                        }
                    }
                    
                    if ($subject && $message_text) {
                        $success = false;
                        
                        // Handle both email and phone if selected
                        if ($notification_method === 'both') {
                            // Send email
                            $email_success = sendApplicantNotification($application_id, $subject, $message_text, 'email', $correspondence_type);
                            // Send phone/SMS
                            $phone_success = sendApplicantNotification($application_id, $subject, $message_text, 'phone', $correspondence_type);
                            $success = $email_success || $phone_success;
                        } else {
                            $success = sendApplicantNotification($application_id, $subject, $message_text, $notification_method, $correspondence_type);
                        }
                        
                        if ($success) {
                            $method_text = $notification_method === 'both' ? 'via Email and Phone' : ($notification_method === 'phone' ? 'via Phone/SMS' : 'via Email');
                            $message = "Applicant notified successfully {$method_text}!";
                            $message_type = "success";
                        } else {
                            $message = "Error notifying applicant!";
                            $message_type = "error";
                        }
                    } else {
                        $message = "Subject and message are required!";
                        $message_type = "error";
                    }
                }
            }
            $conn->close();
        }
    }
}

// Get all applications
$conn = getDBConnection();
$applications = [];
$stats = [];
$hods = [];
if ($conn) {
    // Get HOD users for forwarding
    $hod_result = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'hod' AND status = 'active'");
    while ($row = $hod_result->fetch_assoc()) {
        $hods[] = $row;
    }
    $status_filter = $_GET['status'] ?? '';
    $enrollment_ready_filter = $_GET['enrollment_ready'] ?? '';
    
    // Check if application_type column exists
    $col_check = $conn->query("SHOW COLUMNS FROM applications LIKE 'application_type'");
    $has_application_type = $col_check->num_rows > 0;
    
    $query = "SELECT a.*, 
              u1.full_name as assessed_by_name, 
              u2.full_name as hod_decision_by_name,
              s.student_number
              FROM applications a 
              LEFT JOIN users u1 ON a.assessed_by = u1.user_id 
              LEFT JOIN users u2 ON a.hod_decision_by = u2.user_id
              LEFT JOIN students s ON a.student_id = s.student_id";
    
    $where_conditions = [];
    
    // For studentservices role, filter to show only school leaver applications
    if ($_SESSION['role'] === 'studentservices' && $has_application_type) {
        $where_conditions[] = "(a.application_type = 'new_student' OR a.application_type IS NULL)";
    }
    
    if ($status_filter) {
        $where_conditions[] = "a.status = '" . $conn->real_escape_string($status_filter) . "'";
    }
    
    if ($enrollment_ready_filter) {
        $where_conditions[] = "a.enrollment_ready = TRUE";
    }
    
    if (!empty($where_conditions)) {
        $query .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $query .= " ORDER BY a.submitted_at DESC";
    
    $result = $conn->query($query);
    if ($result) {
        $applications = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get statistics
    $stats_query = "SELECT status, COUNT(*) as count FROM applications";
    if ($_SESSION['role'] === 'studentservices' && $has_application_type) {
        $stats_query .= " WHERE (application_type = 'new_student' OR application_type IS NULL)";
    }
    $stats_query .= " GROUP BY status";
    $result = $conn->query($stats_query);
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
  <title>Application Management - Admin</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <style>
    .message { padding: 12px; margin: 10px 0; border-radius: 5px; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
    .stat-card { background: var(--card-bg); padding: 15px; border-radius: 8px; text-align: center; }
    .stat-card .num { font-size: 1.8rem; font-weight: bold; color: var(--primary); }
    .stat-card .label { font-size: 0.9rem; color: #666; }
    .filter-tabs { display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap; }
    .filter-tab { padding: 8px 15px; background: #f0f0f0; border: none; border-radius: 5px; cursor: pointer; }
    .filter-tab.active { background: var(--primary); color: white; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: var(--primary); color: white; }
    .badge { padding: 4px 8px; border-radius: 3px; font-size: 0.85rem; }
    .badge-submitted { background: #17a2b8; color: white; }
    .badge-under_review { background: #ffc107; color: #000; }
    .badge-hod_review { background: #fd7e14; color: white; }
    .badge-accepted { background: #28a745; color: white; }
    .badge-rejected { background: #dc3545; color: white; }
    .badge-enrolled { background: #6f42c1; color: white; }
    .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 0.9rem; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-sm { padding: 5px 10px; font-size: 0.85rem; }
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
    .modal-content { background: white; margin: 50px auto; padding: 25px; width: 90%; max-width: 600px; border-radius: 10px; max-height: 80vh; overflow-y: auto; }
    .form-group { margin-bottom: 15px; }
    label { display: block; margin-bottom: 5px; font-weight: 600; color: var(--primary); }
    input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
  </style>
</head>
<body>
    <div class="dashboard-wrap container">
    <nav class="sidebar" aria-label="Main navigation">
      <div class="brand">
        <a href="<?php 
          if ($_SESSION['role'] === 'admin') echo 'admin_dashboard.php';
          elseif ($_SESSION['role'] === 'hod') echo 'hod_dashboard.php';
          else echo 'student_service_dashboard.php';
        ?>" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
          <img src="../images/pnmc.png" alt="logo"> 
          <strong>PNGMC</strong>
        </a>
      </div>
      <div class="menu">
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <a class="menu-item <?php echo isActive('admin_dashboard.php'); ?>" href="admin_dashboard.php">Dashboard</a>
          <div class="menu-section">Application Management</div>
          <a class="menu-item <?php echo isActive('applications.php'); ?>" href="applications.php">School Leavers</a>
          <a class="menu-item <?php echo isActive('continuing_students.php'); ?>" href="continuing_students.php">Candidates Returning</a>
          <div class="menu-section">Administration</div>
          <a class="menu-item <?php echo isActive('manage_staff.php'); ?>" href="manage_staff.php">Manage Staff</a>
          <a class="menu-item <?php echo isActive('system_settings.php'); ?>" href="system_settings.php">System Settings</a>
          <a class="menu-item <?php echo isActive('reports.php'); ?>" href="reports.php">Reports & Analytics</a>
        <?php elseif ($_SESSION['role'] === 'hod'): ?>
          <a class="menu-item <?php echo isActive('hod_dashboard.php'); ?>" href="hod_dashboard.php">Dashboard</a>
          <div class="menu-section">Applications</div>
          <a class="menu-item <?php echo isActive('applications.php'); ?>" href="applications.php?status=hod_review">Pending Review</a>
          <a class="menu-item <?php echo isActive('workflow_manager.php'); ?>" href="workflow_manager.php">Workflow Manager</a>
          <div class="menu-section">Reports</div>
          <a class="menu-item <?php echo isActive('reports.php'); ?>" href="reports.php">Reports</a>
        <?php else: ?>
          <a class="menu-item <?php echo isActive('student_service_dashboard.php'); ?>" href="student_service_dashboard.php">Dashboard</a>
          <div class="menu-section">Application Processing</div>
          <a class="menu-item <?php echo isActive('applications.php'); ?>" href="applications.php">School Leavers</a>
          <a class="menu-item <?php echo isActive('continuing_students.php'); ?>" href="continuing_students.php">Candidates Returning</a>
          <div class="menu-section">Student Management</div>
          <a class="menu-item <?php echo isActive('student_records.php'); ?>" href="student_records.php">Student Records</a>
          <a class="menu-item <?php echo isActive('advising.php'); ?>" href="advising.php">Advising</a>
          <a class="menu-item <?php echo isActive('support_tickets.php'); ?>" href="support_tickets.php">Support Tickets</a>
        <?php endif; ?>
      </div>
    </nav>

    <div class="content">
      <div class="main-card">
        <h1>Application Management</h1>
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <p class="small" style="color: #666; margin-top: 5px;">
            <strong>📊 Administration View (Read-Only):</strong> You can view all applications and monitor workflow progress. Only <strong>Student Admin Service</strong> can perform actions on applications.
          </p>
        <?php elseif ($_SESSION['role'] === 'studentservices'): ?>
          <p class="small" style="color: #666; margin-top: 5px;">
            <strong>👤 Student Admin Service:</strong> You can perform all actions: review, forward to HOD, generate documents, notify applicants, and enroll students.
          </p>
        <?php endif; ?>
        
        <?php if ($message): ?>
          <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="num"><?php echo $stats['submitted'] ?? 0; ?></div>
            <div class="label">Submitted</div>
          </div>
          <div class="stat-card">
            <div class="num"><?php echo $stats['under_review'] ?? 0; ?></div>
            <div class="label">Under Review</div>
          </div>
          <div class="stat-card">
            <div class="num"><?php echo $stats['hod_review'] ?? 0; ?></div>
            <div class="label">HOD Review</div>
          </div>
          <div class="stat-card">
            <div class="num"><?php echo $stats['accepted'] ?? 0; ?></div>
            <div class="label">Accepted</div>
          </div>
          <div class="stat-card">
            <div class="num"><?php echo $stats['checks_pending'] ?? 0; ?></div>
            <div class="label">Checks Pending</div>
          </div>
          <div class="stat-card">
            <div class="num"><?php echo $stats['enrolled'] ?? 0; ?></div>
            <div class="label">Enrolled</div>
          </div>
        </div>

        <div class="filter-tabs">
          <button class="filter-tab <?php echo !$status_filter ? 'active' : ''; ?>" onclick="window.location.href='applications.php'">All</button>
          <button class="filter-tab <?php echo $status_filter === 'submitted' ? 'active' : ''; ?>" onclick="window.location.href='applications.php?status=submitted'">Submitted</button>
          <button class="filter-tab <?php echo $status_filter === 'under_review' ? 'active' : ''; ?>" onclick="window.location.href='applications.php?status=under_review'">Under Review</button>
          <button class="filter-tab <?php echo $status_filter === 'hod_review' ? 'active' : ''; ?>" onclick="window.location.href='applications.php?status=hod_review'">HOD Review</button>
          <button class="filter-tab <?php echo $status_filter === 'accepted' ? 'active' : ''; ?>" onclick="window.location.href='applications.php?status=accepted'">Accepted</button>
          <button class="filter-tab <?php echo $status_filter === 'checks_pending' ? 'active' : ''; ?>" onclick="window.location.href='applications.php?status=checks_pending'">Checks Pending</button>
        </div>

        <table>
          <thead>
            <tr>
              <th>App #</th>
              <th>Name</th>
              <th>Program</th>
              <th>Grades</th>
              <th>Status</th>
              <th>Submitted</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($applications)): ?>
              <tr><td colspan="7" style="text-align: center;">No applications found.</td></tr>
            <?php else: ?>
              <?php foreach ($applications as $app): ?>
                <tr>
                  <td><?php echo htmlspecialchars($app['application_number'] ?? 'APP-' . $app['application_id']); ?></td>
                  <td><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                  <td><?php echo htmlspecialchars($app['program_interest']); ?></td>
                  <td>
                    <?php 
                      $grades = [];
                      if ($app['maths_grade']) $grades[] = 'Math: ' . $app['maths_grade'];
                      if ($app['physics_grade']) $grades[] = 'Phy: ' . $app['physics_grade'];
                      if ($app['english_grade']) $grades[] = 'Eng: ' . $app['english_grade'];
                      echo htmlspecialchars(implode(', ', $grades));
                    ?>
                  </td>
                  <td>
                    <span class="badge badge-<?php echo str_replace('_', '-', $app['status']); ?>">
                      <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                    </span>
                  </td>
                  <td><?php echo date('Y-m-d', strtotime($app['submitted_at'])); ?></td>
                  <td>
                    <a href="application_details.php?id=<?php echo $app['application_id']; ?>" class="btn btn-primary btn-sm" style="text-decoration: none; display: inline-block;">View Details</a>
                    <?php if ($_SESSION['role'] === 'studentservices' && ($app['status'] === 'submitted' || $app['status'] === 'under_review')): ?>
                      <button onclick="checkRequirements(<?php echo $app['application_id']; ?>)" class="btn btn-primary btn-sm" style="background: #ff9800;">Check Requirements</button>
                      <button onclick="forwardToHOD(<?php echo $app['application_id']; ?>)" class="btn btn-primary btn-sm" style="background: #fd7e14;">Forward to HOD</button>
                      <button onclick="assessApplication(<?php echo $app['application_id']; ?>)" class="btn btn-primary btn-sm">Assess</button>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] === 'hod' && $app['status'] === 'hod_review'): ?>
                      <button onclick="hodDecision(<?php echo $app['application_id']; ?>)" class="btn btn-primary btn-sm">HOD Decision</button>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] === 'studentservices' && ($app['status'] === 'accepted' || $app['status'] === 'rejected' || $app['status'] === 'ineligible')): ?>
                      <button onclick="notifyApplicant(<?php echo $app['application_id']; ?>)" class="btn btn-primary btn-sm" style="background: #28a745;">Notify Applicant</button>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] === 'studentservices' && $app['status'] === 'accepted'): ?>
                      <button onclick="sendCorrespondence(<?php echo $app['application_id']; ?>)" class="btn btn-primary btn-sm">Send Requirements</button>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] === 'studentservices' && $app['status'] === 'correspondence_sent'): ?>
                      <button onclick="manageChecks(<?php echo $app['application_id']; ?>)" class="btn btn-primary btn-sm">Manage Checks</button>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] === 'studentservices' && $app['status'] === 'checks_completed' && $app['enrollment_ready']): ?>
                      <button onclick="enrollStudent(<?php echo $app['application_id']; ?>)" class="btn btn-primary btn-sm">Enroll</button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Assess Application Modal -->
  <div id="assessModal" class="modal">
    <div class="modal-content">
      <h2>Assess Application</h2>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="assess">
        <input type="hidden" name="application_id" id="assess_app_id">
        <div class="form-group">
          <label>Decision *</label>
          <select name="status" required>
            <option value="accepted">Accept - Forward to HOD</option>
            <option value="rejected">Reject - Ineligible</option>
          </select>
        </div>
        <div class="form-group">
          <label>Assessment Notes</label>
          <textarea name="assessment_notes" rows="4"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit Assessment</button>
        <button type="button" onclick="closeModal('assessModal')" class="btn">Cancel</button>
      </form>
    </div>
  </div>

  <!-- HOD Decision Modal -->
  <div id="hodModal" class="modal">
    <div class="modal-content">
      <h2>HOD Decision</h2>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="hod_decision">
        <input type="hidden" name="application_id" id="hod_app_id">
        <div class="form-group">
          <label>Decision *</label>
          <select name="decision" required>
            <option value="approved">Approve - Proceed to Correspondence</option>
            <option value="rejected">Reject - Ineligible</option>
          </select>
        </div>
        <div class="form-group">
          <label>HOD Notes</label>
          <textarea name="hod_notes" rows="4"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit Decision</button>
        <button type="button" onclick="closeModal('hodModal')" class="btn">Cancel</button>
      </form>
    </div>
  </div>

  <!-- Send Correspondence Modal -->
  <div id="correspondenceModal" class="modal">
    <div class="modal-content">
      <h2>Send Correspondence</h2>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="send_correspondence">
        <input type="hidden" name="application_id" id="corr_app_id">
        <div class="form-group">
          <label>Type *</label>
          <select name="correspondence_type" required>
            <option value="requirements_letter">Requirements Letter</option>
            <option value="invoice">Invoice & Requirements</option>
            <option value="acceptance_letter">Acceptance Letter</option>
            <option value="email">Email</option>
          </select>
        </div>
        <div class="form-group">
          <label>Subject *</label>
          <input type="text" name="subject" required>
        </div>
        <div class="form-group">
          <label>Message *</label>
          <textarea name="message" rows="6" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send</button>
        <button type="button" onclick="closeModal('correspondenceModal')" class="btn">Cancel</button>
      </form>
    </div>
  </div>

  <!-- Check Requirements Modal -->
  <div id="requirementsModal" class="modal">
    <div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
      <span style="float: right; cursor: pointer; font-size: 24px; font-weight: bold;" onclick="closeModal('requirementsModal')">&times;</span>
      <h2>Check Requirements</h2>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="check_requirements">
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
          <textarea name="shortfalls" id="req_shortfalls" rows="3" placeholder="List any shortfalls that need to be addressed"></textarea>
        </div>
        
        <div style="display: flex; gap: 10px; margin-top: 20px;">
          <button type="submit" class="btn btn-primary">Submit</button>
          <button type="button" onclick="closeModal('requirementsModal')" class="btn" style="background: #ccc; color: #333;">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Manage Checks Modal -->
  <div id="checksModal" class="modal">
    <div class="modal-content">
      <h2>Mandatory Checks</h2>
      <form method="POST" id="checksForm">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="update_checks">
        <input type="hidden" name="application_id" id="checks_app_id">
        <div id="checksContainer"></div>
        <button type="button" onclick="addCheck()" class="btn" style="margin: 10px 0;">+ Add Check</button>
        <button type="submit" class="btn btn-primary">Save Checks</button>
        <button type="button" onclick="closeModal('checksModal')" class="btn">Cancel</button>
      </form>
    </div>
  </div>

  <!-- Forward to HOD Modal -->
  <div id="forwardHODModal" class="modal">
    <div class="modal-content">
      <h2>Forward to Head of Department</h2>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="forward_to_hod">
        <input type="hidden" name="application_id" id="forward_hod_app_id">
        <div class="form-group">
          <label>Forward Notes (Optional)</label>
          <textarea name="forward_notes" rows="4" placeholder="Add any notes or comments for the HOD..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Forward to HOD</button>
        <button type="button" onclick="closeModal('forwardHODModal')" class="btn">Cancel</button>
      </form>
    </div>
  </div>

  <!-- Notify Applicant Modal -->
  <div id="notifyApplicantModal" class="modal">
    <div class="modal-content">
      <h2>Notify Applicant</h2>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="notify_applicant">
        <input type="hidden" name="application_id" id="notify_app_id">
        <div class="form-group">
          <label>Notification Method *</label>
          <select name="notification_method" id="notification_method" required onchange="updateMessageTemplate()">
            <option value="email">Email</option>
            <option value="phone">Phone/SMS</option>
            <option value="both">Both Email and Phone</option>
          </select>
        </div>
        <div class="form-group">
          <label>Correspondence Type *</label>
          <select name="correspondence_type" id="correspondence_type" required>
            <option value="acceptance_letter">Acceptance Letter</option>
            <option value="rejection_letter">Rejection Letter</option>
            <option value="requirements_letter">Requirements Letter</option>
            <option value="invoice">Invoice & Requirements</option>
            <option value="email">Email</option>
            <option value="phone">Phone Call</option>
          </select>
        </div>
        <div class="form-group">
          <label>Subject *</label>
          <input type="text" name="notification_subject" id="notification_subject" required placeholder="e.g., Application Status Update">
        </div>
        <div class="form-group">
          <label>Message *</label>
          <textarea name="notification_message" id="notification_message" rows="8" required placeholder="Enter the message to send to the applicant..."></textarea>
          <small style="color: #666; font-size: 0.85rem;">Tip: Use placeholders like {name}, {application_number}, {program} - they will be automatically replaced.</small>
        </div>
        <button type="submit" class="btn btn-primary">Send Notification</button>
        <button type="button" onclick="closeModal('notifyApplicantModal')" class="btn">Cancel</button>
      </form>
    </div>
  </div>

  <script>

    function assessApplication(appId) {
      document.getElementById('assess_app_id').value = appId;
      document.getElementById('assessModal').style.display = 'block';
    }

    function forwardToHOD(appId) {
      document.getElementById('forward_hod_app_id').value = appId;
      document.getElementById('forwardHODModal').style.display = 'block';
    }

    function hodDecision(appId) {
      document.getElementById('hod_app_id').value = appId;
      document.getElementById('hodModal').style.display = 'block';
    }

    function notifyApplicant(appId) {
      document.getElementById('notify_app_id').value = appId;
      document.getElementById('notifyApplicantModal').style.display = 'block';
      updateMessageTemplate();
    }
    
    function updateMessageTemplate() {
      const method = document.getElementById('notification_method').value;
      const type = document.getElementById('correspondence_type').value;
      const subjectField = document.getElementById('notification_subject');
      const messageField = document.getElementById('notification_message');
      
      // Get application status to determine if accepted or rejected
      // Note: This would need to be passed from the page or fetched via AJAX
      // For now, we'll use the correspondence type to determine the template
      
      if (type === 'acceptance_letter') {
        subjectField.value = 'Application Accepted - {application_number}';
        messageField.value = 'Dear {name},\n\nCongratulations! Your application for {program} has been accepted by PNG Maritime College.\n\nApplication Number: {application_number}\n\nSCHOOL FEE INVOICE:\nPlease find attached your School Fee Invoice. Payment must be completed as per the invoice instructions.\n\nREQUIRED DOCUMENTS:\nTo proceed with enrollment, please submit the following documents:\n\n1. Medical Clearance Certificate\n2. Police Clearance Certificate\n3. Academic Records Verification\n4. Identity Verification (Birth Certificate/Passport)\n5. Financial Clearance\n\nPlease submit these documents at your earliest convenience to complete your enrollment process.\n\nWe look forward to welcoming you to PNG Maritime College.\n\nBest regards,\nPNG Maritime College';
      } else if (type === 'rejection_letter') {
        subjectField.value = 'Application Status Update - {application_number}';
        messageField.value = 'Dear {name},\n\nThank you for your interest in PNG Maritime College.\n\nWe regret to inform you that your application for {program} (Application #: {application_number}) has not been successful at this time.\n\nSHORTFALL DETAILS:\n[The HOD decision notes/shortfall details will be automatically inserted here when available]\n\nWe encourage you to address these shortfalls and reapply in the future.\n\nBest regards,\nPNG Maritime College';
      } else if (type === 'requirements_letter') {
        subjectField.value = 'Requirements for Application {application_number}';
        messageField.value = 'Dear {name},\n\nYour application for {program} (Application #: {application_number}) has been accepted.\n\nPlease complete the following requirements:\n\n1. Medical Clearance Certificate\n2. Police Clearance Certificate\n3. Academic Records Verification\n4. Identity Verification (Birth Certificate/Passport)\n5. Financial Clearance\n\nPlease submit these documents at your earliest convenience.\n\nBest regards,\nPNG Maritime College';
      } else if (type === 'invoice') {
        subjectField.value = 'Acceptance Letter with School Fee Invoice & Requirements - Application {application_number}';
        messageField.value = 'Dear {name},\n\nCongratulations! Your application for {program} (Application #: {application_number}) has been accepted by PNG Maritime College.\n\nPlease find attached:\n1. Acceptance Letter\n2. School Fee Invoice\n\nPayment must be completed as per the invoice instructions.\n\nREQUIRED DOCUMENTS:\nTo proceed with enrollment, please submit the following documents:\n\n1. Medical Clearance Certificate\n2. Police Clearance Certificate\n3. Academic Records Verification\n4. Identity Verification (Birth Certificate/Passport)\n5. Financial Clearance\n\nPlease submit these documents at your earliest convenience to complete your enrollment process.\n\nWe look forward to welcoming you to PNG Maritime College.\n\nBest regards,\nPNG Maritime College';
      } else {
        subjectField.value = '';
        messageField.value = '';
      }
      
      // Adjust message length for phone/SMS
      if (method === 'phone' && messageField.value.length > 160) {
        messageField.value = messageField.value.substring(0, 157) + '...';
      }
    }

    function sendCorrespondence(appId) {
      document.getElementById('corr_app_id').value = appId;
      document.getElementById('correspondenceModal').style.display = 'block';
    }

    function manageChecks(appId) {
      document.getElementById('checks_app_id').value = appId;
      document.getElementById('checksContainer').innerHTML = '';
      addCheck();
      document.getElementById('checksModal').style.display = 'block';
    }

    let checkCount = 0;
    function addCheck() {
      const container = document.getElementById('checksContainer');
      const div = document.createElement('div');
      div.className = 'form-group';
      div.innerHTML = `
        <div style="display: grid; grid-template-columns: 2fr 3fr 1fr; gap: 10px;">
          <select name="checks[${checkCount}][type]" required>
            <option value="medical">Medical</option>
            <option value="police_clearance">Police Clearance</option>
            <option value="academic_verification">Academic Verification</option>
            <option value="identity_verification">Identity Verification</option>
            <option value="financial_clearance">Financial Clearance</option>
            <option value="other">Other</option>
          </select>
          <input type="text" name="checks[${checkCount}][name]" placeholder="Check Name" required>
          <button type="button" onclick="this.parentElement.parentElement.remove()" class="btn btn-sm">Remove</button>
        </div>
      `;
      container.appendChild(div);
      checkCount++;
    }

    function enrollStudent(appId) {
      if (confirm('Are you sure you want to enroll this student? This will create a student record.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
          <input type="hidden" name="action" value="enroll">
          <input type="hidden" name="application_id" value="${appId}">
        `;
        document.body.appendChild(form);
        form.submit();
      }
    }

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
                  'not_met': '#dc3545',
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
        'not_met': '#dc3545',
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

    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
    }

    window.onclick = function(event) {
      if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
      }
    }
  </script>
</body>
</html>

