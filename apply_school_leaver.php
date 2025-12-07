<?php
session_start();
require_once 'pages/includes/db_config.php';

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if POST data was truncated due to size limit
    if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $content_length = intval($_SERVER['CONTENT_LENGTH']);
        $post_max_size = ini_get('post_max_size');
        $post_max_bytes = intval($post_max_size) * 1024 * 1024; // Convert MB to bytes
        
        header('Location: index.html?error=upload_size&size=' . round($content_length / 1024 / 1024, 2) . '&limit=' . $post_max_size);
        exit;
    }
    
    $conn = getDBConnection();
    if ($conn) {
        // Check if applications table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'applications'");
        if ($table_check->num_rows === 0) {
            $message = "Error: Application tables not found. <a href='database/create_app_tables.php' style='color: #1d4e89; text-decoration: underline; font-weight: bold;'>Click here to automatically create the tables</a> or import database/application_workflow_tables.sql manually.";
            $message_type = "error";
            $conn->close();
        } else {
            // Personal Information
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $middle_name = trim($_POST['middle_name'] ?? '');
            $date_of_birth = $_POST['date_of_birth'] ?? null;
            $gender = $_POST['gender'] ?? null;
            $place_of_birth = trim($_POST['place_of_birth'] ?? '');
            $nationality = trim($_POST['nationality'] ?? 'Papua New Guinea');
            $nid_number = trim($_POST['nid_number'] ?? '');
            
            // Contact Information
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $province = trim($_POST['province'] ?? '');
            $postal_code = trim($_POST['postal_code'] ?? '');
            
            // Next of Kin
            $next_of_kin_name = trim($_POST['next_of_kin_name'] ?? '');
            $next_of_kin_relationship = trim($_POST['next_of_kin_relationship'] ?? '');
            $next_of_kin_phone = trim($_POST['next_of_kin_phone'] ?? '');
            $next_of_kin_address = trim($_POST['next_of_kin_address'] ?? '');
            
            // Academic Information
            $education_level = trim($_POST['education_level'] ?? '');
            $school_name = trim($_POST['school_name'] ?? '');
            $school_address = trim($_POST['school_address'] ?? '');
            $year_completed = trim($_POST['year_completed'] ?? '');
            $grade_12_passed = isset($_POST['grade_12_passed']) ? 1 : 0;
            
            // Subject Grades
            $maths_grade = trim($_POST['maths_grade'] ?? '');
            $physics_grade = trim($_POST['physics_grade'] ?? '');
            $english_grade = trim($_POST['english_grade'] ?? '');
            $chemistry_grade = trim($_POST['chemistry_grade'] ?? '');
            $overall_gpa = $_POST['overall_gpa'] ?? null;
            
            // Program Selection
            $program_type = trim($_POST['program_type'] ?? '');
            $program_interest = '';
            if ($program_type === 'deck_officers') {
                $program_interest = 'Deck Officers';
            } elseif ($program_type === 'marine_engineers') {
                $program_interest = 'Marine Engineers';
            } elseif ($program_type === 'gp_rating') {
                $program_interest = 'GP Rating 2';
            }
            
            // Validate declaration
            if (!isset($_POST['declaration_agreed']) || $_POST['declaration_agreed'] != '1') {
                $message = "You must agree to the declaration to submit your application.";
                $message_type = "error";
                $conn->close();
            } else {
                // Generate application number
                $app_count = 0;
                $result = $conn->query("SELECT COUNT(*) as count FROM applications");
                if ($result) {
                    $app_count = $result->fetch_assoc()['count'];
                }
                $application_number = 'APP-' . date('Y') . '-' . str_pad($app_count + 1, 4, '0', STR_PAD_LEFT);
                
                // Handle file uploads
                $upload_dir = 'uploads/school_leaver_documents/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $max_file_size = 5 * 1024 * 1024; // 5MB
                $uploaded_files = [];
                
                // Upload academic certificates
                if (isset($_FILES['academic_certificates'])) {
                    $cert_files = [];
                    foreach ($_FILES['academic_certificates']['name'] as $key => $name) {
                        if ($_FILES['academic_certificates']['error'][$key] === UPLOAD_ERR_OK) {
                            $file_ext = pathinfo($name, PATHINFO_EXTENSION);
                            $new_filename = $application_number . '_cert_' . $key . '_' . time() . '.' . $file_ext;
                            $target_path = $upload_dir . $new_filename;
                            if (move_uploaded_file($_FILES['academic_certificates']['tmp_name'][$key], $target_path)) {
                                $cert_files[] = $target_path;
                            }
                        }
                    }
                    $uploaded_files['academic_certificates'] = !empty($cert_files) ? implode('|', $cert_files) : null;
                }
                
                // Upload academic transcripts
                if (isset($_FILES['academic_transcripts'])) {
                    $transcript_files = [];
                    foreach ($_FILES['academic_transcripts']['name'] as $key => $name) {
                        if ($_FILES['academic_transcripts']['error'][$key] === UPLOAD_ERR_OK) {
                            $file_ext = pathinfo($name, PATHINFO_EXTENSION);
                            $new_filename = $application_number . '_transcript_' . $key . '_' . time() . '.' . $file_ext;
                            $target_path = $upload_dir . $new_filename;
                            if (move_uploaded_file($_FILES['academic_transcripts']['tmp_name'][$key], $target_path)) {
                                $transcript_files[] = $target_path;
                            }
                        }
                    }
                    $uploaded_files['academic_transcripts'] = !empty($transcript_files) ? implode('|', $transcript_files) : null;
                }
                
                // Upload reference letters
                if (isset($_FILES['reference_letters'])) {
                    $ref_files = [];
                    foreach ($_FILES['reference_letters']['name'] as $key => $name) {
                        if ($_FILES['reference_letters']['error'][$key] === UPLOAD_ERR_OK) {
                            $file_ext = pathinfo($name, PATHINFO_EXTENSION);
                            $new_filename = $application_number . '_ref_' . $key . '_' . time() . '.' . $file_ext;
                            $target_path = $upload_dir . $new_filename;
                            if (move_uploaded_file($_FILES['reference_letters']['tmp_name'][$key], $target_path)) {
                                $ref_files[] = $target_path;
                            }
                        }
                    }
                    $uploaded_files['reference_letters'] = !empty($ref_files) ? implode('|', $ref_files) : null;
                }
                
                // Upload CV
                if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
                    $file_ext = pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION);
                    $new_filename = $application_number . '_cv_' . time() . '.' . $file_ext;
                    $target_path = $upload_dir . $new_filename;
                    if (move_uploaded_file($_FILES['cv']['tmp_name'], $target_path)) {
                        $uploaded_files['cv'] = $target_path;
                    }
                }
                
                // Upload ID document
                if (isset($_FILES['id_document']) && $_FILES['id_document']['error'] === UPLOAD_ERR_OK) {
                    $file_ext = pathinfo($_FILES['id_document']['name'], PATHINFO_EXTENSION);
                    $new_filename = $application_number . '_id_' . time() . '.' . $file_ext;
                    $target_path = $upload_dir . $new_filename;
                    if (move_uploaded_file($_FILES['id_document']['tmp_name'], $target_path)) {
                        $uploaded_files['id_document'] = $target_path;
                    }
                }
                
                // Ensure overall_gpa is a valid number
                $overall_gpa_value = (!empty($overall_gpa) && is_numeric($overall_gpa)) ? floatval($overall_gpa) : null;
                
                // Check which columns exist in the database
                $check_education_level = $conn->query("SHOW COLUMNS FROM applications LIKE 'education_level'");
                $has_education_level = $check_education_level && $check_education_level->num_rows > 0;
                
                // Build SQL query with available columns
                $columns = ['application_number', 'first_name', 'last_name', 'middle_name', 'date_of_birth', 'gender', 'email', 'phone', 'address', 'city', 'province', 'program_interest', 'grade_12_passed', 'maths_grade', 'physics_grade', 'english_grade', 'overall_gpa', 'expression_date', 'status'];
                $values = ['?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', 'CURDATE()', "'submitted'"];
                $params = [$application_number, $first_name, $last_name, $middle_name, $date_of_birth, $gender, $email, $phone, $address, $city, $province, $program_interest, $grade_12_passed, $maths_grade, $physics_grade, $english_grade, $overall_gpa_value];
                $types = 'ssssssssssssisssd';
                
                if ($has_education_level) {
                    array_splice($columns, 11, 0, 'education_level');
                    array_splice($values, 11, 0, '?');
                    array_splice($params, 11, 0, $education_level);
                    $types = 'sssssssssssssisssd';
                }
                
                $sql = "INSERT INTO applications (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
                $stmt = $conn->prepare($sql);
                
                if (!$stmt) {
                    $message = "Error preparing statement: " . $conn->error;
                    $message_type = "error";
                } else {
                    // Bind parameters
                    $refs = [];
                    foreach ($params as $key => $value) {
                        $refs[$key] = &$params[$key];
                    }
                    call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $refs));
                    
                    if ($stmt->execute()) {
                        $application_id = $conn->insert_id;
                        
                        // Automatically create requirements for school leaver applications
                        $table_check = $conn->query("SHOW TABLES LIKE 'continuing_student_requirements'");
                        if ($table_check->num_rows > 0) {
                            $req_sql = "INSERT INTO continuing_student_requirements (application_id, requirement_type, requirement_name, status) VALUES (?, ?, ?, 'pending')";
                            $req_stmt = $conn->prepare($req_sql);
                            
                            if ($req_stmt) {
                                // Default requirements for school leaver applications
                                $requirements = [
                                    ['academic_prerequisites', 'Academic Prerequisites (Grade 12 Certificate)'],
                                    ['academic_prerequisites', 'Academic Transcript Verification'],
                                    ['financial_clearance', 'Financial Clearance'],
                                    ['other', 'Medical Certificate'],
                                    ['other', 'Police Clearance'],
                                    ['other', 'Identity Verification (Birth Certificate)']
                                ];
                                
                                foreach ($requirements as $req) {
                                    $req_stmt->bind_param('iss', $application_id, $req[0], $req[1]);
                                    $req_stmt->execute();
                                }
                                $req_stmt->close();
                            }
                        }
                        
                        $app_num = htmlspecialchars($application_number);
                        header('Location: index.html?success=1&type=school_leaver&app=' . urlencode($app_num));
                        exit;
                    } else {
                        $message = "Error submitting application: " . $stmt->error;
                        $message_type = "error";
                    }
                    $stmt->close();
                }
                $conn->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>School Leaver Application - PNG Maritime College</title>
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
    .form-header {
      text-align: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 3px solid #1d4e89;
    }
    .form-header h1 {
      color: #1d4e89;
      margin: 0 0 10px 0;
      font-size: 2rem;
    }
    .form-header .subtitle {
      color: #666;
      font-size: 1.1rem;
    }
    .message {
      padding: 15px 20px;
      margin: 20px 0;
      border-radius: 5px;
      line-height: 1.6;
    }
    .success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    .error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    .form-section {
      margin: 30px 0;
      padding: 20px;
      background: #f9f9f9;
      border-radius: 8px;
      border-left: 4px solid #1d4e89;
    }
    .section-title {
      color: #1d4e89;
      margin: 0 0 20px 0;
      padding-bottom: 10px;
      border-bottom: 2px solid #1d4e89;
      font-size: 1.3rem;
    }
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 15px;
    }
    .form-row.full {
      grid-template-columns: 1fr;
    }
    .form-row.three {
      grid-template-columns: 1fr 1fr 1fr;
    }
    label {
      display: block;
      margin-bottom: 5px;
      font-weight: 600;
      color: #333;
      font-size: 0.95rem;
    }
    .required {
      color: red;
      font-weight: bold;
    }
    input[type="text"],
    input[type="email"],
    input[type="tel"],
    input[type="date"],
    input[type="number"],
    input[type="file"],
    select,
    textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 1rem;
      font-family: inherit;
      box-sizing: border-box;
    }
    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: #1d4e89;
      box-shadow: 0 0 0 2px rgba(29, 78, 137, 0.1);
    }
    input[type="file"] {
      padding: 8px;
      background: white;
      cursor: pointer;
    }
    input[type="file"]:focus {
      outline: none;
      border-color: #1d4e89;
      box-shadow: 0 0 0 2px rgba(29, 78, 137, 0.1);
    }
    small {
      display: block;
      margin-top: 5px;
      font-size: 0.85rem;
    }
    textarea {
      resize: vertical;
      min-height: 80px;
    }
    .checkbox-group {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .checkbox-group input[type="checkbox"] {
      width: auto;
    }
    .btn-submit {
      width: 100%;
      padding: 15px;
      background: #1d4e89;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 1.1rem;
      font-weight: bold;
      cursor: pointer;
      margin-top: 30px;
      transition: background 0.3s;
    }
    .btn-submit:hover {
      background: #163c6a;
    }
    .info-box {
      background: #e7f3ff;
      padding: 15px;
      border-radius: 5px;
      margin: 20px 0;
      border-left: 4px solid #1d4e89;
      font-size: 0.95rem;
    }
    .program-type-badge {
      display: inline-block;
      padding: 5px 15px;
      background: #1d4e89;
      color: white;
      border-radius: 20px;
      font-size: 0.9rem;
      margin-top: 10px;
    }
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
    <div class="form-header">
      <h1>PNG MARITIME COLLEGE</h1>
      <div class="subtitle">SCHOOL LEAVER APPLICATION FORM</div>
      <div class="info-box" style="margin-top: 20px; text-align: left;">
        <strong>Application Types Available:</strong><br>
        • <strong>Deck Officers</strong> - For Grade 12 students interested in navigation and ship operations<br>
        • <strong>Marine Engineers</strong> - For Grade 12 students interested in ship engineering and maintenance<br>
        • <strong>GP Rating 2</strong> - For Grade 10 students interested in general purpose rating
      </div>
    </div>
    
    <?php if ($message): ?>
      <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" id="applicationForm" enctype="multipart/form-data">
      <!-- Program Selection -->
      <div class="form-section">
        <h2 class="section-title">Program Selection</h2>
        <div class="form-row full">
          <div>
            <label>Select Program Type <span class="required">*</span></label>
            <select name="program_type" id="program_type" required onchange="updateProgramDisplay()">
              <option value="">-- Select Program Type --</option>
              <option value="deck_officers" <?php echo (isset($_POST['program_type']) && $_POST['program_type'] === 'deck_officers') ? 'selected' : ''; ?>>Deck Officers</option>
              <option value="marine_engineers" <?php echo (isset($_POST['program_type']) && $_POST['program_type'] === 'marine_engineers') ? 'selected' : ''; ?>>Marine Engineers</option>
              <option value="gp_rating" <?php echo (isset($_POST['program_type']) && $_POST['program_type'] === 'gp_rating') ? 'selected' : ''; ?>>GP Rating 2</option>
            </select>
            <div id="program_display" class="program-type-badge" style="display: none;"></div>
          </div>
        </div>
        
        <!-- Program-Specific Information -->
        <div id="program_info" style="display: none; margin-top: 20px;">
          <div class="info-box" id="program_info_content"></div>
        </div>
      </div>

      <!-- Personal Information -->
      <div class="form-section">
        <h2 class="section-title">Personal Information</h2>
        <div class="form-row">
          <div>
            <label>Surname (Last Name) <span class="required">*</span></label>
            <input type="text" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
          </div>
          <div>
            <label>First Name <span class="required">*</span></label>
            <input type="text" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Middle Name</label>
            <input type="text" name="middle_name" value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">
          </div>
          <div>
            <label>Date of Birth <span class="required">*</span></label>
            <input type="date" name="date_of_birth" required value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Gender <span class="required">*</span></label>
            <select name="gender" required>
              <option value="">Select Gender</option>
              <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
              <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
              <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
            </select>
          </div>
          <div>
            <label>Place of Birth</label>
            <input type="text" name="place_of_birth" value="<?php echo htmlspecialchars($_POST['place_of_birth'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Marital Status</label>
            <select name="marital_status">
              <option value="">Select Marital Status</option>
              <option value="Single" <?php echo (isset($_POST['marital_status']) && $_POST['marital_status'] === 'Single') ? 'selected' : ''; ?>>Single</option>
              <option value="Married" <?php echo (isset($_POST['marital_status']) && $_POST['marital_status'] === 'Married') ? 'selected' : ''; ?>>Married</option>
              <option value="Divorced" <?php echo (isset($_POST['marital_status']) && $_POST['marital_status'] === 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
              <option value="Widowed" <?php echo (isset($_POST['marital_status']) && $_POST['marital_status'] === 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
            </select>
          </div>
          <div>
            <label>Do you consider yourself a person with a Disability?</label>
            <select name="disability">
              <option value="">Select</option>
              <option value="Yes" <?php echo (isset($_POST['disability']) && $_POST['disability'] === 'Yes') ? 'selected' : ''; ?>>Yes</option>
              <option value="No" <?php echo (isset($_POST['disability']) && $_POST['disability'] === 'No') ? 'selected' : ''; ?>>No</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Home Province</label>
            <input type="text" name="home_province" value="<?php echo htmlspecialchars($_POST['home_province'] ?? ''); ?>">
          </div>
          <div>
            <label>Home District</label>
            <input type="text" name="home_district" value="<?php echo htmlspecialchars($_POST['home_district'] ?? ''); ?>">
          </div>
        </div>
      </div>

      <!-- Contact Information -->
      <div class="form-section">
        <h2 class="section-title">Contact Information</h2>
        <div class="form-row">
          <div>
            <label>Mobile Number <span class="required">*</span></label>
            <input type="tel" name="phone" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
          </div>
          <div>
            <label>Alternate Mobile Number</label>
            <input type="tel" name="alternate_phone" value="<?php echo htmlspecialchars($_POST['alternate_phone'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Email Address <span class="required">*</span></label>
            <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row full">
          <div>
            <label>Current Residential Address <span class="required">*</span></label>
            <textarea name="address" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Village</label>
            <input type="text" name="village" value="<?php echo htmlspecialchars($_POST['village'] ?? ''); ?>">
          </div>
          <div>
            <label>Province</label>
            <input type="text" name="province" value="<?php echo htmlspecialchars($_POST['province'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row full">
          <div>
            <label>Postal Address</label>
            <textarea name="postal_address" rows="2"><?php echo htmlspecialchars($_POST['postal_address'] ?? ''); ?></textarea>
          </div>
        </div>
      </div>

      <!-- Emergency Contact Details -->
      <div class="form-section">
        <h2 class="section-title">Emergency Contact Details</h2>
        <div class="form-row">
          <div>
            <label>Name of the Emergency Contact Person <span class="required">*</span></label>
            <input type="text" name="next_of_kin_name" required value="<?php echo htmlspecialchars($_POST['next_of_kin_name'] ?? ''); ?>">
          </div>
          <div>
            <label>Relationship to You <span class="required">*</span></label>
            <select name="next_of_kin_relationship" required>
              <option value="">Select Relationship</option>
              <option value="Parent" <?php echo (isset($_POST['next_of_kin_relationship']) && $_POST['next_of_kin_relationship'] === 'Parent') ? 'selected' : ''; ?>>Parent</option>
              <option value="Guardian" <?php echo (isset($_POST['next_of_kin_relationship']) && $_POST['next_of_kin_relationship'] === 'Guardian') ? 'selected' : ''; ?>>Guardian</option>
              <option value="Sibling" <?php echo (isset($_POST['next_of_kin_relationship']) && $_POST['next_of_kin_relationship'] === 'Sibling') ? 'selected' : ''; ?>>Sibling</option>
              <option value="Spouse" <?php echo (isset($_POST['next_of_kin_relationship']) && $_POST['next_of_kin_relationship'] === 'Spouse') ? 'selected' : ''; ?>>Spouse</option>
              <option value="Other" <?php echo (isset($_POST['next_of_kin_relationship']) && $_POST['next_of_kin_relationship'] === 'Other') ? 'selected' : ''; ?>>Other</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Contact Number <span class="required">*</span></label>
            <input type="tel" name="next_of_kin_phone" required value="<?php echo htmlspecialchars($_POST['next_of_kin_phone'] ?? ''); ?>">
          </div>
          <div>
            <label>Email Address</label>
            <input type="email" name="next_of_kin_email" value="<?php echo htmlspecialchars($_POST['next_of_kin_email'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row full">
          <div>
            <label>Residential Address</label>
            <textarea name="next_of_kin_address" rows="2"><?php echo htmlspecialchars($_POST['next_of_kin_address'] ?? ''); ?></textarea>
          </div>
        </div>
      </div>

      <!-- Education Background -->
      <div class="form-section">
        <h2 class="section-title">Education Background</h2>
        <p style="margin-bottom: 15px; color: #666; font-size: 0.9rem;"><em>Please provide details of the highest level of study you have completed</em></p>
        <div class="form-row">
          <div>
            <label>1. Name of the Institution</label>
            <input type="text" name="school_name" value="<?php echo htmlspecialchars($_POST['school_name'] ?? ''); ?>">
          </div>
          <div>
            <label>2. Highest Qualification</label>
            <select name="education_level" id="education_level">
              <option value="">Select Qualification</option>
              <option value="Grade 10" <?php echo (isset($_POST['education_level']) && $_POST['education_level'] === 'Grade 10') ? 'selected' : ''; ?>>Grade 10 Certificate</option>
              <option value="Grade 12" <?php echo (isset($_POST['education_level']) && $_POST['education_level'] === 'Grade 12') ? 'selected' : ''; ?>>Grade 12 Certificate</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>3. Year Graduated</label>
            <input type="text" name="year_completed" placeholder="e.g., 2023" value="<?php echo htmlspecialchars($_POST['year_completed'] ?? ''); ?>">
          </div>
        </div>
        <div class="info-box" style="margin-top: 15px;">
          <strong>Note:</strong> Copies of Certificates gained must accompany this application: DO NOT SEND ORIGINALS.
        </div>
        <div class="form-row" id="grade_12_section" style="margin-top: 15px;">
          <div class="checkbox-group">
            <input type="checkbox" name="grade_12_passed" value="1" id="grade_12_passed" <?php echo (isset($_POST['grade_12_passed'])) ? 'checked' : ''; ?>>
            <label for="grade_12_passed" style="margin: 0;">I have passed Grade 12</label>
          </div>
        </div>
      </div>

      <!-- Subject Grades -->
      <div class="form-section">
        <h2 class="section-title">Subject Grades</h2>
        <div class="form-row">
          <div>
            <label>Mathematics Grade</label>
            <input type="text" name="maths_grade" placeholder="e.g., A, B, C, D" value="<?php echo htmlspecialchars($_POST['maths_grade'] ?? ''); ?>">
          </div>
          <div>
            <label>Physics Grade</label>
            <input type="text" name="physics_grade" placeholder="e.g., A, B, C, D" value="<?php echo htmlspecialchars($_POST['physics_grade'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>English Grade</label>
            <input type="text" name="english_grade" placeholder="e.g., A, B, C, D" value="<?php echo htmlspecialchars($_POST['english_grade'] ?? ''); ?>">
          </div>
          <div>
            <label>Chemistry Grade</label>
            <input type="text" name="chemistry_grade" placeholder="e.g., A, B, C, D" value="<?php echo htmlspecialchars($_POST['chemistry_grade'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Overall GPA</label>
            <input type="number" name="overall_gpa" step="0.01" min="0" max="4" placeholder="e.g., 3.5" value="<?php echo htmlspecialchars($_POST['overall_gpa'] ?? ''); ?>">
          </div>
        </div>
      </div>

      <!-- Sponsor Details -->
      <div class="form-section">
        <h2 class="section-title">Sponsor Details</h2>
        <div class="form-row">
          <div>
            <label>Name</label>
            <input type="text" name="sponsor_name" value="<?php echo htmlspecialchars($_POST['sponsor_name'] ?? ''); ?>">
          </div>
          <div>
            <label>Surname</label>
            <input type="text" name="sponsor_surname" value="<?php echo htmlspecialchars($_POST['sponsor_surname'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row full">
          <div>
            <label>Postal Address</label>
            <textarea name="sponsor_postal_address" rows="2"><?php echo htmlspecialchars($_POST['sponsor_postal_address'] ?? ''); ?></textarea>
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Email Address</label>
            <input type="email" name="sponsor_email" value="<?php echo htmlspecialchars($_POST['sponsor_email'] ?? ''); ?>">
          </div>
          <div>
            <label>Mobile</label>
            <input type="tel" name="sponsor_mobile" value="<?php echo htmlspecialchars($_POST['sponsor_mobile'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Landline</label>
            <input type="tel" name="sponsor_landline" value="<?php echo htmlspecialchars($_POST['sponsor_landline'] ?? ''); ?>">
          </div>
        </div>
      </div>

      <!-- Refund Customer Details -->
      <div class="form-section">
        <h2 class="section-title">Refund Customer Details</h2>
        <p style="margin-bottom: 15px; color: #666; font-size: 0.9rem;"><em>If the student is suspended, terminated, or decided to withdraw from studies, please nominate account details for the funds to be reimbursed.</em></p>
        <div class="form-row">
          <div>
            <label>1. Name</label>
            <input type="text" name="refund_name" value="<?php echo htmlspecialchars($_POST['refund_name'] ?? ''); ?>">
          </div>
          <div>
            <label>2. Surname</label>
            <input type="text" name="refund_surname" value="<?php echo htmlspecialchars($_POST['refund_surname'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>3. Bank</label>
            <input type="text" name="refund_bank" value="<?php echo htmlspecialchars($_POST['refund_bank'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Account Type</label>
            <div style="display: flex; gap: 20px; margin-top: 10px;">
              <label style="display: flex; align-items: center; gap: 5px; font-weight: normal;">
                <input type="radio" name="refund_account_type" value="Cheque" <?php echo (isset($_POST['refund_account_type']) && $_POST['refund_account_type'] === 'Cheque') ? 'checked' : ''; ?>>
                Cheque
              </label>
              <label style="display: flex; align-items: center; gap: 5px; font-weight: normal;">
                <input type="radio" name="refund_account_type" value="Savings" <?php echo (isset($_POST['refund_account_type']) && $_POST['refund_account_type'] === 'Savings') ? 'checked' : ''; ?>>
                Savings
              </label>
              <label style="display: flex; align-items: center; gap: 5px; font-weight: normal;">
                <input type="radio" name="refund_account_type" value="Loan" <?php echo (isset($_POST['refund_account_type']) && $_POST['refund_account_type'] === 'Loan') ? 'checked' : ''; ?>>
                Loan
              </label>
            </div>
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Account Name</label>
            <input type="text" name="refund_account_name" value="<?php echo htmlspecialchars($_POST['refund_account_name'] ?? ''); ?>">
          </div>
          <div>
            <label>Account Number</label>
            <input type="text" name="refund_account_number" value="<?php echo htmlspecialchars($_POST['refund_account_number'] ?? ''); ?>">
          </div>
        </div>
      </div>

      <!-- Required Documents -->
      <div class="form-section">
        <h2 class="section-title">Required Documents</h2>
        <div class="info-box" style="margin-bottom: 20px;">
          <strong>Note:</strong> Please upload copies of all required documents. Accepted formats: PDF, DOC, DOCX, JPG, JPEG, PNG. Maximum file size: 5MB per file. DO NOT SEND ORIGINALS.
        </div>
        
        <div class="form-row full">
          <div>
            <label>Academic Certificate(s) <span class="required">*</span></label>
            <input type="file" name="academic_certificates[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple required>
            <small style="color: #666;">Upload certified copy of your most recent academic certificate(s). You can upload multiple files.</small>
          </div>
        </div>
        
        <div class="form-row full">
          <div>
            <label>Academic Transcripts/Term Reports <span class="required">*</span></label>
            <input type="file" name="academic_transcripts[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple required>
            <small style="color: #666;">Upload certified copy of your most recent academic transcripts/term reports. You can upload multiple files.</small>
          </div>
        </div>
        
        <div class="form-row full">
          <div>
            <label>Letters of Reference <span class="required">*</span></label>
            <input type="file" name="reference_letters[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple required>
            <small style="color: #666;">Upload 2 copies of Letters of Reference. You can upload multiple files.</small>
          </div>
        </div>
        
        <div class="form-row full">
          <div>
            <label>Curriculum Vitae (CV) <span class="required">*</span></label>
            <input type="file" name="cv" accept=".pdf,.doc,.docx" required>
            <small style="color: #666;">Upload your updated Curriculum Vitae.</small>
          </div>
        </div>
        
        <div class="form-row full">
          <div>
            <label>Identity Document <span class="required">*</span></label>
            <input type="file" name="id_document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
            <small style="color: #666;">Upload certified copy of your NID Card, Driver's License, School ID Card, or Employment ID Card.</small>
          </div>
        </div>
        
        <div class="info-box" style="margin-top: 15px;">
          <strong>Important:</strong> If you submit an application that does not include all supporting certified documents or does not meet all submission requirements, then your application will be considered non-compliant and will be disqualified.
        </div>
      </div>

      <!-- Declaration -->
      <div class="form-section">
        <h2 class="section-title">Declaration and Submission</h2>
        <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px;">
          <p style="line-height: 1.8; color: #333; margin-bottom: 20px;">
            I declare, in submitting this application form, that the information contained in and provided with it is true and correct. I acknowledge that giving false or misleading information is a serious offense under the PNG Government Criminal Code Act. PNG Maritime College takes a zero-tolerance approach to fraud. All application documents will be verified and if found fraudulent, applicants will be excluded from the course.
          </p>
          <div class="checkbox-group" style="margin-top: 15px;">
            <input type="checkbox" name="declaration_agreed" value="1" id="declaration_agreed" required <?php echo (isset($_POST['declaration_agreed'])) ? 'checked' : ''; ?>>
            <label for="declaration_agreed" style="margin: 0; font-weight: 600;"><span class="required">*</span> I agree to the above declaration</label>
          </div>
        </div>
      </div>

      <button type="submit" class="btn-submit">Submit Application</button>
    </form>
  </section>

  <script>
    const programInfo = {
      'deck_officers': {
        name: 'Deck Officers Program',
        fee: 'K17,688.00',
        startDate: '2nd February 2026',
        requirements: 'Grade 12 Certificate with minimum C grade and above in Social Sciences (with exception of Geography) OR Science Streams. Tertiary qualification would be an advantage.',
        description: 'Leads to becoming Deck Officers who command ships. Responsibilities include navigation and operation of ships, tugs, ferries, dredges, offshore supply vessels, and other specialized ships. Also involves cargo storage and efficient goods carriage. Other roles include Marine Surveyors, Pilots, Harbor Masters, and various off-shore marine-related jobs.'
      },
      'marine_engineers': {
        name: 'Marine Engineers Program',
        fee: 'K24,120.00',
        startDate: '2nd February 2026',
        requirements: 'Successful Completion of Grade 12 Certificate with a C grade and above in Mathematics A, English, Physics, and Chemistry. Tertiary qualification would be an advantage.',
        description: 'Leads to becoming Chief Engineers. Responsibilities include operating and maintaining ships, tugs, dredges, offshore oil exploration vessels, seismic research ships, and specialized vessels. Also includes shore-based roles in Marine dry docks, fleet operation, survey functions, and non-marine operational industries like power stations, hospitals, hotels, water/sewage treatment, and large building services.'
      },
      'gp_rating': {
        name: 'GP Rating 2 Program',
        fee: 'K9,722.00',
        startDate: '11th May 2026',
        requirements: 'Successful Completion of Grade 10 Certificate with Upper Passes and Credits in English, Maths, Science, and Social Sciences. Grade 12 Certificate holders are automatically selected.',
        description: 'A Basic Rating 2 induction course for General Purpose (GP) Ratings, conducted over three months in two parts: Deck Rating Stream and Engine Rating Stream. GP Ratings can work in both Deck and Engine Departments. Assist Deck Officers and Marine Engineers, steer the ship, keep a lookout, operate machinery (cranes), tie up at port, maneuver boats, work aloft, and assist with ship maintenance.'
      }
    };

    function updateProgramDisplay() {
      const programType = document.getElementById('program_type').value;
      const displayDiv = document.getElementById('program_display');
      const infoDiv = document.getElementById('program_info');
      const infoContent = document.getElementById('program_info_content');
      
      if (programType && programInfo[programType]) {
        const info = programInfo[programType];
        displayDiv.textContent = info.name;
        displayDiv.style.display = 'inline-block';
        
        // Display program-specific information
        infoContent.innerHTML = `
          <h3 style="margin-top: 0; color: #1d4e89;">${info.name}</h3>
          <p><strong>Course Fee:</strong> ${info.fee}</p>
          <p><strong>Start Date:</strong> ${info.startDate}</p>
          <p><strong>Minimum Entry Requirements:</strong><br>${info.requirements}</p>
          <p><strong>Program Description:</strong><br>${info.description}</p>
          <p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ccc;">
            <strong>Important:</strong> Students MUST PAY 100% of the Full Course Fee before the course commencement. Failure to do so will result in denied enrollment.
          </p>
        `;
        infoDiv.style.display = 'block';
      } else {
        displayDiv.style.display = 'none';
        infoDiv.style.display = 'none';
      }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
      updateProgramDisplay();
    });
  </script>

  <footer>
    &copy; 2025 PNG Maritime College. All rights reserved.
  </footer>
</body>
</html>

