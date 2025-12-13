<?php
/**
 * Application Validation Helper
 * Validates application completeness and required criteria before submission
 */

/**
 * Validate application data
 * Returns array with 'valid' (boolean) and 'errors' (array of error messages)
 */
function validateApplication($data) {
    $errors = [];
    $valid = true;
    
    // Required personal information
    if (empty(trim($data['first_name'] ?? ''))) {
        $errors[] = "First Name is required";
        $valid = false;
    }
    
    if (empty(trim($data['last_name'] ?? ''))) {
        $errors[] = "Last Name is required";
        $valid = false;
    }
    
    // Email validation
    $email = trim($data['email'] ?? '');
    if (empty($email)) {
        $errors[] = "Email address is required";
        $valid = false;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
        $valid = false;
    }
    
    // Phone validation
    $phone = trim($data['phone'] ?? '');
    if (empty($phone)) {
        $errors[] = "Phone number is required";
        $valid = false;
    } elseif (strlen($phone) < 7) {
        $errors[] = "Please enter a valid phone number (minimum 7 digits)";
        $valid = false;
    }
    
    // Education level validation
    $education_level = trim($data['education_level'] ?? '');
    if (empty($education_level)) {
        $errors[] = "Education Level is required";
        $valid = false;
    } elseif (!in_array($education_level, ['Grade 10', 'Grade 12'])) {
        $errors[] = "Please select a valid education level (Grade 10 or Grade 12)";
        $valid = false;
    }
    
    // Program interest validation
    $program_interest = trim($data['program_interest'] ?? '');
    if (empty($program_interest)) {
        $errors[] = "Program of Interest is required";
        $valid = false;
    } elseif (!in_array($program_interest, ['GP Course', 'Cadet Officers Program'])) {
        $errors[] = "Please select a valid program (GP Course or Cadet Officers Program)";
        $valid = false;
    }
    
    // Program-specific validation
    if ($education_level === 'Grade 12' && $program_interest === 'Cadet Officers Program') {
        // For Grade 12 Cadet Officers Program, require Grade 12 passed confirmation
        if (empty($data['grade_12_passed'])) {
            $errors[] = "You must confirm that you have passed Grade 12 for the Cadet Officers Program";
            $valid = false;
        }
        
        // Require at least one grade or GPA for Cadet Officers Program
        $maths_grade = trim($data['maths_grade'] ?? '');
        $physics_grade = trim($data['physics_grade'] ?? '');
        $english_grade = trim($data['english_grade'] ?? '');
        $overall_gpa = $data['overall_gpa'] ?? null;
        
        $has_grade = !empty($maths_grade) || !empty($physics_grade) || !empty($english_grade);
        $has_gpa = !empty($overall_gpa) && is_numeric($overall_gpa) && floatval($overall_gpa) > 0;
        
        if (!$has_grade && !$has_gpa) {
            $errors[] = "For Cadet Officers Program, please provide at least one subject grade (Mathematics, Physics, or English) or your Overall GPA";
            $valid = false;
        }
    }
    
    // Validate program and education level match
    if ($education_level === 'Grade 10' && $program_interest !== 'GP Course') {
        $errors[] = "Grade 10 students must apply for GP Course";
        $valid = false;
    }
    
    if ($education_level === 'Grade 12' && $program_interest !== 'Cadet Officers Program') {
        $errors[] = "Grade 12 students must apply for Cadet Officers Program";
        $valid = false;
    }
    
    // Date of birth validation (if provided)
    if (!empty($data['date_of_birth'])) {
        $dob = $data['date_of_birth'];
        $dob_timestamp = strtotime($dob);
        $min_age_date = strtotime('-16 years'); // Minimum age 16
        $max_age_date = strtotime('-50 years'); // Maximum age 50
        
        if ($dob_timestamp === false) {
            $errors[] = "Please enter a valid date of birth";
            $valid = false;
        } elseif ($dob_timestamp > $min_age_date) {
            $errors[] = "You must be at least 16 years old to apply";
            $valid = false;
        } elseif ($dob_timestamp < $max_age_date) {
            $errors[] = "Applicants must be under 50 years old";
            $valid = false;
        }
    }
    
    return [
        'valid' => $valid,
        'errors' => $errors
    ];
}

/**
 * Send validation error notification email to applicant
 */
function sendValidationErrorEmail($email, $first_name, $last_name, $errors) {
    $to = $email;
    $subject = "PNG Maritime College - Application Incomplete";
    $headers = "From: PNG Maritime College <noreply@pngmc.edu.pg>\r\n";
    $headers .= "Reply-To: PNG Maritime College <info@pngmc.edu.pg>\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $error_list = "<ul>";
    foreach ($errors as $error) {
        $error_list .= "<li>" . htmlspecialchars($error) . "</li>";
    }
    $error_list .= "</ul>";
    
    $message = "<html><body>";
    $message .= "<h2>Application Submission - Missing Information</h2>";
    $message .= "<p>Dear " . htmlspecialchars($first_name . ' ' . $last_name) . ",</p>";
    $message .= "<p>Thank you for your interest in PNG Maritime College. However, your application submission was incomplete.</p>";
    $message .= "<p><strong>Please complete the following requirements:</strong></p>";
    $message .= $error_list;
    $message .= "<p>Please visit our website to complete and resubmit your application with all required information.</p>";
    $message .= "<p>If you have any questions, please contact us.</p>";
    $message .= "<p>Best regards,<br>PNG Maritime College</p>";
    $message .= "</body></html>";
    
    @mail($to, $subject, $message, $headers);
}

?>

