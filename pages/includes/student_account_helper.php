<?php
/**
 * Student Account Helper Functions
 * Functions for managing student accounts, login, and account status
 */

require_once __DIR__ . '/db_config.php';

/**
 * Create a student account when student is enrolled
 */
function createStudentAccount($student_id, $student_number, $email = null, $phone = null, $initial_password = null) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Check if student_accounts table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'student_accounts'");
    if ($table_check->num_rows == 0) {
        $conn->close();
        return false;
    }
    
    // Check if account already exists
    $check = $conn->query("SELECT account_id FROM student_accounts WHERE student_id = $student_id OR student_number = '$student_number'");
    if ($check->num_rows > 0) {
        $conn->close();
        return false; // Account already exists
    }
    
    // Generate username (student number or email-based)
    $username = $student_number;
    if ($email) {
        $username = strtolower(explode('@', $email)[0]);
        // Check if username already exists
        $username_check = $conn->query("SELECT account_id FROM student_accounts WHERE username = '$username'");
        if ($username_check->num_rows > 0) {
            $username = $student_number; // Fallback to student number
        }
    }
    
    // Generate password if not provided
    if (!$initial_password) {
        // Default password: Student number + last 4 digits of student_id
        $initial_password = $student_number . substr(str_pad($student_id, 4, '0', STR_PAD_LEFT), -4);
    }
    
    // Hash password
    $password_hash = password_hash($initial_password, PASSWORD_DEFAULT);
    
    // Create account
    $stmt = $conn->prepare("INSERT INTO student_accounts (student_id, student_number, username, password_hash, email, phone, account_status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("isssss", $student_id, $student_number, $username, $password_hash, $email, $phone);
    
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

/**
 * Activate student account (when returning student)
 */
function activateStudentAccount($student_id) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Update student account status
    $table_check = $conn->query("SHOW TABLES LIKE 'student_accounts'");
    if ($table_check->num_rows > 0) {
        $conn->query("UPDATE student_accounts SET account_status = 'active', updated_at = CURRENT_TIMESTAMP WHERE student_id = $student_id");
    }
    
    // Update student account_status
    $col_check = $conn->query("SHOW COLUMNS FROM students LIKE 'account_status'");
    if ($col_check->num_rows > 0) {
        $conn->query("UPDATE students SET account_status = 'active', status = 'active' WHERE student_id = $student_id");
    }
    
    $conn->close();
    return true;
}

/**
 * Put student account on hold (when course ends)
 */
function putAccountOnHold($student_id) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Update student account status
    $table_check = $conn->query("SHOW TABLES LIKE 'student_accounts'");
    if ($table_check->num_rows > 0) {
        $conn->query("UPDATE student_accounts SET account_status = 'on_hold', updated_at = CURRENT_TIMESTAMP WHERE student_id = $student_id");
    }
    
    // Update student account_status
    $col_check = $conn->query("SHOW COLUMNS FROM students LIKE 'account_status'");
    if ($col_check->num_rows > 0) {
        $conn->query("UPDATE students SET account_status = 'on_hold' WHERE student_id = $student_id");
    }
    
    $conn->close();
    return true;
}

/**
 * Add course to student's course history
 */
function addStudentCourseHistory($student_id, $student_number, $course_name, $enrollment_date, $course_end_date = null, $course_type = null, $application_id = null, $status = 'enrolled') {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'student_course_history'");
    if ($table_check->num_rows == 0) {
        $conn->close();
        return false;
    }
    
    $stmt = $conn->prepare("INSERT INTO student_course_history (student_id, student_number, course_name, course_type, enrollment_date, course_end_date, status, application_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssi", $student_id, $student_number, $course_name, $course_type, $enrollment_date, $course_end_date, $status, $application_id);
    
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

/**
 * Get student account by student_id or student_number
 */
function getStudentAccount($identifier, $by = 'student_id') {
    $conn = getDBConnection();
    if (!$conn) return null;
    
    $table_check = $conn->query("SHOW TABLES LIKE 'student_accounts'");
    if ($table_check->num_rows == 0) {
        $conn->close();
        return null;
    }
    
    $field = ($by === 'student_number') ? 'student_number' : 'student_id';
    $result = $conn->query("SELECT sa.*, s.first_name, s.last_name, s.account_status as student_account_status 
        FROM student_accounts sa 
        LEFT JOIN students s ON sa.student_id = s.student_id 
        WHERE sa.$field = '$identifier'");
    
    $account = $result->fetch_assoc();
    $conn->close();
    
    return $account;
}

/**
 * Check if student account exists
 */
function studentAccountExists($student_id) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    $table_check = $conn->query("SHOW TABLES LIKE 'student_accounts'");
    if ($table_check->num_rows == 0) {
        $conn->close();
        return false;
    }
    
    $result = $conn->query("SELECT account_id FROM student_accounts WHERE student_id = $student_id");
    $exists = $result->num_rows > 0;
    $conn->close();
    
    return $exists;
}

/**
 * Link continuing student application to existing student account
 */
function linkApplicationToStudentAccount($application_id, $student_id) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Update application with student_id
    $conn->query("UPDATE applications SET student_id = $student_id, previous_student_id = $student_id WHERE application_id = $application_id");
    
    // Activate the student account
    activateStudentAccount($student_id);
    
    $conn->close();
    return true;
}

/**
 * Send notification to student
 */
function sendStudentNotification($student_id, $from_department, $title, $message, $notification_type = 'info', $action_url = null, $created_by = null) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'student_notifications'");
    if ($table_check->num_rows == 0) {
        $conn->close();
        return false;
    }
    
    // Get student number
    $result = $conn->query("SELECT student_number FROM students WHERE student_id = $student_id");
    if (!$result || $result->num_rows == 0) {
        $conn->close();
        return false;
    }
    $student = $result->fetch_assoc();
    $student_number = $student['student_number'];
    
    if ($created_by === null) {
        $created_by = $_SESSION['user_id'] ?? null;
    }
    
    $stmt = $conn->prepare("INSERT INTO student_notifications (student_id, student_number, from_department, notification_type, title, message, action_url, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssi", $student_id, $student_number, $from_department, $notification_type, $title, $message, $action_url, $created_by);
    
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

/**
 * Get unread notifications for a student
 */
function getStudentNotifications($student_id, $limit = 20) {
    $conn = getDBConnection();
    if (!$conn) return [];
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'student_notifications'");
    if ($table_check->num_rows == 0) {
        $conn->close();
        return [];
    }
    
    $stmt = $conn->prepare("SELECT n.*, u.full_name as created_by_name 
        FROM student_notifications n
        LEFT JOIN users u ON n.created_by = u.user_id
        WHERE n.student_id = ?
        ORDER BY n.created_at DESC
        LIMIT ?");
    
    if (!$stmt) {
        $conn->close();
        return [];
    }
    
    $stmt->bind_param("ii", $student_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    
    return $notifications;
}

/**
 * Get unread notification count for a student
 */
function getStudentNotificationCount($student_id) {
    $conn = getDBConnection();
    if (!$conn) return 0;
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'student_notifications'");
    if ($table_check->num_rows == 0) {
        $conn->close();
        return 0;
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM student_notifications WHERE student_id = $student_id AND status = 'unread'");
    if (!$result) {
        $conn->close();
        return 0;
    }
    $count = $result->fetch_assoc()['count'];
    $conn->close();
    
    return $count;
}

/**
 * Mark student notification as read
 */
function markStudentNotificationRead($notification_id) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'student_notifications'");
    if ($table_check->num_rows == 0) {
        $conn->close();
        return false;
    }
    
    $stmt = $conn->prepare("UPDATE student_notifications SET status = 'read', read_at = CURRENT_TIMESTAMP WHERE notification_id = ?");
    if (!$stmt) {
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("i", $notification_id);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

