<?php
/**
 * Archive Helper Functions
 * PNG Maritime College - Student Management System
 * 
 * Functions for archiving and restoring records
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/security_helper.php';

/**
 * Archive an application
 */
function archiveApplication($application_id, $archived_by, $reason = '', $notes = '') {
    $conn = getDBConnection();
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    // Get application data
    $stmt = $conn->prepare("SELECT * FROM applications WHERE application_id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Application not found'];
    }
    
    $application = $result->fetch_assoc();
    $stmt->close();
    
    // Copy to archive table
    $archive_stmt = $conn->prepare("
        INSERT INTO archived_applications (
            original_application_id, application_number, first_name, last_name, middle_name,
            date_of_birth, gender, email, phone, address, city, province, country,
            grade_12_passed, maths_grade, physics_grade, english_grade, overall_gpa,
            program_interest, expression_date, submitted_at, status,
            assessed_by, assessment_date, assessment_notes,
            hod_decision, hod_decision_by, hod_decision_date, hod_decision_notes,
            correspondence_sent, correspondence_date, invoice_sent, invoice_id,
            enrollment_ready, enrolled, enrollment_date, student_id,
            archived_by, archive_reason, archive_notes,
            original_created_at, original_updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $archive_stmt->bind_param("isssssssssssssssssssssssssssssssssssssssssssssss",
        $application['application_id'],
        $application['application_number'],
        $application['first_name'],
        $application['last_name'],
        $application['middle_name'],
        $application['date_of_birth'],
        $application['gender'],
        $application['email'],
        $application['phone'],
        $application['address'],
        $application['city'],
        $application['province'],
        $application['country'],
        $application['grade_12_passed'],
        $application['maths_grade'],
        $application['physics_grade'],
        $application['english_grade'],
        $application['overall_gpa'],
        $application['program_interest'],
        $application['expression_date'],
        $application['submitted_at'],
        $application['status'],
        $application['assessed_by'],
        $application['assessment_date'],
        $application['assessment_notes'],
        $application['hod_decision'],
        $application['hod_decision_by'],
        $application['hod_decision_date'],
        $application['hod_decision_notes'],
        $application['correspondence_sent'],
        $application['correspondence_date'],
        $application['invoice_sent'],
        $application['invoice_id'],
        $application['enrollment_ready'],
        $application['enrolled'],
        $application['enrollment_date'],
        $application['student_id'],
        $archived_by,
        $reason,
        $notes,
        $application['created_at'],
        $application['updated_at']
    );
    
    if ($archive_stmt->execute()) {
        // Archive related documents
        archiveApplicationDocuments($application_id, $conn);
        
        // Log archive action
        logArchiveAction('application', $application_id, 'archived', $archived_by, $reason, $notes, $conn);
        
        // Delete original (or mark as archived if you want to keep reference)
        // Option 1: Delete original
        $delete_stmt = $conn->prepare("DELETE FROM applications WHERE application_id = ?");
        $delete_stmt->bind_param("i", $application_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        $archive_stmt->close();
        return ['success' => true, 'message' => 'Application archived successfully'];
    } else {
        $archive_stmt->close();
        return ['success' => false, 'message' => 'Failed to archive application: ' . $conn->error];
    }
}

/**
 * Archive application documents
 */
function archiveApplicationDocuments($application_id, $conn) {
    // Get all documents for this application
    $stmt = $conn->prepare("SELECT * FROM application_documents WHERE application_id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($doc = $result->fetch_assoc()) {
        $archive_doc_stmt = $conn->prepare("
            INSERT INTO archived_application_documents (
                original_document_id, original_application_id, document_type, document_name,
                file_path, uploaded_at, verified, verified_by, verified_at, notes, archived_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $archive_doc_stmt->bind_param("iissssissst",
            $doc['document_id'],
            $doc['application_id'],
            $doc['document_type'],
            $doc['document_name'],
            $doc['file_path'],
            $doc['uploaded_at'],
            $doc['verified'],
            $doc['verified_by'],
            $doc['verified_at'],
            $doc['notes']
        );
        
        $archive_doc_stmt->execute();
        $archive_doc_stmt->close();
        
        // Delete original document record (file is kept)
        $delete_doc_stmt = $conn->prepare("DELETE FROM application_documents WHERE document_id = ?");
        $delete_doc_stmt->bind_param("i", $doc['document_id']);
        $delete_doc_stmt->execute();
        $delete_doc_stmt->close();
    }
    
    $stmt->close();
}

/**
 * Archive a student
 */
function archiveStudent($student_id, $archived_by, $reason = '', $notes = '') {
    $conn = getDBConnection();
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    // Get student data
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Student not found'];
    }
    
    $student = $result->fetch_assoc();
    $stmt->close();
    
    // Copy to archive table
    $archive_stmt = $conn->prepare("
        INSERT INTO archived_students (
            original_student_id, student_number, first_name, last_name, middle_name,
            date_of_birth, gender, email, phone, address, city, province, country,
            enrollment_date, graduation_date, program_id, status, account_status, profile_photo_path,
            archived_by, archive_reason, archive_notes,
            original_created_at, original_updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $archive_stmt->bind_param("isssssssssssssssssssssss",
        $student['student_id'],
        $student['student_number'],
        $student['first_name'],
        $student['last_name'],
        $student['middle_name'],
        $student['date_of_birth'],
        $student['gender'],
        $student['email'],
        $student['phone'],
        $student['address'],
        $student['city'],
        $student['province'],
        $student['country'],
        $student['enrollment_date'],
        $student['graduation_date'],
        $student['program_id'],
        $student['status'],
        $student['account_status'] ?? 'inactive',
        $student['profile_photo_path'] ?? null,
        $archived_by,
        $reason,
        $notes,
        $student['created_at'],
        $student['updated_at']
    );
    
    if ($archive_stmt->execute()) {
        // Log archive action
        logArchiveAction('student', $student_id, 'archived', $archived_by, $reason, $notes, $conn);
        
        // Delete original
        $delete_stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
        $delete_stmt->bind_param("i", $student_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        $archive_stmt->close();
        return ['success' => true, 'message' => 'Student archived successfully'];
    } else {
        $archive_stmt->close();
        return ['success' => false, 'message' => 'Failed to archive student: ' . $conn->error];
    }
}

/**
 * Archive an invoice
 */
function archiveInvoice($invoice_id, $archived_by, $reason = '', $notes = '') {
    $conn = getDBConnection();
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    // Get invoice data
    $stmt = $conn->prepare("SELECT * FROM invoices WHERE invoice_id = ?");
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Invoice not found'];
    }
    
    $invoice = $result->fetch_assoc();
    $stmt->close();
    
    // Copy to archive table
    $archive_stmt = $conn->prepare("
        INSERT INTO archived_invoices (
            original_invoice_id, invoice_number, student_id, application_id,
            invoice_type, amount, due_date, status,
            payment_method, payment_date, payment_reference, notes,
            archived_by, archive_reason, archive_notes,
            original_created_at, original_updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $archive_stmt->bind_param("isissdssssssssssss",
        $invoice['invoice_id'],
        $invoice['invoice_number'],
        $invoice['student_id'],
        $invoice['application_id'] ?? null,
        $invoice['invoice_type'] ?? 'tuition',
        $invoice['amount'],
        $invoice['due_date'],
        $invoice['status'],
        $invoice['payment_method'] ?? null,
        $invoice['payment_date'] ?? null,
        $invoice['payment_reference'] ?? null,
        $invoice['notes'] ?? null,
        $archived_by,
        $reason,
        $notes,
        $invoice['created_at'],
        $invoice['updated_at']
    );
    
    if ($archive_stmt->execute()) {
        // Log archive action
        logArchiveAction('invoice', $invoice_id, 'archived', $archived_by, $reason, $notes, $conn);
        
        // Delete original
        $delete_stmt = $conn->prepare("DELETE FROM invoices WHERE invoice_id = ?");
        $delete_stmt->bind_param("i", $invoice_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        $archive_stmt->close();
        return ['success' => true, 'message' => 'Invoice archived successfully'];
    } else {
        $archive_stmt->close();
        return ['success' => false, 'message' => 'Failed to archive invoice: ' . $conn->error];
    }
}

/**
 * Log archive action
 */
function logArchiveAction($archive_type, $original_id, $action, $performed_by, $reason = '', $notes = '', $conn = null) {
    if (!$conn) {
        $conn = getDBConnection();
    }
    if (!$conn) {
        return false;
    }
    
    $stmt = $conn->prepare("
        INSERT INTO archive_log (archive_type, original_id, action, performed_by, reason, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("sisiss", $archive_type, $original_id, $action, $performed_by, $reason, $notes);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get archive statistics
 */
function getArchiveStatistics() {
    $conn = getDBConnection();
    if (!$conn) {
        return null;
    }
    
    $stats = [];
    
    // Count archived applications
    $result = $conn->query("SELECT COUNT(*) as count FROM archived_applications");
    $stats['applications'] = $result->fetch_assoc()['count'];
    
    // Count archived students
    $result = $conn->query("SELECT COUNT(*) as count FROM archived_students");
    $stats['students'] = $result->fetch_assoc()['count'];
    
    // Count archived invoices
    $result = $conn->query("SELECT COUNT(*) as count FROM archived_invoices");
    $stats['invoices'] = $result->fetch_assoc()['count'];
    
    // Count archived documents
    $result = $conn->query("SELECT COUNT(*) as count FROM archived_application_documents");
    $stats['documents'] = $result->fetch_assoc()['count'];
    
    return $stats;
}

/**
 * Get archive settings
 */
function getArchiveSetting($key, $default = '') {
    $conn = getDBConnection();
    if (!$conn) {
        return $default;
    }
    
    $stmt = $conn->prepare("SELECT setting_value FROM archive_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['setting_value'];
    }
    
    $stmt->close();
    return $default;
}

/**
 * Update archive setting
 */
function updateArchiveSetting($key, $value) {
    $conn = getDBConnection();
    if (!$conn) {
        return false;
    }
    
    $stmt = $conn->prepare("
        INSERT INTO archive_settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    
    $stmt->bind_param("sss", $key, $value, $value);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

?>

