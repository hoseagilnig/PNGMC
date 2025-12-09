-- Archive Tables for PNG Maritime College SMS
-- Creates archive tables for applications, students, invoices, and documents
-- Run this after the main database schema

USE sms2_db;

-- ============================================
-- ARCHIVED APPLICATIONS
-- ============================================
CREATE TABLE IF NOT EXISTS archived_applications (
    archive_id INT AUTO_INCREMENT PRIMARY KEY,
    original_application_id INT NOT NULL,
    application_number VARCHAR(50) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    country VARCHAR(100),
    
    -- Academic Information
    grade_12_passed BOOLEAN DEFAULT FALSE,
    maths_grade VARCHAR(10),
    physics_grade VARCHAR(10),
    english_grade VARCHAR(10),
    overall_gpa DECIMAL(4,2),
    
    -- Application Details
    program_interest VARCHAR(200),
    expression_date DATE,
    submitted_at TIMESTAMP,
    
    -- Workflow Status (at time of archiving)
    status ENUM('submitted', 'under_review', 'hod_review', 'accepted', 'rejected', 'correspondence_sent', 'checks_pending', 'checks_completed', 'enrolled', 'ineligible') DEFAULT 'submitted',
    
    -- Assessment
    assessed_by INT,
    assessment_date DATE,
    assessment_notes TEXT,
    
    -- HOD Decision
    hod_decision ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    hod_decision_by INT,
    hod_decision_date DATE,
    hod_decision_notes TEXT,
    
    -- Correspondence
    correspondence_sent BOOLEAN DEFAULT FALSE,
    correspondence_date DATE,
    invoice_sent BOOLEAN DEFAULT FALSE,
    invoice_id INT,
    
    -- Enrollment
    enrollment_ready BOOLEAN DEFAULT FALSE,
    enrolled BOOLEAN DEFAULT FALSE,
    enrollment_date DATE,
    student_id INT NULL,
    
    -- Archive Information
    archived_by INT,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archive_reason VARCHAR(255),
    archive_notes TEXT,
    
    -- Original timestamps
    original_created_at TIMESTAMP,
    original_updated_at TIMESTAMP,
    
    INDEX idx_original_application_id (original_application_id),
    INDEX idx_application_number (application_number),
    INDEX idx_status (status),
    INDEX idx_archived_at (archived_at),
    INDEX idx_archived_by (archived_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ARCHIVED APPLICATION DOCUMENTS
-- ============================================
CREATE TABLE IF NOT EXISTS archived_application_documents (
    archive_document_id INT AUTO_INCREMENT PRIMARY KEY,
    original_document_id INT NOT NULL,
    original_application_id INT NOT NULL,
    document_type ENUM('grade_12_certificate', 'transcript', 'birth_certificate', 'medical_certificate', 'police_clearance', 'passport_photo', 'other') NOT NULL,
    document_name VARCHAR(200) NOT NULL,
    file_path VARCHAR(500),
    uploaded_at TIMESTAMP,
    verified BOOLEAN DEFAULT FALSE,
    verified_by INT,
    verified_at TIMESTAMP NULL,
    notes TEXT,
    
    -- Archive Information
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_original_document_id (original_document_id),
    INDEX idx_original_application_id (original_application_id),
    INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ARCHIVED STUDENTS
-- ============================================
CREATE TABLE IF NOT EXISTS archived_students (
    archive_id INT AUTO_INCREMENT PRIMARY KEY,
    original_student_id INT NOT NULL,
    student_number VARCHAR(50) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    country VARCHAR(100) DEFAULT 'Papua New Guinea',
    enrollment_date DATE,
    graduation_date DATE,
    program_id INT,
    status ENUM('active', 'inactive', 'graduated', 'withdrawn', 'suspended') DEFAULT 'active',
    account_status ENUM('active', 'on_hold', 'suspended', 'inactive') DEFAULT 'active',
    profile_photo_path VARCHAR(500),
    
    -- Archive Information
    archived_by INT,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archive_reason VARCHAR(255),
    archive_notes TEXT,
    
    -- Original timestamps
    original_created_at TIMESTAMP,
    original_updated_at TIMESTAMP,
    
    INDEX idx_original_student_id (original_student_id),
    INDEX idx_student_number (student_number),
    INDEX idx_status (status),
    INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ARCHIVED INVOICES
-- ============================================
CREATE TABLE IF NOT EXISTS archived_invoices (
    archive_id INT AUTO_INCREMENT PRIMARY KEY,
    original_invoice_id INT NOT NULL,
    invoice_number VARCHAR(50) NOT NULL,
    student_id INT,
    application_id INT,
    invoice_type ENUM('tuition', 'fees', 'other') DEFAULT 'tuition',
    amount DECIMAL(10, 2) NOT NULL,
    due_date DATE,
    status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_date DATE,
    payment_reference VARCHAR(100),
    notes TEXT,
    
    -- Archive Information
    archived_by INT,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archive_reason VARCHAR(255),
    archive_notes TEXT,
    
    -- Original timestamps
    original_created_at TIMESTAMP,
    original_updated_at TIMESTAMP,
    
    INDEX idx_original_invoice_id (original_invoice_id),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status),
    INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ARCHIVE LOG
-- ============================================
CREATE TABLE IF NOT EXISTS archive_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    archive_type ENUM('application', 'student', 'invoice', 'document') NOT NULL,
    original_id INT NOT NULL,
    action ENUM('archived', 'restored', 'deleted') NOT NULL,
    performed_by INT,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason VARCHAR(255),
    notes TEXT,
    
    INDEX idx_archive_type (archive_type),
    INDEX idx_original_id (original_id),
    INDEX idx_action (action),
    INDEX idx_performed_at (performed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ARCHIVE SETTINGS
-- ============================================
CREATE TABLE IF NOT EXISTS archive_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value VARCHAR(255),
    setting_description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default archive settings
INSERT INTO archive_settings (setting_key, setting_value, setting_description) VALUES
('auto_archive_applications', 'false', 'Automatically archive applications older than specified days'),
('archive_applications_after_days', '365', 'Archive applications completed/rejected more than this many days ago'),
('auto_archive_students', 'false', 'Automatically archive inactive/graduated students'),
('archive_students_after_days', '730', 'Archive students inactive/graduated more than this many days ago'),
('auto_archive_invoices', 'false', 'Automatically archive paid invoices'),
('archive_invoices_after_days', '180', 'Archive paid invoices older than this many days'),
('archive_keep_documents', 'true', 'Keep document files when archiving (do not delete)')
ON DUPLICATE KEY UPDATE setting_description = VALUES(setting_description);

