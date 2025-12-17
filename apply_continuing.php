<?php
/**
 * Continuing Student Application Form
 * Fixed for Linux compatibility with __DIR__ paths
 */

// Enable output buffering to catch any errors before headers
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use __DIR__ for Linux compatibility
require_once __DIR__ . '/pages/includes/db_config.php';
require_once __DIR__ . '/pages/includes/security_helper.php';

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if POST data was truncated due to size limit (check before CSRF to avoid issues)
    if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $content_length = intval($_SERVER['CONTENT_LENGTH']);
        $post_max_size = ini_get('post_max_size');
        $post_max_bytes = intval($post_max_size) * 1024 * 1024; // Convert MB to bytes
        
        header('Location: index.html?error=upload_size&size=' . round($content_length / 1024 / 1024, 2) . '&limit=' . $post_max_size);
        exit;
    }
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = 'Invalid security token. Please refresh the page and try again.';
        $message_type = 'error';
    } else {
        $conn = getDBConnection();
        if ($conn) {
            // Check if applications table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'applications'");
            if ($table_check && $table_check->num_rows === 0) {
            $message = "Error: Application tables not found. <a href='database/create_app_tables.php' style='color: #1d4e89; text-decoration: underline; font-weight: bold;'>Click here to automatically create the tables</a> or import database/application_workflow_tables.sql manually.";
            $message_type = "error";
            $conn->close();
        } else {
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $middle_name = trim($_POST['middle_name'] ?? '');
            $date_of_birth = $_POST['date_of_birth'] ?? null;
            $gender = $_POST['gender'] ?? null;
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $province = trim($_POST['province'] ?? '');
            
            // Continuing student specific fields
            $application_type = trim($_POST['application_type'] ?? 'continuing_student_next_level');
            $course_category = trim($_POST['course_category'] ?? '');
            $engine_room_course = trim($_POST['engine_room_course'] ?? '');
            $deck_course = trim($_POST['deck_course'] ?? '');
            
            // Determine course_type based on category and specific course
            $course_type = '';
            $program_interest = '';
            if ($course_category === 'Engine Room') {
                $course_type = 'Engineering';
                $program_interest = $engine_room_course;
            } elseif ($course_category === 'Mates (Deck)') {
                $course_type = 'Nautical';
                $program_interest = $deck_course;
            }
            
            $coc_number = trim($_POST['coc_number'] ?? '');
            $coc_expiry_date = $_POST['coc_expiry_date'] ?? null;
            $previous_student_id = !empty($_POST['previous_student_id']) ? intval($_POST['previous_student_id']) : null;
            
            // Try to find existing student account by email, phone, or name
            if (!$previous_student_id) {
                // Search by email (using prepared statement to prevent SQL injection)
                if ($email) {
                    $search_stmt = $conn->prepare("SELECT student_id, student_number FROM students WHERE email = ? LIMIT 1");
                    $search_stmt->bind_param("s", $email);
                    $search_stmt->execute();
                    $search_result = $search_stmt->get_result();
                    if ($search_result && $search_result->num_rows > 0) {
                        $existing = $search_result->fetch_assoc();
                        $previous_student_id = $existing['student_id'];
                    }
                    $search_stmt->close();
                }
                
                // If not found, search by phone (using prepared statement to prevent SQL injection)
                if (!$previous_student_id && $phone) {
                    $search_stmt = $conn->prepare("SELECT student_id, student_number FROM students WHERE phone = ? LIMIT 1");
                    $search_stmt->bind_param("s", $phone);
                    $search_stmt->execute();
                    $search_result = $search_stmt->get_result();
                    if ($search_result && $search_result->num_rows > 0) {
                        $existing = $search_result->fetch_assoc();
                        $previous_student_id = $existing['student_id'];
                    }
                    $search_stmt->close();
                }
                
                // If still not found, search by name and date of birth (using prepared statement to prevent SQL injection)
                if (!$previous_student_id && $date_of_birth) {
                    $search_stmt = $conn->prepare("SELECT student_id, student_number FROM students WHERE first_name = ? AND last_name = ? AND date_of_birth = ? LIMIT 1");
                    $search_stmt->bind_param("sss", $first_name, $last_name, $date_of_birth);
                    $search_stmt->execute();
                    $search_result = $search_stmt->get_result();
                    if ($search_result && $search_result->num_rows > 0) {
                        $existing = $search_result->fetch_assoc();
                        $previous_student_id = $existing['student_id'];
                    }
                    $search_stmt->close();
                }
            }
            
            // File uploads
            $upload_dir = 'uploads/continuing_students/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $nmsa_approval_path = null;
            $sea_service_record_path = null;
            
            // Handle NMSA approval letter upload
            if (isset($_FILES['nmsa_approval_letter']) && $_FILES['nmsa_approval_letter']['error'] === UPLOAD_ERR_OK) {
                $nmsa_file = $_FILES['nmsa_approval_letter'];
                $nmsa_ext = pathinfo($nmsa_file['name'], PATHINFO_EXTENSION);
                $nmsa_filename = 'nmsa_' . time() . '_' . uniqid() . '.' . $nmsa_ext;
                $nmsa_path = $upload_dir . $nmsa_filename;
                if (move_uploaded_file($nmsa_file['tmp_name'], $nmsa_path)) {
                    $nmsa_approval_path = $nmsa_path;
                }
            }
            
            // Handle sea service record upload
            if (isset($_FILES['sea_service_record']) && $_FILES['sea_service_record']['error'] === UPLOAD_ERR_OK) {
                $sea_file = $_FILES['sea_service_record'];
                $sea_ext = pathinfo($sea_file['name'], PATHINFO_EXTENSION);
                $sea_filename = 'sea_service_' . time() . '_' . uniqid() . '.' . $sea_ext;
                $sea_path = $upload_dir . $sea_filename;
                if (move_uploaded_file($sea_file['tmp_name'], $sea_path)) {
                    $sea_service_record_path = $sea_path;
                }
            }
            
            // Generate application number
            $app_count = 0;
            $result = $conn->query("SELECT COUNT(*) as count FROM applications");
            if ($result) {
                $app_count = $result->fetch_assoc()['count'];
            }
            $application_number = 'APP-CONT-' . date('Y') . '-' . str_pad($app_count + 1, 4, '0', STR_PAD_LEFT);
            
            // Check if application_type column exists
            $check_col = $conn->query("SHOW COLUMNS FROM applications LIKE 'application_type'");
            $has_application_type = $check_col && $check_col->num_rows > 0;
            
            // Prepare statement
            if ($has_application_type) {
                $sql = "INSERT INTO applications (application_number, first_name, last_name, middle_name, date_of_birth, gender, email, phone, address, city, province, application_type, course_type, program_interest, nmsa_approval_letter_path, sea_service_record_path, coc_number, coc_expiry_date, previous_student_id, expression_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'submitted')";
            } else {
                $sql = "INSERT INTO applications (application_number, first_name, last_name, middle_name, date_of_birth, gender, email, phone, address, city, province, program_interest, expression_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'submitted')";
            }
            
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                $message = "Prepare failed: " . $conn->error;
                $message_type = "error";
            } else {
                if ($has_application_type) {
                    // If program_interest is empty, set default based on application type
                    if (empty($program_interest)) {
                        $program_interest = ($application_type === 'continuing_student_solas') ? 'SOLAS Refresher / COC Revalidation' : 'Next Level Course';
                    }
                    $types = 'sssssssssssssssssss';
                    $params = [
                        $application_number, $first_name, $last_name, $middle_name, $date_of_birth,
                        $gender, $email, $phone, $address, $city, $province,
                        $application_type, $course_type, $program_interest,
                        $nmsa_approval_path, $sea_service_record_path, $coc_number, $coc_expiry_date, $previous_student_id
                    ];
                } else {
                    $types = 'ssssssssssss';
                    $params = [
                        $application_number, $first_name, $last_name, $middle_name, $date_of_birth,
                        $gender, $email, $phone, $address, $city, $province, 'Returning Student Application'
                    ];
                }
                
                $refs = [];
                foreach ($params as $key => $value) {
                    $refs[$key] = &$params[$key];
                }
                call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $refs));
                
                if ($stmt->execute()) {
                    $application_id = $conn->insert_id;
                    
                    // Link to existing student account if found
                    if ($previous_student_id) {
                        if (file_exists(__DIR__ . '/pages/includes/student_account_helper.php')) {
                            require_once __DIR__ . '/pages/includes/student_account_helper.php';
                            if (function_exists('linkApplicationToStudentAccount')) {
                                @linkApplicationToStudentAccount($application_id, $previous_student_id);
                            }
                        }
                    }
                    
                    // Save uploaded files to application_documents table
                    if (file_exists(__DIR__ . '/pages/includes/document_helper.php')) {
                        require_once __DIR__ . '/pages/includes/document_helper.php';
                        
                        // Save NMSA approval letter if uploaded
                        if ($nmsa_approval_path && isset($_FILES['nmsa_approval_letter'])) {
                            $original_filename = $_FILES['nmsa_approval_letter']['name'];
                            if (function_exists('saveApplicationDocument')) {
                                @saveApplicationDocument($application_id, 'nmsa_approval_letter', $nmsa_approval_path, $original_filename);
                            }
                        }
                        
                        // Save sea service record if uploaded
                        if ($sea_service_record_path && isset($_FILES['sea_service_record'])) {
                            $original_filename = $_FILES['sea_service_record']['name'];
                            if (function_exists('saveApplicationDocument')) {
                                @saveApplicationDocument($application_id, 'sea_service_record', $sea_service_record_path, $original_filename);
                            }
                        }
                    }
                    
                    // Create requirement records (non-critical, continue even if fails)
                    if ($has_application_type) {
                        $table_check_req = $conn->query("SHOW TABLES LIKE 'continuing_student_requirements'");
                        if ($table_check_req && $table_check_req->num_rows > 0) {
                            $req_sql = "INSERT INTO continuing_student_requirements (application_id, requirement_type, requirement_name, status) VALUES (?, ?, ?, 'pending')";
                            $req_stmt = $conn->prepare($req_sql);
                            
                            if ($req_stmt) {
                                $requirements = [
                                    ['nmsa_approval', 'NMSA Approval Letter'],
                                    ['sea_service_record', 'Record of Sea Service'],
                                    ['expression_of_interest', 'Expression of Interest Application']
                                ];
                                
                                foreach ($requirements as $req) {
                                    $req_stmt->bind_param('iss', $application_id, $req[0], $req[1]);
                                    @$req_stmt->execute();
                                }
                                $req_stmt->close();
                            }
                        }
                    }
                    
                    // Success - redirect to confirmation page
                    ob_end_clean(); // Clear any output before redirect
                    $app_num = htmlspecialchars($application_number);
                    header('Location: index.html?success=1&type=continuing&app=' . urlencode($app_num));
                    exit;
                } else {
                    error_log("Apply Continuing Error: " . $stmt->error);
                    $message = "Error submitting application: " . htmlspecialchars($stmt->error);
                    $message_type = "error";
                }
                $stmt->close();
            }
            $conn->close();
        } // End else block from line 44 (table check)
        } // End if ($conn) from line 37
    } // End CSRF check (closes else block from line 35)
} // End POST check (closes if from line 20)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Candidate Returning Application - PNG Maritime College</title>
  <link rel="stylesheet" href="css/sms_styles.css">
  <style>
    .apply-section {
      max-width: 900px;
      margin: 40px auto;
      padding: 40px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .message { padding: 15px; margin: 20px 0; border-radius: 5px; }
    .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
    .form-row.full { grid-template-columns: 1fr; }
    label { display: block; margin-bottom: 5px; font-weight: 600; color: #1d4e89; }
    input, select, textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; box-sizing: border-box; }
    input[type="file"] { padding: 8px; }
    .required { color: red; }
    .btn-submit { width: 100%; padding: 15px; background: #1d4e89; color: white; border: none; border-radius: 5px; font-size: 1.1rem; font-weight: bold; cursor: pointer; margin-top: 20px; }
    .btn-submit:hover { background: #163c6a; }
    .section-title { color: #1d4e89; margin: 30px 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #1d4e89; }
    .info-box { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #1d4e89; }
  </style>
</head>
<body>
  <header>
    <div class="logo">
      <a href="index.html" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
        <img src="images/pnmc.png" alt="PNG Maritime College Logo" class="logo-img">
        PNG Maritime College
      </a>
    </div>
    <a href="pages/login.php" class="login-btn">Login</a>
  </header>

  <section class="apply-section">
    <h1 style="color: #1d4e89; text-align: center; margin-bottom: 10px;">Candidate Returning Application</h1>
    <p style="text-align: center; color: #666; margin-bottom: 30px;">For candidates returning to PNG Maritime College after sea service</p>
    
    <!-- Course Requirements Information -->
    <div style="background: linear-gradient(135deg, #1d4e89 0%, #0f4c75 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
      <h2 style="color: white; margin-top: 0; margin-bottom: 20px; text-align: center; font-size: 1.8rem;">Course Information for Returning Students (Requirements)</h2>
      
      <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 8px; margin-bottom: 20px; backdrop-filter: blur(10px);">
        <h3 style="color: #ffd700; margin-top: 0; margin-bottom: 15px; font-size: 1.3rem;">1. RATING 1 (DECK & ENGINE)</h3>
        <ul style="line-height: 2; margin: 0; padding-left: 20px;">
          <li>Endorsed CERB Pages 1/2, 3/4, 7/8, 9/10, 11/12, etc.</li>
          <li>SOLAS Certificates</li>
          <li>Current Medical</li>
          <li>Letter of Interest for Course to Attend</li>
        </ul>
      </div>
      
      <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 8px; margin-bottom: 20px; backdrop-filter: blur(10px);">
        <h3 style="color: #ffd700; margin-top: 0; margin-bottom: 15px; font-size: 1.3rem;">2. MATE CLASS 5, 4, 3, 2/1 / ENGINE CLASS 5, 4, 3, 2/1</h3>
        <ul style="line-height: 2; margin: 0; padding-left: 20px;">
          <li>Eligibility Letter from NMSA</li>
          <li>Endorsed CERB Pages 1/2, 3/4, 7/8, 9/10, 11/12, etc.</li>
          <li>SOLAS Certificates</li>
          <li>Current Medical</li>
          <li>COC (Certificate of Competency)</li>
          <li>Letter of Interest for Course to Attend</li>
        </ul>
      </div>
      
      <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 8px; margin-bottom: 20px; backdrop-filter: blur(10px);">
        <h3 style="color: #ffd700; margin-top: 0; margin-bottom: 15px; font-size: 1.3rem;">3. ADVANCE SOLAS REFRESHER COURSE / BASIC SOLAS REFRESHER COURSE</h3>
        <ul style="line-height: 2; margin: 0; padding-left: 20px;">
          <li>Expired SOLAS Certificates</li>
          <li>Current Medical</li>
          <li>COC (Certificate of Competency)</li>
          <li>Letter of Interest for Course to Attend</li>
        </ul>
      </div>
      
      <div style="background: rgba(255, 255, 255, 0.15); padding: 15px; border-radius: 8px; margin-top: 20px; border-left: 4px solid #ffd700;">
        <p style="margin: 0; line-height: 1.6;"><strong>Important Note:</strong> All these documents are to be used for official purposes. Therefore, all documents must be properly scanned and forwarded to the PNG Maritime College, Student Administration Office.</p>
      </div>
      
      <div style="background: rgba(255, 255, 255, 0.1); padding: 15px; border-radius: 8px; margin-top: 15px; text-align: center;">
        <p style="margin: 5px 0; font-size: 0.95rem;"><strong>Contact for Submissions:</strong></p>
        <p style="margin: 5px 0; font-size: 0.95rem;">Email: <a href="mailto:smandengat@pngmc.ac.pg" style="color: #ffd700; text-decoration: underline;">smandengat@pngmc.ac.pg</a> - Serah Mandengat (Ms)</p>
        <p style="margin: 5px 0; font-size: 0.95rem;">Phone: (D) 7989 4811 (T) 7702 8990 - Serah Mandengat (Ms)</p>
      </div>
    </div>
    
    <div class="info-box">
      <strong>Application Types:</strong><br>
      • <strong>SOLAS Refresher / COC Revalidation:</strong> For candidates returning to attend SOLAS refresher to revalidate their COC after 5 years<br>
      • <strong>Next Level Course:</strong> For candidates with NMSA approval to attend PNG MC for the next level of course<br><br>
      <strong>Course Categories (for Next Level Course):</strong><br>
      • <strong>Engine Room:</strong> Engine Rating 1, Engineer Class 5, Engineer Class 4, Engineer Class 3, Engineer Class 2/1<br>
      • <strong>Mates (Deck):</strong> Deck Rating 1, Mate Class 5, Mate Class 4, Mate Class 3, Mate Class 2/1
    </div>
    
    <div style="background: #f0f8ff; padding: 20px; border-radius: 8px; margin: 25px 0; border: 2px solid #1d4e89;">
      <h3 style="color: #1d4e89; margin-top: 0; margin-bottom: 15px; text-align: center;">Engine Room Enrollment Forms</h3>
      <p style="text-align: center; color: #666; margin-bottom: 20px;">Click on any course below to access the detailed enrollment application form:</p>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
        <a href="enroll_engine_rating1.php?course=engine_rating_1" style="display: block; padding: 15px; background: white; border: 2px solid #1d4e89; border-radius: 5px; text-decoration: none; color: #1d4e89; text-align: center; font-weight: bold; transition: all 0.3s;" onmouseover="this.style.background='#1d4e89'; this.style.color='white';" onmouseout="this.style.background='white'; this.style.color='#1d4e89';">
          Engine Rating 1<br>
          <small style="font-size: 0.85rem; font-weight: normal; color: #666;">Self-Sponsored</small>
        </a>
        <a href="enroll_engine_rating1.php?course=engineer_class_5" style="display: block; padding: 15px; background: white; border: 2px solid #1d4e89; border-radius: 5px; text-decoration: none; color: #1d4e89; text-align: center; font-weight: bold; transition: all 0.3s;" onmouseover="this.style.background='#1d4e89'; this.style.color='white';" onmouseout="this.style.background='white'; this.style.color='#1d4e89';">
          Engineer Class 5<br>
          <small style="font-size: 0.85rem; font-weight: normal; color: #666;">Company-Sponsored</small>
        </a>
        <a href="enroll_engine_rating1.php?course=engineer_class_4" style="display: block; padding: 15px; background: white; border: 2px solid #1d4e89; border-radius: 5px; text-decoration: none; color: #1d4e89; text-align: center; font-weight: bold; transition: all 0.3s;" onmouseover="this.style.background='#1d4e89'; this.style.color='white';" onmouseout="this.style.background='white'; this.style.color='#1d4e89';">
          Engineer Class 4<br>
          <small style="font-size: 0.85rem; font-weight: normal; color: #666;">Company-Sponsored</small>
        </a>
        <a href="enroll_engine_rating1.php?course=engineer_class_3" style="display: block; padding: 15px; background: white; border: 2px solid #1d4e89; border-radius: 5px; text-decoration: none; color: #1d4e89; text-align: center; font-weight: bold; transition: all 0.3s; background: #fff3cd; border-color: #ffc107;" onmouseover="this.style.background='#ffc107'; this.style.color='white';" onmouseout="this.style.background='#fff3cd'; this.style.color='#1d4e89';">
          Engineer Class 3<br>
          <small style="font-size: 0.85rem; font-weight: normal; color: #666;">Company-Sponsored</small>
        </a>
        <a href="enroll_engine_rating1.php?course=engineer_class_2_1" style="display: block; padding: 15px; background: white; border: 2px solid #1d4e89; border-radius: 5px; text-decoration: none; color: #1d4e89; text-align: center; font-weight: bold; transition: all 0.3s; background: #fff3cd; border-color: #ffc107;" onmouseover="this.style.background='#ffc107'; this.style.color='white';" onmouseout="this.style.background='#fff3cd'; this.style.color='#1d4e89';">
          Engineer Class 2/1<br>
          <small style="font-size: 0.85rem; font-weight: normal; color: #666;">Company-Sponsored</small>
        </a>
      </div>
      <p style="text-align: center; margin-top: 15px; color: #666; font-size: 0.9rem;">
        <strong>Note:</strong> These enrollment forms contain detailed course information, entry requirements, and all necessary fields for enrollment.
      </p>
    </div>
    
    <div style="background: #f0f8ff; padding: 20px; border-radius: 8px; margin: 25px 0; border: 2px solid #1d4e89;">
      <h3 style="color: #1d4e89; margin-top: 0; margin-bottom: 15px; text-align: center;">Mates (Deck) Enrollment Forms</h3>
      <p style="text-align: center; color: #666; margin-bottom: 20px;">Click on any course below to access the detailed enrollment application form:</p>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
        <a href="enroll_deck_rating1.php?course=deck_rating_1" style="display: block; padding: 15px; background: white; border: 2px solid #1d4e89; border-radius: 5px; text-decoration: none; color: #1d4e89; text-align: center; font-weight: bold; transition: all 0.3s;" onmouseover="this.style.background='#1d4e89'; this.style.color='white';" onmouseout="this.style.background='white'; this.style.color='#1d4e89';">
          Deck Rating 1<br>
          <small style="font-size: 0.85rem; font-weight: normal; color: #666;">Self-Sponsored</small>
        </a>
        <a href="enroll_deck_rating1.php?course=mate_class_5" style="display: block; padding: 15px; background: white; border: 2px solid #1d4e89; border-radius: 5px; text-decoration: none; color: #1d4e89; text-align: center; font-weight: bold; transition: all 0.3s;" onmouseover="this.style.background='#1d4e89'; this.style.color='white';" onmouseout="this.style.background='white'; this.style.color='#1d4e89';">
          Mate Class 5<br>
          <small style="font-size: 0.85rem; font-weight: normal; color: #666;">Company-Sponsored</small>
        </a>
        <a href="enroll_deck_rating1.php?course=mate_class_4" style="display: block; padding: 15px; background: white; border: 2px solid #1d4e89; border-radius: 5px; text-decoration: none; color: #1d4e89; text-align: center; font-weight: bold; transition: all 0.3s;" onmouseover="this.style.background='#1d4e89'; this.style.color='white';" onmouseout="this.style.background='white'; this.style.color='#1d4e89';">
          Mate Class 4<br>
          <small style="font-size: 0.85rem; font-weight: normal; color: #666;">Company-Sponsored</small>
        </a>
        <a href="enroll_deck_rating1.php?course=mate_class_3" style="display: block; padding: 15px; background: white; border: 2px solid #1d4e89; border-radius: 5px; text-decoration: none; color: #1d4e89; text-align: center; font-weight: bold; transition: all 0.3s; background: #fff3cd; border-color: #ffc107;" onmouseover="this.style.background='#ffc107'; this.style.color='white';" onmouseout="this.style.background='#fff3cd'; this.style.color='#1d4e89';">
          Mate Class 3<br>
          <small style="font-size: 0.85rem; font-weight: normal; color: #666;">Company-Sponsored</small>
        </a>
        <a href="enroll_deck_rating1.php?course=mate_class_2_1" style="display: block; padding: 15px; background: white; border: 2px solid #1d4e89; border-radius: 5px; text-decoration: none; color: #1d4e89; text-align: center; font-weight: bold; transition: all 0.3s; background: #fff3cd; border-color: #ffc107;" onmouseover="this.style.background='#ffc107'; this.style.color='white';" onmouseout="this.style.background='#fff3cd'; this.style.color='#1d4e89';">
          Mate Class 2/1<br>
          <small style="font-size: 0.85rem; font-weight: normal; color: #666;">Company-Sponsored</small>
        </a>
      </div>
      <p style="text-align: center; margin-top: 15px; color: #666; font-size: 0.9rem;">
        <strong>Note:</strong> These enrollment forms contain detailed course information, entry requirements, and all necessary fields for enrollment.
      </p>
    </div>
    
    <?php if ($message): ?>
      <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
  </section>

  <footer>
    &copy; 2025 PNG Maritime College. All rights reserved.
  </footer>
</body>
</html>

