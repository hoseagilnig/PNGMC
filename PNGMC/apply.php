<?php
session_start();
require_once 'pages/includes/db_config.php';
require_once 'pages/includes/application_validator.php';

$message = '';
$message_type = '';
$validation_errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate application before processing
    $validation_result = validateApplication($_POST);
    
    if (!$validation_result['valid']) {
        // Application validation failed
        $validation_errors = $validation_result['errors'];
        $message = "Your application is incomplete. Please review the errors below and complete all required fields.";
        $message_type = "error";
        
        // Optionally send email notification (can be enabled/disabled via configuration)
        // Set $send_validation_email = true to enable email notifications
        $send_validation_email = true; // Change to false to disable email notifications
        
        if ($send_validation_email) {
            $email = trim($_POST['email'] ?? '');
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            
            if (!empty($email) && !empty($first_name) && !empty($last_name) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                sendValidationErrorEmail($email, $first_name, $last_name, $validation_errors);
            }
        }
    } else {
        // Validation passed, proceed with submission
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
            
            // Academic information
            $education_level = trim($_POST['education_level'] ?? '');
            $program_interest = trim($_POST['program_interest'] ?? 'Cadet Officers Program');
            $grade_12_passed = isset($_POST['grade_12_passed']) ? 1 : 0;
            $maths_grade = trim($_POST['maths_grade'] ?? '');
            $physics_grade = trim($_POST['physics_grade'] ?? '');
            $english_grade = trim($_POST['english_grade'] ?? '');
            $overall_gpa = $_POST['overall_gpa'] ?? null;
            
            // Generate application number
            $app_count = 0;
            $result = $conn->query("SELECT COUNT(*) as count FROM applications");
            if ($result) {
                $app_count = $result->fetch_assoc()['count'];
            }
            $application_number = 'APP-' . date('Y') . '-' . str_pad($app_count + 1, 4, '0', STR_PAD_LEFT);
            
            // Ensure overall_gpa is a valid number (default to 0 if empty/null)
            $overall_gpa_value = (!empty($overall_gpa) && is_numeric($overall_gpa)) ? floatval($overall_gpa) : 0.00;
            
            // Check if education_level column exists
            $check_col = $conn->query("SHOW COLUMNS FROM applications LIKE 'education_level'");
            $has_education_level = $check_col && $check_col->num_rows > 0;
            
            // Check if application_type column exists
            $check_app_type = $conn->query("SHOW COLUMNS FROM applications LIKE 'application_type'");
            $has_application_type = $check_app_type && $check_app_type->num_rows > 0;
            
            // Prepare statement - include education_level and application_type if columns exist
            if ($has_education_level && $has_application_type) {
                $sql = "INSERT INTO applications (application_number, first_name, last_name, middle_name, date_of_birth, gender, email, phone, address, city, province, education_level, program_interest, grade_12_passed, maths_grade, physics_grade, english_grade, overall_gpa, application_type, expression_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'submitted')";
            } elseif ($has_education_level) {
                $sql = "INSERT INTO applications (application_number, first_name, last_name, middle_name, date_of_birth, gender, email, phone, address, city, province, education_level, program_interest, grade_12_passed, maths_grade, physics_grade, english_grade, overall_gpa, expression_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'submitted')";
            } elseif ($has_application_type) {
                $sql = "INSERT INTO applications (application_number, first_name, last_name, middle_name, date_of_birth, gender, email, phone, address, city, province, program_interest, grade_12_passed, maths_grade, physics_grade, english_grade, overall_gpa, application_type, expression_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'submitted')";
            } else {
                $sql = "INSERT INTO applications (application_number, first_name, last_name, middle_name, date_of_birth, gender, email, phone, address, city, province, program_interest, grade_12_passed, maths_grade, physics_grade, english_grade, overall_gpa, expression_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'submitted')";
            }
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                $error = "Prepare failed: " . $conn->error;
            } else {
                // Build type string and parameters based on which columns exist
                $application_type_value = 'new_student'; // Set application type for new student applications
                
                if ($has_education_level && $has_application_type) {
                    // 19 parameters: 13 strings (application_number through province + education_level + program_interest) + 1 integer + 3 strings + 1 double + 1 string (application_type)
                    $types = '';
                    $types .= str_repeat('s', 13); // 13 strings: application_number through province + education_level + program_interest
                    $types .= 'i';                  // 1 integer: grade_12_passed
                    $types .= str_repeat('s', 3);   // 3 strings: maths_grade, physics_grade, english_grade
                    $types .= 'd';                  // 1 double: overall_gpa
                    $types .= 's';                  // 1 string: application_type
                    // Total: 13 + 1 + 3 + 1 + 1 = 19
                    
                    $expected_count = 19;
                    $params = [
                        $application_number, $first_name, $last_name, $middle_name, $date_of_birth,
                        $gender, $email, $phone, $address, $city, $province,
                        $education_level, $program_interest,
                        $grade_12_passed, $maths_grade, $physics_grade, $english_grade, $overall_gpa_value,
                        $application_type_value
                    ];
                } elseif ($has_education_level) {
                    // 18 parameters: 13 strings (application_number through province + education_level + program_interest) + 1 integer + 3 strings + 1 double
                    $types = '';
                    $types .= str_repeat('s', 13); // 13 strings: application_number through province + education_level + program_interest
                    $types .= 'i';                  // 1 integer: grade_12_passed
                    $types .= str_repeat('s', 3);   // 3 strings: maths_grade, physics_grade, english_grade
                    $types .= 'd';                  // 1 double: overall_gpa
                    // Total: 13 + 1 + 3 + 1 = 18
                    
                    $expected_count = 18;
                    $params = [
                        $application_number, $first_name, $last_name, $middle_name, $date_of_birth,
                        $gender, $email, $phone, $address, $city, $province,
                        $education_level, $program_interest,
                        $grade_12_passed, $maths_grade, $physics_grade, $english_grade, $overall_gpa_value
                    ];
                } elseif ($has_application_type) {
                    // 18 parameters: 12 strings (application_number through province + program_interest) + 1 integer + 3 strings + 1 double + 1 string (application_type)
                    $types = '';
                    $types .= str_repeat('s', 12); // 12 strings: application_number through province + program_interest
                    $types .= 'i';                  // 1 integer: grade_12_passed
                    $types .= str_repeat('s', 3);   // 3 strings: maths_grade, physics_grade, english_grade
                    $types .= 'd';                  // 1 double: overall_gpa
                    $types .= 's';                  // 1 string: application_type
                    // Total: 12 + 1 + 3 + 1 + 1 = 18
                    
                    $expected_count = 18;
                    $params = [
                        $application_number, $first_name, $last_name, $middle_name, $date_of_birth,
                        $gender, $email, $phone, $address, $city, $province,
                        $program_interest,
                        $grade_12_passed, $maths_grade, $physics_grade, $english_grade, $overall_gpa_value,
                        $application_type_value
                    ];
                } else {
                    // 17 parameters: 12 strings (application_number through province + program_interest) + 1 integer + 3 strings + 1 double
                    $types = '';
                    $types .= str_repeat('s', 12); // 12 strings: application_number through province + program_interest
                    $types .= 'i';                  // 1 integer: grade_12_passed
                    $types .= str_repeat('s', 3);   // 3 strings: maths_grade, physics_grade, english_grade
                    $types .= 'd';                  // 1 double: overall_gpa
                    // Total: 12 + 1 + 3 + 1 = 17
                    
                    $expected_count = 17;
                    $params = [
                        $application_number, $first_name, $last_name, $middle_name, $date_of_birth,
                        $gender, $email, $phone, $address, $city, $province,
                        $program_interest,
                        $grade_12_passed, $maths_grade, $physics_grade, $english_grade, $overall_gpa_value
                    ];
                }
                
                // Verify count matches
                if (strlen($types) !== $expected_count) {
                    $message = "Error: Type string length mismatch. Expected {$expected_count}, got " . strlen($types) . ". Type string: '" . $types . "'";
                    $message_type = "error";
                } else {
                    // Bind parameters with references (required by bind_param)
                    // Create references for all parameters
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
                            require_once 'pages/includes/workflow_helper.php';
                            createApplicationRequirements($application_id, 'new_student');
                        }
                        
                        // Initialize workflow - notify Student Admin Service (only for valid applications)
                        require_once 'pages/includes/workflow_helper.php';
                        initializeApplicationWorkflow($application_id);
                        
                        $app_num = htmlspecialchars($application_number);
                        header('Location: index.html?success=1&type=expression&app=' . urlencode($app_num));
                        exit;
                    } else {
                        $message = "Error submitting application: " . $stmt->error;
                        $message_type = "error";
                    }
                    $stmt->close();
                }
            }
            $conn->close();
        }
    } // End of validation check
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Apply to PNG Maritime College</title>
  <link rel="stylesheet" href="css/sms_styles.css">
  <style>
    .apply-section {
      max-width: 800px;
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
    input, select, textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; transition: border-color 0.3s; }
    input:focus, select:focus, textarea:focus { outline: none; border-color: #1d4e89; }
    .error-border { border-color: #dc3545 !important; border-width: 2px !important; }
    .field-error { animation: fadeIn 0.3s; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    .required { color: red; }
    .btn-submit { width: 100%; padding: 15px; background: #1d4e89; color: white; border: none; border-radius: 5px; font-size: 1.1rem; font-weight: bold; cursor: pointer; margin-top: 20px; }
    .btn-submit:hover { background: #163c6a; }
    .section-title { color: #1d4e89; margin: 30px 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #1d4e89; }
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
    <h1 style="color: #1d4e89; text-align: center; margin-bottom: 10px;">Expression of Interest</h1>
    <p style="text-align: center; color: #666; margin-bottom: 30px;">Apply to study at PNG Maritime College - Grade 10 students (GP course) or Grade 12 students (Cadet Officers Program)</p>
    
    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;">
      <strong>⚠️ Important:</strong> Please ensure all required fields are completed before submitting. Incomplete applications will not be forwarded to Student Admin Service and you will be notified immediately.
    </div>
    
    <div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #1d4e89; text-align: center;">
      <strong>Returning after sea service?</strong><br>
      <a href="apply_continuing.php" style="color: #1d4e89; text-decoration: underline; font-weight: bold;">Click here to apply as a candidate returning</a> (SOLAS Refresher or Next Level Course)
    </div>
    
    <?php if ($message): ?>
      <div class="message <?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
        <?php if (!empty($validation_errors)): ?>
          <ul style="margin: 10px 0 0 20px; padding-left: 20px;">
            <?php foreach ($validation_errors as $error): ?>
              <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <h2 class="section-title">Personal Information</h2>
      <div class="form-row">
        <div>
          <label>First Name <span class="required">*</span></label>
          <input type="text" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
        </div>
        <div>
          <label>Last Name <span class="required">*</span></label>
          <input type="text" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
        </div>
      </div>
      <div class="form-row">
        <div>
          <label>Middle Name</label>
          <input type="text" name="middle_name" value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">
        </div>
        <div>
          <label>Date of Birth</label>
          <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
        </div>
      </div>
      <div class="form-row">
        <div>
          <label>Gender</label>
          <select name="gender">
            <option value="">Select</option>
            <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
            <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
            <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
          </select>
        </div>
        <div>
          <label>Email <span class="required">*</span></label>
          <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
      </div>
      <div class="form-row">
        <div>
          <label>Phone <span class="required">*</span></label>
          <input type="text" name="phone" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
        </div>
        <div>
          <label>City</label>
          <input type="text" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
        </div>
      </div>
      <div class="form-row">
        <div>
          <label>Province</label>
          <input type="text" name="province" value="<?php echo htmlspecialchars($_POST['province'] ?? ''); ?>">
        </div>
      </div>
      <div class="form-row full">
        <div>
          <label>Address</label>
          <textarea name="address" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
        </div>
      </div>

      <h2 class="section-title">Academic Information</h2>
      <div class="form-row">
        <div>
          <label>Education Level <span class="required">*</span></label>
          <select name="education_level" id="education_level" required onchange="toggleProgramFields()">
            <option value="">Select Education Level</option>
            <option value="Grade 10" <?php echo (isset($_POST['education_level']) && $_POST['education_level'] === 'Grade 10') ? 'selected' : ''; ?>>Grade 10</option>
            <option value="Grade 12" <?php echo (isset($_POST['education_level']) && $_POST['education_level'] === 'Grade 12') ? 'selected' : ''; ?>>Grade 12</option>
          </select>
        </div>
        <div>
          <label>Program of Interest <span class="required">*</span></label>
          <select name="program_interest" id="program_interest" required>
            <option value="">Select Program</option>
            <option value="GP Course" id="gp_option" <?php echo (isset($_POST['program_interest']) && $_POST['program_interest'] === 'GP Course') ? 'selected' : ''; ?>>GP Course (Grade 10)</option>
            <option value="Cadet Officers Program" id="cadet_option" <?php echo (isset($_POST['program_interest']) && $_POST['program_interest'] === 'Cadet Officers Program') ? 'selected' : ''; ?>>Cadet Officers Program (Grade 12)</option>
          </select>
        </div>
      </div>
      <div class="form-row" id="grade_12_section" style="display: none;">
        <div>
          <label>
            <input type="checkbox" name="grade_12_passed" value="1" <?php echo (isset($_POST['grade_12_passed'])) ? 'checked' : ''; ?>>
            I have passed Grade 12
          </label>
        </div>
      </div>
      <div class="form-row">
        <div>
          <label>Mathematics Grade</label>
          <input type="text" name="maths_grade" placeholder="e.g., A, B, C" value="<?php echo htmlspecialchars($_POST['maths_grade'] ?? ''); ?>">
        </div>
        <div>
          <label>Physics Grade</label>
          <input type="text" name="physics_grade" placeholder="e.g., A, B, C" value="<?php echo htmlspecialchars($_POST['physics_grade'] ?? ''); ?>">
        </div>
      </div>
      <div class="form-row">
        <div>
          <label>English Grade</label>
          <input type="text" name="english_grade" placeholder="e.g., A, B, C" value="<?php echo htmlspecialchars($_POST['english_grade'] ?? ''); ?>">
        </div>
        <div>
          <label>Overall GPA</label>
          <input type="number" name="overall_gpa" step="0.01" min="0" max="4" placeholder="e.g., 3.5" value="<?php echo htmlspecialchars($_POST['overall_gpa'] ?? ''); ?>">
        </div>
      </div>

      <button type="submit" class="btn-submit" id="submitBtn">Submit Expression of Interest</button>
    </form>
  </section>

  <script>
    // Client-side validation for instant feedback
    function validateForm() {
      const errors = [];
      const first_name = document.querySelector('input[name="first_name"]').value.trim();
      const last_name = document.querySelector('input[name="last_name"]').value.trim();
      const email = document.querySelector('input[name="email"]').value.trim();
      const phone = document.querySelector('input[name="phone"]').value.trim();
      const education_level = document.querySelector('select[name="education_level"]').value;
      const program_interest = document.querySelector('select[name="program_interest"]').value;
      const grade_12_passed = document.querySelector('input[name="grade_12_passed"]')?.checked;
      const maths_grade = document.querySelector('input[name="maths_grade"]').value.trim();
      const physics_grade = document.querySelector('input[name="physics_grade"]').value.trim();
      const english_grade = document.querySelector('input[name="english_grade"]').value.trim();
      const overall_gpa = document.querySelector('input[name="overall_gpa"]').value.trim();
      
      // Clear previous error indicators
      document.querySelectorAll('.field-error').forEach(el => el.remove());
      document.querySelectorAll('.error-border').forEach(el => el.classList.remove('error-border'));
      
      // Required field validation
      if (!first_name) errors.push({field: 'first_name', message: 'First Name is required'});
      if (!last_name) errors.push({field: 'last_name', message: 'Last Name is required'});
      if (!email) {
        errors.push({field: 'email', message: 'Email is required'});
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errors.push({field: 'email', message: 'Please enter a valid email address'});
      }
      if (!phone) {
        errors.push({field: 'phone', message: 'Phone is required'});
      } else if (phone.length < 7) {
        errors.push({field: 'phone', message: 'Please enter a valid phone number (minimum 7 digits)'});
      }
      if (!education_level) errors.push({field: 'education_level', message: 'Education Level is required'});
      if (!program_interest) errors.push({field: 'program_interest', message: 'Program of Interest is required'});
      
      // Program-specific validation
      if (education_level === 'Grade 12' && program_interest === 'Cadet Officers Program') {
        if (!grade_12_passed) {
          errors.push({field: 'grade_12_passed', message: 'You must confirm that you have passed Grade 12'});
        }
        
        const has_grade = maths_grade || physics_grade || english_grade;
        const has_gpa = overall_gpa && parseFloat(overall_gpa) > 0;
        if (!has_grade && !has_gpa) {
          errors.push({field: 'maths_grade', message: 'Please provide at least one subject grade or Overall GPA'});
        }
      }
      
      // Display errors
      if (errors.length > 0) {
        errors.forEach(error => {
          const field = document.querySelector(`[name="${error.field}"]`);
          if (field) {
            field.classList.add('error-border');
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            errorDiv.style.cssText = 'color: #dc3545; font-size: 0.875rem; margin-top: 5px;';
            errorDiv.textContent = error.message;
            field.parentElement.appendChild(errorDiv);
          }
        });
        
        // Scroll to first error
        const firstError = document.querySelector('.error-border');
        if (firstError) {
          firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
          firstError.focus();
        }
        
        return false;
      }
      
      return true;
    }
    
    // Attach validation to form submit
    document.querySelector('form').addEventListener('submit', function(e) {
      if (!validateForm()) {
        e.preventDefault();
        return false;
      }
    });
    
    // Real-time validation on blur
    document.querySelectorAll('input[required], select[required]').forEach(field => {
      field.addEventListener('blur', function() {
        const fieldName = this.name;
        const value = this.value.trim();
        
        // Remove previous error
        const prevError = this.parentElement.querySelector('.field-error');
        if (prevError) prevError.remove();
        this.classList.remove('error-border');
        
        // Validate specific fields
        if (!value && this.hasAttribute('required')) {
          this.classList.add('error-border');
          const errorDiv = document.createElement('div');
          errorDiv.className = 'field-error';
          errorDiv.style.cssText = 'color: #dc3545; font-size: 0.875rem; margin-top: 5px;';
          errorDiv.textContent = 'This field is required';
          this.parentElement.appendChild(errorDiv);
        } else if (fieldName === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
          this.classList.add('error-border');
          const errorDiv = document.createElement('div');
          errorDiv.className = 'field-error';
          errorDiv.style.cssText = 'color: #dc3545; font-size: 0.875rem; margin-top: 5px;';
          errorDiv.textContent = 'Please enter a valid email address';
          this.parentElement.appendChild(errorDiv);
        } else if (fieldName === 'phone' && value && value.length < 7) {
          this.classList.add('error-border');
          const errorDiv = document.createElement('div');
          errorDiv.className = 'field-error';
          errorDiv.style.cssText = 'color: #dc3545; font-size: 0.875rem; margin-top: 5px;';
          errorDiv.textContent = 'Please enter a valid phone number (minimum 7 digits)';
          this.parentElement.appendChild(errorDiv);
        }
      });
    });
    
    function toggleProgramFields() {
      const educationLevel = document.getElementById('education_level').value;
      const programSelect = document.getElementById('program_interest');
      const gpOption = document.getElementById('gp_option');
      const cadetOption = document.getElementById('cadet_option');
      const grade12Section = document.getElementById('grade_12_section');
      
      // Reset program selection
      programSelect.value = '';
      
      if (educationLevel === 'Grade 10') {
        // Show GP Course option, hide Cadet Officers
        gpOption.style.display = 'block';
        cadetOption.style.display = 'none';
        grade12Section.style.display = 'none';
        // Auto-select GP Course
        programSelect.value = 'GP Course';
      } else if (educationLevel === 'Grade 12') {
        // Show Cadet Officers option, hide GP Course
        gpOption.style.display = 'none';
        cadetOption.style.display = 'block';
        grade12Section.style.display = 'block';
        // Auto-select Cadet Officers Program
        programSelect.value = 'Cadet Officers Program';
      } else {
        // Show both options
        gpOption.style.display = 'block';
        cadetOption.style.display = 'block';
        grade12Section.style.display = 'none';
      }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
      toggleProgramFields();
    });
  </script>

  <footer>
    &copy; 2025 PNG Maritime College. All rights reserved.
  </footer>
</body>
</html>

