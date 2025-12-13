<?php
/**
 * Workflow Helper Functions
 * Functions for managing cross-department workflow, notifications, and audit trail
 */

require_once __DIR__ . '/db_config.php';

/**
 * Create a workflow notification
 */
function createWorkflowNotification($application_id, $from_department, $to_department, $title, $message, $notification_type = 'action_required', $action_url = null, $created_by = null) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'workflow_notifications'");
    if ($table_check->num_rows == 0) {
        $conn->close();
        return false;
    }
    
    if ($created_by === null) {
        $created_by = $_SESSION['user_id'] ?? null;
    }
    
    if (!$created_by) return false;
    
    // Handle NULL application_id (for non-application related notifications)
    if ($application_id == 0) {
        $stmt = $conn->prepare("INSERT INTO workflow_notifications (application_id, from_department, to_department, notification_type, title, message, action_url, created_by) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            $conn->close();
            return false;
        }
        $stmt->bind_param("ssssssi", $from_department, $to_department, $notification_type, $title, $message, $action_url, $created_by);
    } else {
        $stmt = $conn->prepare("INSERT INTO workflow_notifications (application_id, from_department, to_department, notification_type, title, message, action_url, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            $conn->close();
            return false;
        }
        $stmt->bind_param("issssssi", $application_id, $from_department, $to_department, $notification_type, $title, $message, $action_url, $created_by);
    }
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

/**
 * Log a workflow action (audit trail)
 */
function logWorkflowAction($application_id, $action_type, $action_description, $from_status = null, $to_status = null, $notes = null, $performed_by = null) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'workflow_actions'");
    if ($table_check->num_rows == 0) {
        $conn->close();
        return false;
    }
    
    if ($performed_by === null) {
        $performed_by = $_SESSION['user_id'] ?? null;
    }
    
    $performed_by_department = $_SESSION['role'] ?? 'admin';
    
    if (!$performed_by) return false;
    
    $stmt = $conn->prepare("INSERT INTO workflow_actions (application_id, action_type, from_status, to_status, performed_by, performed_by_department, action_description, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("isssisss", $application_id, $action_type, $from_status, $to_status, $performed_by, $performed_by_department, $action_description, $notes);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

/**
 * Update application workflow status
 */
function updateApplicationWorkflow($application_id, $new_status, $new_department = null, $workflow_stage = null, $notes = null) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Get current status
    $stmt = $conn->prepare("SELECT status, current_department, workflow_stage FROM applications WHERE application_id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current = $result->fetch_assoc();
    $stmt->close();
    $old_status = $current['status'] ?? null;
    $old_department = $current['current_department'] ?? null;
    
    // Update application
    $user_id = $_SESSION['user_id'] ?? null;
    $updates = ["status = '$new_status'", "last_action_at = CURRENT_TIMESTAMP"];
    
    if ($new_department) {
        $updates[] = "current_department = '$new_department'";
    }
    
    if ($workflow_stage) {
        $updates[] = "workflow_stage = '$workflow_stage'";
    }
    
    if ($user_id) {
        $updates[] = "last_action_by = $user_id";
    }
    
    $sql = "UPDATE applications SET " . implode(', ', $updates) . " WHERE application_id = $application_id";
    $conn->query($sql);
    
    // Log the action
    $action_desc = "Status changed from '$old_status' to '$new_status'";
    if ($old_department && $new_department && $old_department != $new_department) {
        $action_desc .= " (Moved from $old_department to $new_department)";
    }
    
    logWorkflowAction($application_id, 'status_change', $action_desc, $old_status, $new_status, $notes, $user_id);
    
    $conn->close();
    return true;
}

/**
 * Get unread notifications for a department
 */
function getUnreadNotifications($department, $limit = 10) {
    $conn = getDBConnection();
    if (!$conn) return [];
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'workflow_notifications'");
    if ($table_check->num_rows == 0) {
        $conn->close();
        return [];
    }
    
    // Use LEFT JOIN to handle NULL application_id (for non-application notifications)
    $stmt = $conn->prepare("SELECT n.*, a.application_number, a.first_name, a.last_name, u.full_name as created_by_name 
                           FROM workflow_notifications n
                           LEFT JOIN applications a ON n.application_id = a.application_id
                           LEFT JOIN users u ON n.created_by = u.user_id
                           WHERE n.to_department = ? AND n.status = 'unread'
                           ORDER BY n.created_at DESC
                           LIMIT ?");
    if (!$stmt) {
        $conn->close();
        return [];
    }
    
    $stmt->bind_param("si", $department, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    
    return $notifications;
}

/**
 * Get notification count for a department
 */
function getNotificationCount($department) {
    $conn = getDBConnection();
    if (!$conn) return 0;
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'workflow_notifications'");
    if ($table_check->num_rows == 0) {
        $conn->close();
        return 0;
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM workflow_notifications WHERE to_department = '$department' AND status = 'unread'");
    if (!$result) {
        $conn->close();
        return 0;
    }
    $count = $result->fetch_assoc()['count'];
    $conn->close();
    
    return $count;
}

/**
 * Mark notification as read
 */
function markNotificationRead($notification_id) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'workflow_notifications'");
    if ($table_check->num_rows == 0) {
        $conn->close();
        return false;
    }
    
    $stmt = $conn->prepare("UPDATE workflow_notifications SET status = 'read', read_at = CURRENT_TIMESTAMP WHERE notification_id = ?");
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

/**
 * Get workflow history for an application
 */
function getWorkflowHistory($application_id) {
    $conn = getDBConnection();
    if (!$conn) return [];
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'workflow_actions'");
    if ($table_check->num_rows == 0) {
        $conn->close();
        return [];
    }
    
    $result = $conn->query("SELECT wa.*, u.full_name as performed_by_name 
                           FROM workflow_actions wa
                           LEFT JOIN users u ON wa.performed_by = u.user_id
                           WHERE wa.application_id = $application_id
                           ORDER BY wa.created_at DESC");
    if (!$result) {
        $conn->close();
        return [];
    }
    $history = $result->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    
    return $history;
}

/**
 * Get applications pending action for a department
 */
function getPendingApplicationsForDepartment($department, $status_filter = null) {
    $conn = getDBConnection();
    if (!$conn) return [];
    
    // Check if current_department column exists
    $col_check = $conn->query("SHOW COLUMNS FROM applications LIKE 'current_department'");
    $has_current_dept = $col_check->num_rows > 0;
    
    // Check if workflow_notifications table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'workflow_notifications'");
    $has_notifications = $table_check->num_rows > 0;
    
    if (!$has_current_dept) {
        // If column doesn't exist, return empty array or filter by status only
        $sql = "SELECT a.*";
        if ($has_notifications) {
            $sql .= ", (SELECT COUNT(*) FROM workflow_notifications WHERE application_id = a.application_id AND to_department = '$department' AND status = 'unread') as unread_notifications";
        } else {
            $sql .= ", 0 as unread_notifications";
        }
        $sql .= " FROM applications a WHERE 1=0"; // Return nothing if column doesn't exist
    } else {
        $sql = "SELECT a.*";
        if ($has_notifications) {
            $sql .= ", (SELECT COUNT(*) FROM workflow_notifications WHERE application_id = a.application_id AND to_department = '$department' AND status = 'unread') as unread_notifications";
        } else {
            $sql .= ", 0 as unread_notifications";
        }
        $sql .= " FROM applications a WHERE a.current_department = '$department'";
        
        if ($status_filter) {
            $sql .= " AND a.status = '$status_filter'";
        }
    }
    
    $sql .= " ORDER BY a.last_action_at DESC, a.submitted_at DESC";
    
    $result = $conn->query($sql);
    if (!$result) {
        $conn->close();
        return [];
    }
    $applications = $result->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    
    return $applications;
}

/**
 * Forward application to another department
 */
function forwardToDepartment($application_id, $to_department, $title, $message, $new_status = null, $workflow_stage = null) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Get current department
    $stmt = $conn->prepare("SELECT current_department, status FROM applications WHERE application_id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $app = $result->fetch_assoc();
    $stmt->close();
    $from_department = $app['current_department'] ?? $_SESSION['role'] ?? 'admin';
    
    // Update application
    $updates = ["current_department = '$to_department'", "last_action_at = CURRENT_TIMESTAMP"];
    
    if ($new_status) {
        $updates[] = "status = '$new_status'";
    }
    
    if ($workflow_stage) {
        $updates[] = "workflow_stage = '$workflow_stage'";
    }
    
    if (isset($_SESSION['user_id'])) {
        $updates[] = "last_action_by = " . $_SESSION['user_id'];
    }
    
    $conn->query("UPDATE applications SET " . implode(', ', $updates) . " WHERE application_id = $application_id");
    
    // Create notification
    $action_url = "application_details.php?id=$application_id";
    createWorkflowNotification($application_id, $from_department, $to_department, $title, $message, 'action_required', $action_url);
    
    // Log action
    logWorkflowAction($application_id, 'status_change', "Forwarded from $from_department to $to_department: $message", $app['status'], $new_status, null);
    
    $conn->close();
    return true;
}

/**
 * Get HOD users for a program (program-to-HOD mapping)
 * Returns array of HOD user IDs based on program name
 */
function getHODsForProgram($program_name) {
    $conn = getDBConnection();
    if (!$conn) return [];
    
    // Simple mapping: For now, return all HOD users
    // In the future, this could be enhanced with a program_hod_mapping table
    $result = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'hod' AND status = 'active'");
    $hods = [];
    while ($row = $result->fetch_assoc()) {
        $hods[] = $row;
    }
    $conn->close();
    
    return $hods;
}

/**
 * Notify applicant about application status
 * Creates a correspondence record and optionally sends email
 * This is a wrapper function that calls sendApplicantNotification for backward compatibility
 */
function notifyApplicant($application_id, $subject, $message, $correspondence_type = 'email') {
    // Map correspondence_type to notification_method
    $notification_method = 'email';
    if ($correspondence_type === 'phone' || $correspondence_type === 'sms') {
        $notification_method = 'phone';
    }
    
    return sendApplicantNotification($application_id, $subject, $message, $notification_method, $correspondence_type);
}

/**
 * Initialize application workflow when application is submitted
 * Sets current_department to studentservices and creates notification
 */
function initializeApplicationWorkflow($application_id) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Get application details
    $stmt = $conn->prepare("SELECT application_number, first_name, last_name, program_interest FROM applications WHERE application_id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $app = $result->fetch_assoc();
    $stmt->close();
    
    if (!$app) {
        $conn->close();
        return false;
    }
    
    // Set current_department to studentservices if not already set
    $conn->query("UPDATE applications SET current_department = 'studentservices', workflow_stage = 'submitted', last_action_at = CURRENT_TIMESTAMP WHERE application_id = $application_id");
    
    // Create notification to Student Admin Service
    $title = "New Application Received";
    $message = "New application #{$app['application_number']} from {$app['first_name']} {$app['last_name']} for {$app['program_interest']} has been submitted and requires review.";
    $action_url = "application_details.php?id=$application_id";
    
    // Use system user (admin) or first admin user as creator (for public submissions)
    $admin_result = $conn->query("SELECT user_id FROM users WHERE role = 'admin' LIMIT 1");
    $admin_user = $admin_result->fetch_assoc();
    $created_by = $admin_user['user_id'] ?? 1;
    
    // Check if workflow_notifications table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'workflow_notifications'");
    if ($table_check->num_rows > 0) {
        createWorkflowNotification($application_id, 'admin', 'studentservices', $title, $message, 'action_required', $action_url, $created_by);
    }
    
    // Check if workflow_actions table exists
    $actions_table_check = $conn->query("SHOW TABLES LIKE 'workflow_actions'");
    if ($actions_table_check->num_rows > 0) {
        logWorkflowAction($application_id, 'status_change', "Application submitted and assigned to Student Admin Service", null, 'submitted', null, $created_by);
    }
    
    $conn->close();
    return true;
}

/**
 * Automatically generate mandatory checks for accepted applications
 */
function generateMandatoryChecks($application_id) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Check if mandatory_checks table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'mandatory_checks'");
    if ($table_check->num_rows == 0) {
        $conn->close();
        return false;
    }
    
    // Check if checks already exist
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM mandatory_checks WHERE application_id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_count = $result->fetch_assoc()['count'];
    $stmt->close();
    
    if ($existing_count > 0) {
        $conn->close();
        return true; // Already generated
    }
    
    // Standard mandatory checks for all accepted applications
    $mandatory_checks = [
        ['medical', 'Medical Clearance Certificate'],
        ['police_clearance', 'Police Clearance Certificate'],
        ['academic_verification', 'Academic Records Verification'],
        ['identity_verification', 'Identity Verification (Birth Certificate/Passport)'],
        ['financial_clearance', 'Financial Clearance']
    ];
    
    $stmt = $conn->prepare("INSERT INTO mandatory_checks (application_id, check_type, check_name, status) VALUES (?, ?, ?, 'pending')");
    
    foreach ($mandatory_checks as $check) {
        $check_type = $check[0];
        $check_name = $check[1];
        $stmt->bind_param("iss", $application_id, $check_type, $check_name);
        $stmt->execute();
    }
    
    $stmt->close();
    $conn->close();
    return true;
}

/**
 * Automatically create requirements for an application if they don't exist
 */
function createApplicationRequirements($application_id, $application_type = null) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'continuing_student_requirements'");
    if ($table_check->num_rows === 0) {
        $conn->close();
        return false;
    }
    
    // Check if requirements already exist
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM continuing_student_requirements WHERE application_id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    if ($existing['count'] > 0) {
        $conn->close();
        return true; // Requirements already exist
    }
    
    // Get application type if not provided
    if (!$application_type) {
        $stmt = $conn->prepare("SELECT application_type FROM applications WHERE application_id = ?");
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $app = $result->fetch_assoc();
            $application_type = $app['application_type'] ?? 'new_student';
        } else {
            $application_type = 'new_student';
        }
        $stmt->close();
    }
    
    // Determine requirements based on application type
    $requirements = [];
    
    if ($application_type === 'continuing_student_solas' || $application_type === 'continuing_student_next_level') {
        // Continuing student requirements
        $requirements = [
            ['nmsa_approval', 'NMSA Approval Letter'],
            ['sea_service_record', 'Record of Sea Service'],
            ['expression_of_interest', 'Expression of Interest Application']
        ];
        
        if ($application_type === 'continuing_student_next_level') {
            $requirements[] = ['coc_validity', 'Certificate of Competency (COC) Validity'];
        }
    } else {
        // School leaver (new student) requirements
        $requirements = [
            ['academic_prerequisites', 'Academic Prerequisites (Grade 12 Certificate)'],
            ['academic_prerequisites', 'Academic Transcript Verification'],
            ['financial_clearance', 'Financial Clearance'],
            ['other', 'Medical Certificate'],
            ['other', 'Police Clearance'],
            ['other', 'Identity Verification (Birth Certificate)']
        ];
    }
    
    // Create requirements
    $req_sql = "INSERT INTO continuing_student_requirements (application_id, requirement_type, requirement_name, status) VALUES (?, ?, ?, 'pending')";
    $req_stmt = $conn->prepare($req_sql);
    
    if (!$req_stmt) {
        $conn->close();
        return false;
    }
    
    foreach ($requirements as $req) {
        $req_stmt->bind_param('iss', $application_id, $req[0], $req[1]);
        $req_stmt->execute();
    }
    $req_stmt->close();
    $conn->close();
    
    return true;
}

/**
 * Automatically generate requirements and notify Finance and SAS when application is accepted
 */
function processAcceptedApplication($application_id, $hod_notes = '') {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Get application details
    $stmt = $conn->prepare("SELECT application_number, first_name, last_name, program_interest, email, phone, application_type FROM applications WHERE application_id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $app = $result->fetch_assoc();
    $stmt->close();
    
    if (!$app) {
        $conn->close();
        return false;
    }
    
    // Automatically create requirements if they don't exist
    createApplicationRequirements($application_id, $app['application_type'] ?? null);
    
    // DO NOT generate mandatory checks automatically - these will be requested AFTER applicant receives acceptance letter
    
    // Get admin user for notifications
    $admin_result = $conn->query("SELECT user_id FROM users WHERE role = 'admin' LIMIT 1");
    $admin_user = $admin_result->fetch_assoc();
    $created_by = $admin_user['user_id'] ?? 1;
    
    // Notify Finance department with action to generate invoice
    $finance_title = "Application Accepted - Generate School Fee Invoice Required";
    $finance_message = "Application #{$app['application_number']} from {$app['first_name']} {$app['last_name']} for {$app['program_interest']} has been accepted by HOD.\n\nAction Required:\n- Generate School Fee Invoice (Proforma Invoice) for the applicant\n- This invoice will be included with the acceptance letter\n- Process payment requirements\n- Update AR records in MYOB";
    if ($hod_notes) {
        $finance_message .= "\n\nHOD Notes: " . $hod_notes;
    }
    $finance_action_url = "application_details.php?id=$application_id";
    
    createWorkflowNotification($application_id, 'hod', 'finance', $finance_title, $finance_message, 'action_required', $finance_action_url, $created_by);
    
    // Notify Student Admin Service with action to generate acceptance letter with invoice and requirements
    $sas_title = "Application Accepted - Generate Acceptance Letter with Invoice & Requirements";
    $sas_message = "Application #{$app['application_number']} from {$app['first_name']} {$app['last_name']} for {$app['program_interest']} has been accepted by HOD.\n\nAction Required:\n- Generate Acceptance Letter for the applicant\n- Include School Fee Invoice (from Finance) with the acceptance letter\n- Include Requirements List in the acceptance letter (Medical Clearance, Police Clearance, Academic Verification, Identity Verification, Financial Clearance)\n- Notify applicant via email or phone with complete acceptance letter (including invoice and requirements)";
    if ($hod_notes) {
        $sas_message .= "\n\nHOD Notes: " . $hod_notes;
    }
    $sas_action_url = "application_details.php?id=$application_id";
    
    createWorkflowNotification($application_id, 'hod', 'studentservices', $sas_title, $sas_message, 'action_required', $sas_action_url, $created_by);
    
    // Automatically create proforma invoice if student is enrolled
    $student_stmt = $conn->prepare("SELECT student_id FROM applications WHERE application_id = ?");
    $student_stmt->bind_param("i", $application_id);
    $student_stmt->execute();
    $student_check = $student_stmt->get_result();
    if ($student_check && $student_check->num_rows > 0) {
        $app_data = $student_check->fetch_assoc();
        $student_stmt->close();
        
        if ($app_data['student_id']) {
            // Check if invoice already exists
            $invoice_check = $conn->query("SHOW TABLES LIKE 'proforma_invoices'");
            if ($invoice_check->num_rows > 0) {
                $invoice_stmt = $conn->prepare("SELECT pi_id FROM proforma_invoices WHERE student_id = ? AND remarks LIKE ? LIMIT 1");
                $remarks_pattern = '%application #' . $app['application_number'] . '%';
                $invoice_stmt->bind_param("is", $app_data['student_id'], $remarks_pattern);
                $invoice_stmt->execute();
                $existing_invoice = $invoice_stmt->get_result();
                $invoice_stmt->close();
                
                if (!$existing_invoice || $existing_invoice->num_rows === 0) {
                    // Auto-create invoice
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
                    $course_fee = 5000.00; // Default - should be configurable
                    $date = date('Y-m-d');
                    $student_name = $app['first_name'] . ' ' . $app['last_name'];
                    $course_name = $app['program_interest'] ?? 'Course Fee';
                    $remarks = "Auto-generated from application #" . $app['application_number'];
                    
                    $pi_sql = "INSERT INTO proforma_invoices (
                        pi_number, date, student_id, student_name, 
                        forwarding_address, telephone, mobile_number, course_name, 
                        course_fee, balance, status, pi_issuing_officer, remarks, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'outstanding', ?, ?, ?)";
                    
                    $pi_stmt = $conn->prepare($pi_sql);
                    if ($pi_stmt) {
                        $info_stmt = $conn->prepare("SELECT address, phone FROM applications WHERE application_id = ?");
                        $info_stmt->bind_param("i", $application_id);
                        $info_stmt->execute();
                        $info_result = $info_stmt->get_result();
                        $student_info = $info_result->fetch_assoc();
                        $info_stmt->close();
                        
                        $forwarding_address = $student_info['address'] ?? '';
                        $telephone = $student_info['phone'] ?? '';
                        $mobile_number = $student_info['phone'] ?? '';
                        $pi_issuing_officer = $created_by;
                        
                        $pi_stmt->bind_param("ssisssssddisi", 
                            $pi_number, $date, $app_data['student_id'], $student_name,
                            $forwarding_address, $telephone, $mobile_number, $course_name,
                            $course_fee, $course_fee, $pi_issuing_officer, $remarks, $created_by
                        );
                        
                        if ($pi_stmt->execute()) {
                            $pi_id = $conn->insert_id;
                            // Update application with invoice reference
                            $invoice_id_check = $conn->query("SHOW COLUMNS FROM applications LIKE 'invoice_id'");
                            if ($invoice_id_check->num_rows > 0) {
                                $update_stmt = $conn->prepare("UPDATE applications SET invoice_sent = TRUE, invoice_id = ? WHERE application_id = ?");
                                $update_stmt->bind_param("ii", $pi_id, $application_id);
                                $update_stmt->execute();
                                $update_stmt->close();
                            } else {
                                $update_stmt = $conn->prepare("UPDATE applications SET invoice_sent = TRUE WHERE application_id = ?");
                                $update_stmt->bind_param("i", $application_id);
                                $update_stmt->execute();
                                $update_stmt->close();
                            }
                        }
                        $pi_stmt->close();
                    }
                }
            }
        }
    }
    
    // Update application status to accepted and set workflow stage
    $update_stmt = $conn->prepare("UPDATE applications SET status = 'accepted', workflow_stage = 'acceptance_letter_generation', correspondence_sent = FALSE WHERE application_id = ?");
    $update_stmt->bind_param("i", $application_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    $conn->close();
    return true;
}

/**
 * Send notification to applicant via email or phone
 * Enhanced version with email and phone support
 */
function sendApplicantNotification($application_id, $subject, $message, $notification_method = 'email', $notification_type = 'acceptance_letter') {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Get application details
    $stmt = $conn->prepare("SELECT email, phone, first_name, last_name, application_number FROM applications WHERE application_id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $app = $result->fetch_assoc();
    $stmt->close();
    
    if (!$app) {
        $conn->close();
        return false;
    }
    
    $user_id = $_SESSION['user_id'] ?? null;
    
    // Determine correspondence type based on notification method
    $correspondence_type = $notification_type;
    if ($notification_method === 'phone' || $notification_method === 'sms') {
        $correspondence_type = 'phone';
    } elseif ($notification_method === 'email') {
        $correspondence_type = 'email';
    }
    
    // Create correspondence record
    $stmt = $conn->prepare("INSERT INTO correspondence (application_id, correspondence_type, subject, message, sent_by, sent_date, status) VALUES (?, ?, ?, ?, ?, CURDATE(), 'sent')");
    $stmt->bind_param("isssi", $application_id, $correspondence_type, $subject, $message, $user_id);
    $result = $stmt->execute();
    $stmt->close();
    
    // Send actual email if email address is provided and method is email
    if ($notification_method === 'email' && $app['email']) {
        $to = $app['email'];
        $headers = "From: PNG Maritime College <noreply@pngmc.edu.pg>\r\n";
        $headers .= "Reply-To: PNG Maritime College <info@pngmc.edu.pg>\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $html_message = "<html><body>";
        $html_message .= "<h2>" . htmlspecialchars($subject) . "</h2>";
        $html_message .= "<p>Dear " . htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) . ",</p>";
        $html_message .= "<p>" . nl2br(htmlspecialchars($message)) . "</p>";
        $html_message .= "<p>Application Number: " . htmlspecialchars($app['application_number']) . "</p>";
        $html_message .= "<p>Best regards,<br>PNG Maritime College</p>";
        $html_message .= "</body></html>";
        
        @mail($to, $subject, $html_message, $headers);
    }
    
    // TODO: Send SMS if phone number is provided and method is phone/sms
    // This would require an SMS gateway integration
    // if ($notification_method === 'phone' || $notification_method === 'sms') {
    //     // Send SMS via gateway
    // }
    
    // Update application to mark correspondence as sent
    $conn->query("UPDATE applications SET correspondence_sent = TRUE, correspondence_date = CURDATE() WHERE application_id = $application_id");
    
    $conn->close();
    return $result;
}

?>

