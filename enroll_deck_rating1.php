<?php
session_start();
require_once 'pages/includes/db_config.php';

$message = '';
$message_type = '';

// Function to handle file upload
function handleFileUpload($file_key, $upload_dir, $max_size, $application_number) {
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    
    if ($_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($_FILES[$file_key]['size'] > $max_size) {
        return false;
    }
    
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/jpg', 'image/png'];
    $file_type = $_FILES[$file_key]['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        return false;
    }
    
    $file_ext = pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION);
    $new_filename = $application_number . '_' . $file_key . '_' . time() . '.' . $file_ext;
    $target_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target_path)) {
        return $target_path;
    }
    
    return false;
}

// Course configuration for Mates (Deck) courses
$course_config = [
    'deck_rating_1' => [
        'name' => 'DECK RATING 1',
        'display_name' => 'Deck Rating 1',
        'fee' => 'K9,816.00',
        'start_date' => '17th August 2026',
        'duration' => '10 Weeks',
        'finish_date' => '13th November 2026',
        'app_open' => '',
        'app_close' => '30/07/2026',
        'app_prefix' => 'DECK-R1',
        'program_interest' => 'Deck Rating 1',
        'sponsor_type' => 'self', // 'self' or 'company'
        'selection_criteria' => [
            'Must provide a recent Character Reference from the current Employer.',
            'Must provide a Disciplinary History Report for the past two (2) years from the current Employer.',
            'Must possess General Purpose Rating Class 2 Certificate.',
            'Must have not less than 18 months sea service.',
            'Must PAY 100% of the full course fee immediately upon being given an acceptance letter. Course Fee payment of less than 100% WILL NOT be accepted.'
        ]
    ],
    'mate_class_5' => [
        'name' => 'MATE CLASS 5',
        'display_name' => 'Mate Class 5',
        'fee' => 'K15,679.00',
        'start_date' => '11th May 2026',
        'duration' => '21 Weeks',
        'finish_date' => '2nd October 2026',
        'app_open' => '',
        'app_close' => '',
        'app_prefix' => 'DECK-M5',
        'program_interest' => 'Mate Class 5',
        'sponsor_type' => 'self', // 'self' or 'company'
        'selection_criteria' => [
            'Must provide a recent Character Reference from the current Employer.',
            'Must provide a Disciplinary History Report for the past two (2) years from the current Employer.',
            'Must PAY 100% of the full course fee immediately upon being given an acceptance letter. Course Fee payment of less than 100% WILL NOT be accepted.'
        ]
    ],
    'mate_class_4' => [
        'name' => 'MATE CLASS 4',
        'display_name' => 'Mate Class 4',
        'fee' => 'K19,227.00',
        'start_date' => '23rd February 2026',
        'duration' => '24 Weeks',
        'finish_date' => '01st August 2026',
        'app_open' => '01/10/2025',
        'app_close' => '30/01/2026',
        'app_prefix' => 'DECK-M4',
        'program_interest' => 'Mate Class 4',
        'sponsor_type' => 'company', // 'self' or 'company'
        'selection_criteria' => [
            'Must provide a recent Character Reference from the current employer.',
            'Must provide a Disciplinary History Report for the past two (2) years from the current employer.',
            'Prerequisite Certificate: At least hold an appropriate certificate for performing VHF radio communication in accordance with the requirements of the Radio Regulations. If designated to have primary responsibility for radio communications during distress incidents, hold an appropriate certificate issued or recognized under the provisions of Radio Regulations.',
            'Required Sea Service:',
            '  (a) for Mate < 500gt near-coastal -',
            '    (i) Must have completed special training, including an adequate period of appropriate sea-going service as required by the administration; OR',
            '    (ii) Must have completed approved sea-going service in the deck department of not less than 3 years; OR',
            '    (iii) Must meet the appropriate requirements of the Radio Regulations in Chapter IV, as appropriate, for performing designated radio duties; AND',
            '    (iv) Must have completed approved education and training and meet the standard of competence specified in Section A-II/3 of the STCW Code for officers in charge of a navigational watch ships of less than 500 gross tonnage engaged on near-coastal voyages.',
            '  (b) for Mate Class 4 - Must have completed 36 months\' service in the deck department, including 6 months\' bridge watch keeping duties as lookout or helmsman under the supervision of a qualified officer. The service shall be performed either in or outside near-coastal waters. Remission of deck department service may be allowed by the Administration for attendance at approved training courses.',
            'Examination: Must have passed a written and an oral examination conducted by an Examiner.',
            'Must Pay 100% of the full course fee upon being given an acceptance letter by PNGMC. Failure to do so will result in the student being denied admission. Course fee payment of less than 100% WILL NOT be accepted.',
            'WHERE ALL THE ABOVE REQUIRMENTS HAVE BEEN FULLY MET, PREFERENCE FOR ADMISSION WILL BE GIVEN TO STUDENTS WHO HAVE PAID THE FULL FEE.'
        ]
    ],
    'mate_class_3' => [
        'name' => 'MATE CLASS 3',
        'display_name' => 'Mate Class 3',
        'fee' => 'K32,593.00',
        'start_date' => '02nd February 2026',
        'duration' => '41 Weeks',
        'finish_date' => '13th November 2026',
        'app_open' => '09/10/2025',
        'app_close' => '31/12/2025',
        'app_prefix' => 'DECK-M3',
        'program_interest' => 'Mate Class 3',
        'sponsor_type' => 'company', // 'self' or 'company'
        'selection_criteria' => [
            'Must provide a recent Character Reference from the current employer.',
            'Must provide a Disciplinary History Report for the past two (2) years from the current employer.',
            'Prerequisite Certificate: Mate Class 4',
            'Required Sea Service: 36 months approved sea service in Deck Department including at least 6 months bridge watch-keeping duties as look out and helmsman under supervision of a qualified officer. Not less than 3 months of the service shall be performed outside near-coastal waters. Remission of watch-keeping service may be allowed by the Administration for attendance at approved training courses.',
            'Examination: Must have passed a written and an oral examination conducted by an Examiner.',
            'Must Pay 100% of the full course fee upon being accepted. Failure to do so will result in the student being denied admission. Shipping companies with a good payment history are exempted from the prepayment requirement.',
            'WHERE ALL THE ABOVE REQUIREMENTS HAVE BEEN FULLY MET, PREFERENCE FOR ADMISSION WILL BE GIVEN TO STUDENTS WHO HAVE PAID THE FULL FEE.'
        ]
    ],
    'mate_class_2_1' => [
        'name' => 'MATE CLASS 2',
        'display_name' => 'Mate Class 2/1',
        'fee' => 'K34,013.00',
        'start_date' => '02nd February 2026',
        'duration' => '41 Weeks',
        'finish_date' => '13th November 2026',
        'app_open' => '09/10/2025',
        'app_close' => '31/12/2025',
        'app_prefix' => 'DECK-M2',
        'program_interest' => 'Mate Class 2/1',
        'sponsor_type' => 'company', // 'self' or 'company'
        'selection_criteria' => [
            'Must provide a recent Character Reference from the current employer.',
            'Must provide a Disciplinary History Report for the past two (2) years from the current employer.',
            'Prerequisite Certificate: Officer In-charge of a navigational watch, Class 3 Master or Class 3 Mate which need not be current.',
            'Required Sea Service: Must have completed at least 18 months qualifying service in charge of a navigation watch on ships 200 gross tonnage or over while holding a certificate referred to above. At least 12 months must have been served on trading ships of 500 gross tonnage or over.',
            'Course of Study: Must have satisfactorily completed an appropriate course of study that -',
            '  (a) at least meets the standards specified in Table A-11/2 of the STCW Code and includes management training and knowledge of the International Safety Management Code; and',
            '  (b) includes practical training in -',
            '    (1) ARPA, radar and electronic navigation; and',
            '    (ii) medical care training that meets the standards of competence specified in Section A-VI/4, paragraphs 4-6 of the STCW Code; and',
            '    (iii) advanced fire-fighting in accordance with the provisions of Section A-VI/3 of the STCW Code.',
            'Certificates: Must hold the following Certificates -',
            '  certificate of proficiency in survival craft and rescue boats other than fast rescue boats; and',
            '  (a) valid GMDSS General Operator\'s Certificate; and',
            '  (b) valid certificate of medical fitness for service in the deck department.',
            'Examination: Must have passed a written and an oral examination conducted by an Examiner.',
            'Company Sponsor Must Pay 100% of the full course fee upon being given an acceptance letter. Failure to do so will result in the student being denied admission. Shipping companies with a good payment history are exempted from the prepayment requirement.',
            'WHERE ALL THE ABOVE REQUIREMENTS HAVE BEEN FULLY MET, PREFERENCE FOR ADMISSION WILL BE GIVEN TO STUDENTS WHO HAVE PAID THE FULL FEE.'
        ]
    ]
];

// Get course from URL parameter, default to deck_rating_1
$course_key = isset($_GET['course']) ? trim($_GET['course']) : 'deck_rating_1';
if (!isset($course_config[$course_key])) {
    $course_key = 'deck_rating_1'; // Fallback to default
}
$course = $course_config[$course_key];

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
            // Personal Details
            $cerb_no = trim($_POST['cerb_no'] ?? '');
            $family_name = trim($_POST['family_name'] ?? '');
            $given_names = trim($_POST['given_names'] ?? '');
            $gender = trim($_POST['gender'] ?? '');
            $country_of_origin = trim($_POST['country_of_origin'] ?? 'Papua New Guinea');
            $province_state = trim($_POST['province_state'] ?? '');
            $district = trim($_POST['district'] ?? '');
            $sub_province = trim($_POST['sub_province'] ?? '');
            $date_of_birth = $_POST['date_of_birth'] ?? null;
            $place_of_birth = trim($_POST['place_of_birth'] ?? '');
            $marital_status = trim($_POST['marital_status'] ?? '');
            $uniform_short = trim($_POST['uniform_short'] ?? '');
            $uniform_waist = trim($_POST['uniform_waist'] ?? '');
            $uniform_shirt = trim($_POST['uniform_shirt'] ?? '');
            $uniform_shoe = trim($_POST['uniform_shoe'] ?? '');
            $uniform_overall = trim($_POST['uniform_overall'] ?? '');
            $post_office_address = trim($_POST['post_office_address'] ?? '');
            
            // Next of Kin
            $nok_name = trim($_POST['nok_name'] ?? '');
            $nok_relationship = trim($_POST['nok_relationship'] ?? '');
            $nok_post_address = trim($_POST['nok_post_address'] ?? '');
            $nok_phone = trim($_POST['nok_phone'] ?? '');
            $nok_email = trim($_POST['nok_email'] ?? '');
            
            // Educational Background
            $highest_education = $_POST['highest_education'] ?? [];
            $highest_education_other = trim($_POST['highest_education_other'] ?? '');
            $grade_10 = isset($_POST['grade_10']) ? 1 : 0;
            $grade_12 = isset($_POST['grade_12']) ? 1 : 0;
            $diploma = isset($_POST['diploma']) ? 1 : 0;
            $degree = isset($_POST['degree']) ? 1 : 0;
            $school1_name = trim($_POST['school1_name'] ?? '');
            $school1_year = trim($_POST['school1_year'] ?? '');
            $school2_name = trim($_POST['school2_name'] ?? '');
            $school2_year = trim($_POST['school2_year'] ?? '');
            $school3_name = trim($_POST['school3_name'] ?? '');
            $school3_year = trim($_POST['school3_year'] ?? '');
            
            // Sponsorship Details
            $sponsor_name = trim($_POST['sponsor_name'] ?? '');
            $sponsor_postal_address = trim($_POST['sponsor_postal_address'] ?? '');
            $sponsor_contact_person = trim($_POST['sponsor_contact_person'] ?? '');
            $sponsor_email = trim($_POST['sponsor_email'] ?? '');
            $sponsor_mobile = trim($_POST['sponsor_mobile'] ?? '');
            $sponsor_landline = trim($_POST['sponsor_landline'] ?? '');
            $sponsor_fax = trim($_POST['sponsor_fax'] ?? '');
            $sponsor_designation = trim($_POST['sponsor_designation'] ?? '');
            $sponsor_date = $_POST['sponsor_date'] ?? null;
            
            // Previous Course Information
            $previous_course1 = trim($_POST['previous_course1'] ?? '');
            $previous_course1_year = trim($_POST['previous_course1_year'] ?? '');
            $previous_course2 = trim($_POST['previous_course2'] ?? '');
            $previous_course2_year = trim($_POST['previous_course2_year'] ?? '');
            $previous_course3 = trim($_POST['previous_course3'] ?? '');
            $previous_course3_year = trim($_POST['previous_course3_year'] ?? '');
            
            // Employment History
            $current_coc_level = trim($_POST['current_coc_level'] ?? '');
            $certificate_no = trim($_POST['certificate_no'] ?? '');
            $employer1_name = trim($_POST['employer1_name'] ?? '');
            $employer1_position = trim($_POST['employer1_position'] ?? '');
            $employer1_from = $_POST['employer1_from'] ?? null;
            $employer1_to = $_POST['employer1_to'] ?? null;
            $employer2_name = trim($_POST['employer2_name'] ?? '');
            $employer2_position = trim($_POST['employer2_position'] ?? '');
            $employer2_from = $_POST['employer2_from'] ?? null;
            $employer2_to = $_POST['employer2_to'] ?? null;
            $employer3_name = trim($_POST['employer3_name'] ?? '');
            $employer3_position = trim($_POST['employer3_position'] ?? '');
            $employer3_from = $_POST['employer3_from'] ?? null;
            $employer3_to = $_POST['employer3_to'] ?? null;
            
            // Generate application number
            $app_count = 0;
            $result = $conn->query("SELECT COUNT(*) as count FROM applications");
            if ($result) {
                $app_count = $result->fetch_assoc()['count'];
            }
            // Get course from POST or use default
            $selected_course_key = isset($_POST['course_key']) ? trim($_POST['course_key']) : 'deck_rating_1';
            if (!isset($course_config[$selected_course_key])) {
                $selected_course_key = 'deck_rating_1';
            }
            $selected_course = $course_config[$selected_course_key];
            
            $application_number = $selected_course['app_prefix'] . '-' . date('Y') . '-' . str_pad($app_count + 1, 4, '0', STR_PAD_LEFT);
            
            // Handle file uploads
            $upload_dir = 'uploads/enrollment_documents/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $uploaded_files = [];
            $max_file_size = 5 * 1024 * 1024; // 5MB
            
            // Upload files
            if (isset($_FILES['character_reference'])) {
                $uploaded_files['character_reference'] = handleFileUpload('character_reference', $upload_dir, $max_file_size, $application_number);
            }
            if (isset($_FILES['disciplinary_report'])) {
                $uploaded_files['disciplinary_report'] = handleFileUpload('disciplinary_report', $upload_dir, $max_file_size, $application_number);
            }
            if (isset($_FILES['prerequisite_certificate'])) {
                $uploaded_files['prerequisite_certificate'] = handleFileUpload('prerequisite_certificate', $upload_dir, $max_file_size, $application_number);
            }
            if (isset($_FILES['sea_time_records'])) {
                $uploaded_files['sea_time_records'] = handleFileUpload('sea_time_records', $upload_dir, $max_file_size, $application_number);
            }
            if (isset($_FILES['educational_certificates'])) {
                $edu_files = [];
                foreach ($_FILES['educational_certificates']['name'] as $key => $name) {
                    if ($_FILES['educational_certificates']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_ext = pathinfo($name, PATHINFO_EXTENSION);
                        $new_filename = $application_number . '_edu_cert_' . $key . '_' . time() . '.' . $file_ext;
                        $target_path = $upload_dir . $new_filename;
                        if (move_uploaded_file($_FILES['educational_certificates']['tmp_name'][$key], $target_path)) {
                            $edu_files[] = $target_path;
                        }
                    }
                }
                $uploaded_files['educational_certificates'] = !empty($edu_files) ? implode('|', $edu_files) : null;
            }
            if (isset($_FILES['other_documents'])) {
                $other_files = [];
                foreach ($_FILES['other_documents']['name'] as $key => $name) {
                    if ($_FILES['other_documents']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_ext = pathinfo($name, PATHINFO_EXTENSION);
                        $new_filename = $application_number . '_other_' . $key . '_' . time() . '.' . $file_ext;
                        $target_path = $upload_dir . $new_filename;
                        if (move_uploaded_file($_FILES['other_documents']['tmp_name'][$key], $target_path)) {
                            $other_files[] = $target_path;
                        }
                    }
                }
                $uploaded_files['other_documents'] = !empty($other_files) ? implode('|', $other_files) : null;
            }
            
            // Handle signature (canvas drawing as base64 image)
            $signature_path = null;
            if (isset($_POST['signature']) && !empty($_POST['signature'])) {
                $signature_data = $_POST['signature'];
                // Check if it's a base64 image
                if (preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $signature_data)) {
                    // Extract image data
                    list($type, $signature_data) = explode(';', $signature_data);
                    list(, $signature_data) = explode(',', $signature_data);
                    $signature_data = base64_decode($signature_data);
                    
                    // Save as PNG file
                    $new_filename = $application_number . '_signature_' . time() . '.png';
                    $target_path = $upload_dir . $new_filename;
                    if (file_put_contents($target_path, $signature_data)) {
                        $signature_path = $target_path;
                    }
                }
            }
            
            // Check if application_type column exists
            $check_col = $conn->query("SHOW COLUMNS FROM applications LIKE 'application_type'");
            $has_application_type = $check_col && $check_col->num_rows > 0;
            
            // Prepare statement - store as returning student application
            $application_type = 'continuing_student_next_level';
            $course_type = 'Deck';
            $program_interest = $selected_course['program_interest'];
            
            // Build comprehensive data for storage
            $first_name = $given_names;
            $last_name = $family_name;
            $middle_name = ''; // Empty string stored in variable for bind_param
            $email = $nok_email; // Use NOK email if available
            $phone = $nok_phone; // Use NOK phone if available
            $address = $post_office_address;
            $city = $sub_province;
            $province = $province_state;
            
            // Check if signature_path column exists
            $check_signature = $conn->query("SHOW COLUMNS FROM applications LIKE 'signature_path'");
            $has_signature_path = $check_signature && $check_signature->num_rows > 0;
            
            if ($has_application_type) {
                if ($has_signature_path) {
                    $sql = "INSERT INTO applications (application_number, first_name, last_name, middle_name, date_of_birth, gender, email, phone, address, city, province, application_type, course_type, program_interest, signature_path, expression_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'submitted')";
                    $stmt = $conn->prepare($sql);
                    
                    if ($stmt) {
                        $stmt->bind_param('sssssssssssssss', 
                            $application_number, $first_name, $last_name, $middle_name, $date_of_birth, $gender, 
                            $email, $phone, $address, $city, $province, 
                            $application_type, $course_type, $program_interest, $signature_path
                        );
                    }
                } else {
                    $sql = "INSERT INTO applications (application_number, first_name, last_name, middle_name, date_of_birth, gender, email, phone, address, city, province, application_type, course_type, program_interest, expression_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'submitted')";
                    $stmt = $conn->prepare($sql);
                    
                    if ($stmt) {
                        $stmt->bind_param('ssssssssssssss', 
                            $application_number, $first_name, $last_name, $middle_name, $date_of_birth, $gender, 
                            $email, $phone, $address, $city, $province, 
                            $application_type, $course_type, $program_interest
                        );
                    }
                }
                
                if ($stmt) {
                    
                    if ($stmt->execute()) {
                        $application_id = $conn->insert_id;
                        
                        // Save uploaded documents to application_documents table
                        if (file_exists('pages/includes/document_helper.php')) {
                            require_once 'pages/includes/document_helper.php';
                            
                            if (function_exists('saveApplicationDocument')) {
                                // Save Character Reference
                                if (!empty($uploaded_files['character_reference'])) {
                                    saveApplicationDocument($application_id, 'character_reference', $uploaded_files['character_reference'], 'Character Reference');
                                }
                                
                                // Save Disciplinary History Report
                                if (!empty($uploaded_files['disciplinary_report'])) {
                                    saveApplicationDocument($application_id, 'disciplinary_report', $uploaded_files['disciplinary_report'], 'Disciplinary History Report');
                                }
                                
                                // Save Prerequisite Certificate(s)
                                if (!empty($uploaded_files['prerequisite_certificate'])) {
                                    saveApplicationDocument($application_id, 'prerequisite_certificate', $uploaded_files['prerequisite_certificate'], 'Prerequisite Certificate');
                                }
                                
                                // Save Educational Certificates (multiple files)
                                if (!empty($uploaded_files['educational_certificates'])) {
                                    $edu_files = explode('|', $uploaded_files['educational_certificates']);
                                    foreach ($edu_files as $edu_file) {
                                        if (!empty($edu_file)) {
                                            saveApplicationDocument($application_id, 'educational_certificates', $edu_file, 'Educational Certificate');
                                        }
                                    }
                                }
                                
                                // Save Other Supporting Documents (multiple files)
                                if (!empty($uploaded_files['other_documents'])) {
                                    $other_files = explode('|', $uploaded_files['other_documents']);
                                    foreach ($other_files as $other_file) {
                                        if (!empty($other_file)) {
                                            saveApplicationDocument($application_id, 'other_documents', $other_file, 'Other Supporting Document');
                                        }
                                    }
                                }
                                
                                // Save Sea Time Records
                                if (!empty($uploaded_files['sea_time_records'])) {
                                    saveApplicationDocument($application_id, 'sea_time_records', $uploaded_files['sea_time_records'], 'Sea Time Records');
                                }
                            }
                        }
                        
                        // Create requirements for this application
                        if (file_exists('pages/includes/workflow_helper.php')) {
                            require_once 'pages/includes/workflow_helper.php';
                            if (function_exists('createApplicationRequirements')) {
                                createApplicationRequirements($application_id, 'continuing_student_next_level');
                            }
                        }
                        
                        $app_num = htmlspecialchars($application_number) . "/" . date('Y');
                        $course_name = htmlspecialchars($selected_course['display_name']);
                        header('Location: index.html?success=1&type=enrollment&app=' . urlencode($app_num) . '&course=' . urlencode($course_name));
                        exit;
                    } else {
                        $message = "Error submitting application: " . $stmt->error;
                        $message_type = "error";
                    }
                    $stmt->close();
                } else {
                    $message = "Error preparing statement: " . $conn->error;
                    $message_type = "error";
                }
            } else {
                $message = "Error: Application type column not found. <a href='database/fix_migration.php' style='color: #1d4e89; text-decoration: underline; font-weight: bold;'>Click here to automatically fix this</a> or run the migration script manually.";
                $message_type = "error";
            }
            
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($course['display_name']); ?> Enrollment - PNG Maritime College</title>
  <link rel="stylesheet" href="css/sms_styles.css">
  <link rel="stylesheet" href="css/responsive.css">
  <style>
    .enroll-section {
      max-width: 1000px;
      margin: 20px auto;
      padding: 20px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    @media (min-width: 768px) {
      .enroll-section {
        padding: 30px;
      }
    }
    .form-header {
      text-align: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 3px solid #1d4e89;
    }
    .form-header h1 {
      color: #1d4e89;
      margin: 10px 0;
      font-size: 1.8rem;
      text-transform: uppercase;
    }
    .form-header .course-title {
      color: #1d4e89;
      font-size: 1.3rem;
      font-weight: bold;
      margin: 15px 0;
      text-transform: uppercase;
    }
    .contact-info {
      text-align: center;
      font-size: 0.9rem;
      color: #666;
      margin: 15px 0;
      line-height: 1.6;
    }
    .course-details {
      background: #e7f3ff;
      padding: 15px;
      border-radius: 5px;
      margin: 20px 0;
      border-left: 4px solid #1d4e89;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
    }
    .course-details div {
      text-align: center;
    }
    .course-details strong {
      display: block;
      color: #1d4e89;
      margin-bottom: 5px;
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
      margin: 25px 0;
      padding: 20px;
      background: #f9f9f9;
      border-radius: 8px;
      border: 1px solid #ddd;
    }
    .section-title {
      color: #1d4e89;
      margin: 0 0 20px 0;
      padding-bottom: 10px;
      border-bottom: 2px solid #1d4e89;
      font-size: 1.2rem;
      font-weight: bold;
    }
    .form-row {
      display: grid;
      grid-template-columns: 1fr;
      gap: 15px;
      margin-bottom: 15px;
    }
    @media (min-width: 768px) {
      .form-row {
        grid-template-columns: 1fr 1fr;
      }
      .form-row.full {
        grid-template-columns: 1fr;
      }
    }
    @media (min-width: 992px) {
      .form-row.three {
        grid-template-columns: 1fr 1fr 1fr;
      }
    }
    @media (min-width: 1200px) {
      .form-row.five {
        grid-template-columns: repeat(5, 1fr);
      }
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
    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: #1d4e89;
      box-shadow: 0 0 0 2px rgba(29, 78, 137, 0.1);
    }
    textarea {
      resize: vertical;
      min-height: 80px;
    }
    .checkbox-group {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 10px;
    }
    .checkbox-group input[type="checkbox"] {
      width: auto;
    }
    .checkbox-group label {
      margin: 0;
      font-weight: normal;
    }
    .checkbox-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 10px;
      margin: 15px 0;
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
      background: #fff3cd;
      padding: 15px;
      border-radius: 5px;
      margin: 20px 0;
      border-left: 4px solid #ffc107;
      font-size: 0.95rem;
    }
    .selection-criteria {
      background: #e7f3ff;
      padding: 20px;
      border-radius: 5px;
      margin: 20px 0;
      border-left: 4px solid #1d4e89;
    }
    .selection-criteria h3 {
      color: #1d4e89;
      margin-top: 0;
    }
    .selection-criteria ol {
      line-height: 2;
      padding-left: 20px;
    }
    .declaration-box {
      background: #fff;
      padding: 20px;
      border: 2px solid #1d4e89;
      border-radius: 5px;
      margin: 20px 0;
    }
    .declaration-box p {
      line-height: 1.8;
      color: #333;
      margin-bottom: 15px;
    }
    .signature-line {
      border-top: 1px solid #333;
      margin-top: 40px;
      padding-top: 5px;
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

  <section class="enroll-section">
    <div class="form-header">
      <h1>PAPUA NEW GUINEA MARITIME COLLEGE</h1>
      <div class="contact-info">
        <strong>Address:</strong> Kusbau Road PO Box 1040 Madang 511 Papua New Guinea<br>
        <strong>Phone:</strong> +675 422 2615 | <strong>Fax:</strong> +675 422 3113<br>
        <strong>Email:</strong> info@pngmc.ac.pg | <strong>Web:</strong> www.pngmc.ac.pg
      </div>
      <div class="course-title">2026 "<?php echo htmlspecialchars($course['name']); ?>" ENROLMENT APPLICATION FORM FOR <?php echo isset($course['sponsor_type']) && $course['sponsor_type'] === 'company' ? 'COMPANY SPONSORED' : 'SELF SPONSORING'; ?> STUDENT</div>
    </div>
    
    <div class="course-details">
      <div>
        <strong>Application Registration No.</strong>
        <div style="margin-top: 5px; font-size: 0.9rem;"><?php echo date('Y'); ?>/[Auto-generated]</div>
      </div>
      <div>
        <strong>Course Fee</strong>
        <div style="margin-top: 5px; font-size: 1.1rem; font-weight: bold; color: #1d4e89;"><?php echo htmlspecialchars($course['fee']); ?></div>
      </div>
      <div>
        <strong>Start Date</strong>
        <div style="margin-top: 5px; font-size: 1.1rem; font-weight: bold; color: #1d4e89;"><?php echo htmlspecialchars($course['start_date']); ?></div>
      </div>
      <div>
        <strong>Duration</strong>
        <div style="margin-top: 5px; font-size: 1.1rem; font-weight: bold; color: #1d4e89;"><?php echo htmlspecialchars($course['duration']); ?></div>
      </div>
      <div>
        <strong>Finish Date</strong>
        <div style="margin-top: 5px; font-size: 1.1rem; font-weight: bold; color: #1d4e89;"><?php echo htmlspecialchars($course['finish_date']); ?></div>
      </div>
      <?php if (!empty($course['app_open']) && !empty($course['app_close'])): ?>
      <div>
        <strong>Application Open</strong>
        <div style="margin-top: 5px; font-size: 1rem; font-weight: bold; color: #1d4e89;"><?php echo htmlspecialchars($course['app_open']); ?></div>
      </div>
      <div>
        <strong>Application Close</strong>
        <div style="margin-top: 5px; font-size: 1rem; font-weight: bold; color: #1d4e89;"><?php echo htmlspecialchars($course['app_close']); ?></div>
      </div>
      <?php endif; ?>
    </div>
    
    <div class="info-box">
      <strong>IMPORTANT:</strong> (ONLY APPLICANTS WHO MEET THE <?php echo isset($course['sponsor_type']) && $course['sponsor_type'] === 'company' ? 'ENTRY REQUIREMENTS' : 'SELECTION CRITERIA'; ?> STATED ON PAGE 2 OF THIS FORM WILL BE <?php echo isset($course['sponsor_type']) && $course['sponsor_type'] === 'company' ? 'CONSIDERED FOR ACCEPTANCE' : 'CONSIDERED'; ?>)
    </div>
    
    <?php if (!empty($course['selection_criteria'])): ?>
    <div class="selection-criteria">
      <h3><?php echo isset($course['sponsor_type']) && $course['sponsor_type'] === 'company' ? 'ENTRY REQUIREMENTS FOR' : 'SELECTION CRITERIA FOR SELF SPONSOR'; ?> <?php echo htmlspecialchars($course['name']); ?> TRAINING & ASSESSMENT IN 2026</h3>
      <ol>
        <?php 
        $item_num = 1;
        $in_sub_list = false;
        foreach ($course['selection_criteria'] as $index => $criterion) { 
          $is_sub_item = (strpos(trim($criterion), '(a)') === 0 || strpos(trim($criterion), '(b)') === 0);
          
          if ($is_sub_item) {
            if (!$in_sub_list) {
              echo '<ul style="margin-top: 5px; margin-left: 20px;">';
              $in_sub_list = true;
            }
            echo '<li>' . htmlspecialchars(trim($criterion)) . '</li>';
            // Check if next item is also a sub-item
            $next_is_sub = isset($course['selection_criteria'][$index + 1]) && 
                          (strpos(trim($course['selection_criteria'][$index + 1]), '(a)') === 0 || 
                           strpos(trim($course['selection_criteria'][$index + 1]), '(b)') === 0);
            if (!$next_is_sub) {
              echo '</ul></li>';
              $in_sub_list = false;
            }
          } else {
            if ($in_sub_list) {
              echo '</ul></li>';
              $in_sub_list = false;
            }
            // Check if next item is a sub-item
            $next_is_sub = isset($course['selection_criteria'][$index + 1]) && 
                          (strpos(trim($course['selection_criteria'][$index + 1]), '(a)') === 0 || 
                           strpos(trim($course['selection_criteria'][$index + 1]), '(b)') === 0);
            if ($next_is_sub) {
              echo '<li>' . htmlspecialchars($criterion);
            } else {
              echo '<li>' . htmlspecialchars($criterion) . '</li>';
            }
          }
        }
        if ($in_sub_list) {
          echo '</ul></li>';
        }
        ?>
      </ol>
    </div>
    <?php endif; ?>
    
    <?php if ($message): ?>
      <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" id="enrollmentForm" enctype="multipart/form-data">
      <input type="hidden" name="course_key" value="<?php echo htmlspecialchars($course_key); ?>">
      <!-- 1. Personal Details -->
      <div class="form-section">
        <h2 class="section-title">1. Personal Details</h2>
        <div class="form-row">
          <div>
            <label>CERB NO:</label>
            <input type="text" name="cerb_no" value="<?php echo htmlspecialchars($_POST['cerb_no'] ?? ''); ?>">
          </div>
          <div>
            <label>Family Name (Surname) <span class="required">*</span></label>
            <input type="text" name="family_name" required value="<?php echo htmlspecialchars($_POST['family_name'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Given Names <span class="required">*</span></label>
            <input type="text" name="given_names" required value="<?php echo htmlspecialchars($_POST['given_names'] ?? ''); ?>">
          </div>
          <div>
            <label>Gender <span class="required">*</span></label>
            <select name="gender" required>
              <option value="">Select Gender</option>
              <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
              <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Country of Origin</label>
            <input type="text" name="country_of_origin" value="<?php echo htmlspecialchars($_POST['country_of_origin'] ?? 'Papua New Guinea'); ?>">
          </div>
          <div>
            <label>Province/State</label>
            <input type="text" name="province_state" value="<?php echo htmlspecialchars($_POST['province_state'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>District</label>
            <input type="text" name="district" value="<?php echo htmlspecialchars($_POST['district'] ?? ''); ?>">
          </div>
          <div>
            <label>Date of Birth <span class="required">*</span></label>
            <input type="date" name="date_of_birth" required value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Sub Province</label>
            <input type="text" name="sub_province" value="<?php echo htmlspecialchars($_POST['sub_province'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Place of Birth</label>
            <input type="text" name="place_of_birth" value="<?php echo htmlspecialchars($_POST['place_of_birth'] ?? ''); ?>">
          </div>
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
        </div>
        <div class="form-row">
          <div>
            <label>Uniform Sizes:</label>
            <div class="form-row five" style="margin-top: 10px; gap: 10px;">
              <div>
                <label style="font-size: 0.85rem;">Short:</label>
                <input type="text" name="uniform_short" placeholder="Size" value="<?php echo htmlspecialchars($_POST['uniform_short'] ?? ''); ?>">
              </div>
              <div>
                <label style="font-size: 0.85rem;">Waist:</label>
                <input type="text" name="uniform_waist" placeholder="Size" value="<?php echo htmlspecialchars($_POST['uniform_waist'] ?? ''); ?>">
              </div>
              <div>
                <label style="font-size: 0.85rem;">Shirt:</label>
                <input type="text" name="uniform_shirt" placeholder="Size" value="<?php echo htmlspecialchars($_POST['uniform_shirt'] ?? ''); ?>">
              </div>
              <div>
                <label style="font-size: 0.85rem;">Shoe:</label>
                <input type="text" name="uniform_shoe" placeholder="Size" value="<?php echo htmlspecialchars($_POST['uniform_shoe'] ?? ''); ?>">
              </div>
              <div>
                <label style="font-size: 0.85rem;">Overall:</label>
                <input type="text" name="uniform_overall" placeholder="Size" value="<?php echo htmlspecialchars($_POST['uniform_overall'] ?? ''); ?>">
              </div>
            </div>
          </div>
        </div>
        <div class="form-row full">
          <div>
            <label>Post Office Address <span class="required">*</span></label>
            <textarea name="post_office_address" required rows="3"><?php echo htmlspecialchars($_POST['post_office_address'] ?? ''); ?></textarea>
          </div>
        </div>
        
        <!-- Next of Kin (NOK) Details -->
        <h3 style="color: #1d4e89; margin-top: 25px; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 1px solid #ddd;">Next of Kin (NOK) Details</h3>
        <div class="form-row">
          <div>
            <label>Next of Kin (NOK): Name <span class="required">*</span></label>
            <input type="text" name="nok_name" required value="<?php echo htmlspecialchars($_POST['nok_name'] ?? ''); ?>">
          </div>
          <div>
            <label>Relationship <span class="required">*</span></label>
            <input type="text" name="nok_relationship" placeholder="e.g., Father, Mother, etc." required value="<?php echo htmlspecialchars($_POST['nok_relationship'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row full">
          <div>
            <label>Post Office Address (NOK) <span class="required">*</span></label>
            <textarea name="nok_post_address" required rows="2"><?php echo htmlspecialchars($_POST['nok_post_address'] ?? ''); ?></textarea>
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Phone No <span class="required">*</span></label>
            <input type="tel" name="nok_phone" required value="<?php echo htmlspecialchars($_POST['nok_phone'] ?? ''); ?>">
          </div>
          <div>
            <label>Email</label>
            <input type="email" name="nok_email" value="<?php echo htmlspecialchars($_POST['nok_email'] ?? ''); ?>">
          </div>
        </div>
      </div>

      <!-- 2. Educational Background -->
      <div class="form-section">
        <h2 class="section-title">2. Educational Background</h2>
        <p style="margin-bottom: 15px; color: #666;"><em>Highest level of study you have completed:</em></p>
        <div class="checkbox-container">
          <div class="checkbox-group">
            <input type="checkbox" name="highest_education[]" value="Vocational" id="edu_vocational" <?php echo (isset($_POST['highest_education']) && in_array('Vocational', $_POST['highest_education'])) ? 'checked' : ''; ?>>
            <label for="edu_vocational" style="margin: 0;">Vocational</label>
          </div>
          <div class="checkbox-group">
            <input type="checkbox" name="highest_education[]" value="Secondary" id="edu_secondary" <?php echo (isset($_POST['highest_education']) && in_array('Secondary', $_POST['highest_education'])) ? 'checked' : ''; ?>>
            <label for="edu_secondary" style="margin: 0;">Secondary</label>
          </div>
          <div class="checkbox-group">
            <input type="checkbox" name="highest_education[]" value="Technical College" id="edu_technical" <?php echo (isset($_POST['highest_education']) && in_array('Technical College', $_POST['highest_education'])) ? 'checked' : ''; ?>>
            <label for="edu_technical" style="margin: 0;">Technical College</label>
          </div>
          <div class="checkbox-group">
            <input type="checkbox" name="highest_education[]" value="University" id="edu_university" <?php echo (isset($_POST['highest_education']) && in_array('University', $_POST['highest_education'])) ? 'checked' : ''; ?>>
            <label for="edu_university" style="margin: 0;">University / Other:</label>
          </div>
        </div>
        <div class="form-row" id="university_other_div" style="display: none;">
          <div>
            <label>Specify (if University/Other):</label>
            <input type="text" name="highest_education_other" value="<?php echo htmlspecialchars($_POST['highest_education_other'] ?? ''); ?>">
          </div>
        </div>
        
        <div class="info-box" style="margin: 20px 0;">
          <strong>Note:</strong> Copies of certificates gained must accompany this application. DO NOT SEND ORIGINALS.
        </div>
        
        <p style="margin: 20px 0 10px 0; color: #666;"><strong>Grade achieved:</strong></p>
        <div class="checkbox-container">
          <div class="checkbox-group">
            <input type="checkbox" name="grade_10" value="1" id="grade_10" <?php echo (isset($_POST['grade_10'])) ? 'checked' : ''; ?>>
            <label for="grade_10" style="margin: 0;">Grade 10</label>
          </div>
          <div class="checkbox-group">
            <input type="checkbox" name="grade_12" value="1" id="grade_12" <?php echo (isset($_POST['grade_12'])) ? 'checked' : ''; ?>>
            <label for="grade_12" style="margin: 0;">Grade 12</label>
          </div>
          <div class="checkbox-group">
            <input type="checkbox" name="diploma" value="1" id="diploma" <?php echo (isset($_POST['diploma'])) ? 'checked' : ''; ?>>
            <label for="diploma" style="margin: 0;">Diploma</label>
          </div>
          <div class="checkbox-group">
            <input type="checkbox" name="degree" value="1" id="degree" <?php echo (isset($_POST['degree'])) ? 'checked' : ''; ?>>
            <label for="degree" style="margin: 0;">Degree</label>
          </div>
        </div>
        
        <p style="margin: 25px 0 10px 0; color: #666;"><strong>List Schools/Colleges Last Attended (Most Recent One First):</strong></p>
        <div class="form-row">
          <div>
            <label>1. School/College Name</label>
            <input type="text" name="school1_name" value="<?php echo htmlspecialchars($_POST['school1_name'] ?? ''); ?>">
          </div>
          <div>
            <label>Year</label>
            <input type="text" name="school1_year" placeholder="e.g., 2023" value="<?php echo htmlspecialchars($_POST['school1_year'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>2. School/College Name</label>
            <input type="text" name="school2_name" value="<?php echo htmlspecialchars($_POST['school2_name'] ?? ''); ?>">
          </div>
          <div>
            <label>Year</label>
            <input type="text" name="school2_year" placeholder="e.g., 2021" value="<?php echo htmlspecialchars($_POST['school2_year'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>3. School/College Name</label>
            <input type="text" name="school3_name" value="<?php echo htmlspecialchars($_POST['school3_name'] ?? ''); ?>">
          </div>
          <div>
            <label>Year</label>
            <input type="text" name="school3_year" placeholder="e.g., 2019" value="<?php echo htmlspecialchars($_POST['school3_year'] ?? ''); ?>">
          </div>
        </div>
      </div>

      <!-- 3. <?php echo isset($course['sponsor_type']) && $course['sponsor_type'] === 'company' ? 'Company Sponsor' : 'Sponsorship'; ?> Details -->
      <div class="form-section">
        <h2 class="section-title">3. <?php echo isset($course['sponsor_type']) && $course['sponsor_type'] === 'company' ? 'Company Sponsor' : 'Sponsorship'; ?> Details</h2>
        <div class="form-row">
          <div>
            <label>Sponsor's Name <span class="required">*</span></label>
            <input type="text" name="sponsor_name" required value="<?php echo htmlspecialchars($_POST['sponsor_name'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row full">
          <div>
            <label>Postal Address <span class="required">*</span></label>
            <textarea name="sponsor_postal_address" required rows="3"><?php echo htmlspecialchars($_POST['sponsor_postal_address'] ?? ''); ?></textarea>
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Contact Person</label>
            <input type="text" name="sponsor_contact_person" value="<?php echo htmlspecialchars($_POST['sponsor_contact_person'] ?? ''); ?>">
          </div>
          <div>
            <label>Email Address</label>
            <input type="email" name="sponsor_email" value="<?php echo htmlspecialchars($_POST['sponsor_email'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Mobile No / (s)</label>
            <input type="tel" name="sponsor_mobile" value="<?php echo htmlspecialchars($_POST['sponsor_mobile'] ?? ''); ?>">
          </div>
          <div>
            <label>Landline Phone No</label>
            <input type="tel" name="sponsor_landline" value="<?php echo htmlspecialchars($_POST['sponsor_landline'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Fax No</label>
            <input type="text" name="sponsor_fax" value="<?php echo htmlspecialchars($_POST['sponsor_fax'] ?? ''); ?>">
          </div>
          <div>
            <label>Designation</label>
            <input type="text" name="sponsor_designation" value="<?php echo htmlspecialchars($_POST['sponsor_designation'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Date</label>
            <input type="date" name="sponsor_date" value="<?php echo htmlspecialchars($_POST['sponsor_date'] ?? ''); ?>">
          </div>
        </div>
      </div>

      <!-- 4. Previous Course (CoC or Solas) Enrolment Information -->
      <div class="form-section">
        <h2 class="section-title">4. Previous Course (CoC or Solas) Enrolment Information At PNGMC or Elsewhere</h2>
        <div class="form-row">
          <div>
            <label>Most Recent Course Attended:</label>
            <input type="text" name="previous_course1" placeholder="Course name" value="<?php echo htmlspecialchars($_POST['previous_course1'] ?? ''); ?>">
          </div>
          <div>
            <label>Year</label>
            <input type="text" name="previous_course1_year" placeholder="e.g., 2023" value="<?php echo htmlspecialchars($_POST['previous_course1_year'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Course:</label>
            <input type="text" name="previous_course2" placeholder="Course name" value="<?php echo htmlspecialchars($_POST['previous_course2'] ?? ''); ?>">
          </div>
          <div>
            <label>Year</label>
            <input type="text" name="previous_course2_year" placeholder="e.g., 2021" value="<?php echo htmlspecialchars($_POST['previous_course2_year'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Course:</label>
            <input type="text" name="previous_course3" placeholder="Course name" value="<?php echo htmlspecialchars($_POST['previous_course3'] ?? ''); ?>">
          </div>
          <div>
            <label>Year</label>
            <input type="text" name="previous_course3_year" placeholder="e.g., 2019" value="<?php echo htmlspecialchars($_POST['previous_course3_year'] ?? ''); ?>">
          </div>
        </div>
      </div>

      <!-- 5. Employment History (Last 3 Vessels) -->
      <div class="form-section">
        <h2 class="section-title">5. Employment History (Last 3 Vessels)</h2>
        <div class="form-row">
          <div>
            <label>Current Certificate of Competency Level</label>
            <input type="text" name="current_coc_level" placeholder="e.g., Deck Rating Class 2" value="<?php echo htmlspecialchars($_POST['current_coc_level'] ?? ''); ?>">
          </div>
          <div>
            <label>Certificate NO</label>
            <input type="text" name="certificate_no" value="<?php echo htmlspecialchars($_POST['certificate_no'] ?? ''); ?>">
          </div>
        </div>
        
        <h3 style="color: #1d4e89; margin-top: 20px; margin-bottom: 15px; font-size: 1rem;">Vessel 1 (Most Recent)</h3>
        <div class="form-row">
          <div>
            <label>Name of Employer</label>
            <input type="text" name="employer1_name" value="<?php echo htmlspecialchars($_POST['employer1_name'] ?? ''); ?>">
          </div>
          <div>
            <label>Position Held</label>
            <input type="text" name="employer1_position" value="<?php echo htmlspecialchars($_POST['employer1_position'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Date From</label>
            <input type="date" name="employer1_from" value="<?php echo htmlspecialchars($_POST['employer1_from'] ?? ''); ?>">
          </div>
          <div>
            <label>Date To</label>
            <input type="date" name="employer1_to" value="<?php echo htmlspecialchars($_POST['employer1_to'] ?? ''); ?>">
          </div>
        </div>
        
        <h3 style="color: #1d4e89; margin-top: 20px; margin-bottom: 15px; font-size: 1rem;">Vessel 2</h3>
        <div class="form-row">
          <div>
            <label>Name of Employer</label>
            <input type="text" name="employer2_name" value="<?php echo htmlspecialchars($_POST['employer2_name'] ?? ''); ?>">
          </div>
          <div>
            <label>Position Held</label>
            <input type="text" name="employer2_position" value="<?php echo htmlspecialchars($_POST['employer2_position'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Date From</label>
            <input type="date" name="employer2_from" value="<?php echo htmlspecialchars($_POST['employer2_from'] ?? ''); ?>">
          </div>
          <div>
            <label>Date To</label>
            <input type="date" name="employer2_to" value="<?php echo htmlspecialchars($_POST['employer2_to'] ?? ''); ?>">
          </div>
        </div>
        
        <h3 style="color: #1d4e89; margin-top: 20px; margin-bottom: 15px; font-size: 1rem;">Vessel 3</h3>
        <div class="form-row">
          <div>
            <label>Name of Employer</label>
            <input type="text" name="employer3_name" value="<?php echo htmlspecialchars($_POST['employer3_name'] ?? ''); ?>">
          </div>
          <div>
            <label>Position Held</label>
            <input type="text" name="employer3_position" value="<?php echo htmlspecialchars($_POST['employer3_position'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Date From</label>
            <input type="date" name="employer3_from" value="<?php echo htmlspecialchars($_POST['employer3_from'] ?? ''); ?>">
          </div>
          <div>
            <label>Date To</label>
            <input type="date" name="employer3_to" value="<?php echo htmlspecialchars($_POST['employer3_to'] ?? ''); ?>">
          </div>
        </div>
      </div>

      <!-- 6. Required Documents -->
      <div class="form-section">
        <h2 class="section-title">6. Required Documents</h2>
        <div class="info-box" style="margin-bottom: 20px;">
          <strong>Note:</strong> Please upload copies of all required documents. Accepted formats: PDF, DOC, DOCX, JPG, JPEG, PNG. Maximum file size: 5MB per file.
        </div>
        
        <?php
        // Determine which documents are required based on selection criteria
        $requires_character_ref = false;
        $requires_disciplinary_report = false;
        $requires_certificates = false;
        $requires_sea_time_records = false;
        $requires_educational_certs = true; // Always required as mentioned in educational section
        
        foreach ($course['selection_criteria'] as $criterion) {
            if (stripos($criterion, 'Character Reference') !== false) {
                $requires_character_ref = true;
            }
            if (stripos($criterion, 'Disciplinary History') !== false) {
                $requires_disciplinary_report = true;
            }
            if (stripos($criterion, 'Certificate') !== false || stripos($criterion, 'Ticket') !== false) {
                $requires_certificates = true;
            }
            if (stripos($criterion, 'Sea time records') !== false || stripos($criterion, 'CERB') !== false) {
                $requires_sea_time_records = true;
            }
        }
        ?>
        
        <?php if ($requires_character_ref): ?>
        <div class="form-row full">
          <div>
            <label>Character Reference <span class="required">*</span></label>
            <input type="file" name="character_reference" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
            <small style="color: #666;">Upload a recent Character Reference from <?php echo isset($course['sponsor_type']) && $course['sponsor_type'] === 'company' ? 'the Sponsoring Company' : 'the current Employer'; ?></small>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if ($requires_disciplinary_report): ?>
        <div class="form-row full">
          <div>
            <label>Disciplinary History Report <span class="required">*</span></label>
            <input type="file" name="disciplinary_report" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
            <small style="color: #666;">Upload Disciplinary History Report for the past two (2) years from <?php echo isset($course['sponsor_type']) && $course['sponsor_type'] === 'company' ? 'the Sponsoring Company' : 'the current Employer'; ?></small>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if ($requires_certificates): ?>
        <div class="form-row full">
          <div>
            <label>Prerequisite Certificate(s) <span class="required">*</span></label>
            <input type="file" name="prerequisite_certificate" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
            <small style="color: #666;">Upload copy of prerequisite certificate(s) as specified in the entry requirements</small>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if ($requires_sea_time_records): ?>
        <div class="form-row full">
          <div>
            <label>Sea Time Records from CERB <span class="required">*</span></label>
            <input type="file" name="sea_time_records" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
            <small style="color: #666;">Upload photocopies of Sea time records from CERB</small>
          </div>
        </div>
        <?php endif; ?>
        
        <div class="form-row full">
          <div>
            <label>Educational Certificates <span class="required">*</span></label>
            <input type="file" name="educational_certificates[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple required>
            <small style="color: #666;">Upload copies of all educational certificates (Grade 10, Grade 12, Diploma, Degree, etc.). You can upload multiple files.</small>
          </div>
        </div>
        
        <div class="form-row full">
          <div>
            <label>Other Supporting Documents</label>
            <input type="file" name="other_documents[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple>
            <small style="color: #666;">Upload any other supporting documents (examination results, training certificates, etc.). You can upload multiple files.</small>
          </div>
        </div>
      </div>

      <!-- 7. Declaration -->
      <div class="form-section">
        <h2 class="section-title">7. Declaration</h2>
        <div class="declaration-box">
          <p>
            I, <strong><?php echo htmlspecialchars($_POST['given_names'] ?? '[Your Name]'); ?> <?php echo htmlspecialchars($_POST['family_name'] ?? '[Your Surname]'); ?></strong>, 
            hereby declare that all information provided in this enrollment application form is true and correct to the best of my knowledge. 
            I understand that providing false or misleading information may result in the rejection of my application or termination of enrollment.
          </p>
          <p>
            I acknowledge that I have read and understood the <?php echo isset($course['sponsor_type']) && $course['sponsor_type'] === 'company' ? 'Entry Requirements' : 'Selection Criteria'; ?> for <?php echo htmlspecialchars($course['display_name']); ?> Training 2026, and I meet all the requirements stated therein.
          </p>
          <p>
            <?php if (isset($course['sponsor_type']) && $course['sponsor_type'] === 'company'): ?>
            I understand that the Sponsoring Company MUST PAY 100% of the Full Course Fee (<?php echo htmlspecialchars($course['fee']); ?>) two (2) weeks prior to the commencement of the course. 
            Failure to comply with this requirement will result in the student being denied admission. Course fee payment of less than 100% before admission WILL NOT be accepted.
            <?php else: ?>
            I understand that I MUST PAY 100% of the Full Course Fee (<?php echo htmlspecialchars($course['fee']); ?>) immediately upon being given an acceptance letter. 
            Failure to do so will result in denial of admission.
            <?php endif; ?>
          </p>
          <div class="checkbox-group" style="margin-top: 20px;">
            <input type="checkbox" name="declaration_agreed" value="1" id="declaration_agreed" required <?php echo (isset($_POST['declaration_agreed'])) ? 'checked' : ''; ?>>
            <label for="declaration_agreed" style="margin: 0; font-weight: 600;"><span class="required">*</span> I agree to the above declaration</label>
          </div>
          <div class="signature-line" style="margin-top: 40px;">
            <label>Signature <span class="required">*</span></label>
            <div style="margin-top: 10px;">
              <canvas id="signatureCanvas" width="600" height="200" style="border: 2px solid #333; cursor: crosshair; background: white; display: block; margin: 0 auto;"></canvas>
              <input type="hidden" name="signature" id="signatureData" required>
              <div style="text-align: center; margin-top: 10px;">
                <button type="button" id="clearSignature" style="padding: 8px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 0.9rem;">Clear Signature</button>
              </div>
              <small style="color: #666; display: block; margin-top: 10px; text-align: center;">Please draw your signature in the box above using your mouse or touch screen</small>
            </div>
          </div>
        </div>
      </div>

      <button type="submit" class="btn-submit">Submit Enrollment Application</button>
    </form>
  </section>

  <script>
    // Show/hide University/Other input field
    document.addEventListener('DOMContentLoaded', function() {
      const universityCheckbox = document.getElementById('edu_university');
      const universityOtherDiv = document.getElementById('university_other_div');
      
      if (universityCheckbox) {
        universityCheckbox.addEventListener('change', function() {
          if (this.checked) {
            universityOtherDiv.style.display = 'block';
          } else {
            universityOtherDiv.style.display = 'none';
          }
        });
        
        // Check on page load
        if (universityCheckbox.checked) {
          universityOtherDiv.style.display = 'block';
        }
      }
      
      // Signature Pad Functionality
      const canvas = document.getElementById('signatureCanvas');
      const ctx = canvas.getContext('2d');
      const signatureInput = document.getElementById('signatureData');
      const clearBtn = document.getElementById('clearSignature');
      let isDrawing = false;
      let lastX = 0;
      let lastY = 0;
      
      // Set canvas background to white
      ctx.fillStyle = 'white';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      ctx.strokeStyle = '#000';
      ctx.lineWidth = 2;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      
      function startDrawing(e) {
        isDrawing = true;
        const rect = canvas.getBoundingClientRect();
        lastX = (e.clientX || e.touches[0].clientX) - rect.left;
        lastY = (e.clientY || e.touches[0].clientY) - rect.top;
      }
      
      function draw(e) {
        if (!isDrawing) return;
        e.preventDefault();
        
        const rect = canvas.getBoundingClientRect();
        const currentX = (e.clientX || e.touches[0].clientX) - rect.left;
        const currentY = (e.clientY || e.touches[0].clientY) - rect.top;
        
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(currentX, currentY);
        ctx.stroke();
        
        lastX = currentX;
        lastY = currentY;
        
        // Update hidden input with signature data
        signatureInput.value = canvas.toDataURL('image/png');
      }
      
      function stopDrawing() {
        if (isDrawing) {
          isDrawing = false;
          signatureInput.value = canvas.toDataURL('image/png');
        }
      }
      
      // Mouse events
      canvas.addEventListener('mousedown', startDrawing);
      canvas.addEventListener('mousemove', draw);
      canvas.addEventListener('mouseup', stopDrawing);
      canvas.addEventListener('mouseout', stopDrawing);
      
      // Touch events for mobile
      canvas.addEventListener('touchstart', function(e) {
        e.preventDefault();
        startDrawing(e);
      });
      canvas.addEventListener('touchmove', function(e) {
        e.preventDefault();
        draw(e);
      });
      canvas.addEventListener('touchend', function(e) {
        e.preventDefault();
        stopDrawing();
      });
      
      // Clear signature
      clearBtn.addEventListener('click', function() {
        ctx.fillStyle = 'white';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        signatureInput.value = '';
      });
      
      // Update signature data before form submission
      document.getElementById('enrollmentForm').addEventListener('submit', function(e) {
        if (signatureInput.value === '') {
          // Check if canvas has any drawing
          const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
          const hasDrawing = imageData.data.some((channel, index) => {
            return index % 4 !== 3 && channel !== 255; // Check if not all white
          });
          
          if (!hasDrawing) {
            e.preventDefault();
            alert('Please provide your signature by drawing in the signature box.');
            return false;
          }
          
          signatureInput.value = canvas.toDataURL('image/png');
        }
      });
    });
  </script>

  <footer>
    &copy; 2025 PNG Maritime College. All rights reserved.
  </footer>
</body>
</html>

