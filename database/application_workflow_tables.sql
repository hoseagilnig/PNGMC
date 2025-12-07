-- Additional tables for Application Workflow
-- Based on the PNG Maritime College application process flowchart
-- Run this after the main database schema

USE sms2_db;

-- ============================================
-- APPLICATIONS (Expressions of Interest)
-- ============================================
CREATE TABLE IF NOT EXISTS applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    application_number VARCHAR(50) UNIQUE NOT NULL,
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
    
    -- Academic Information
    grade_12_passed BOOLEAN DEFAULT FALSE,
    maths_grade VARCHAR(10),
    physics_grade VARCHAR(10),
    english_grade VARCHAR(10),
    overall_gpa DECIMAL(4,2),
    
    -- Application Details
    program_interest VARCHAR(200) DEFAULT 'Cadet Officers Program',
    expression_date DATE NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Workflow Status
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
    student_id INT NULL, -- Links to students table when enrolled
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (assessed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (hod_decision_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE SET NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE SET NULL,
    
    INDEX idx_application_number (application_number),
    INDEX idx_status (status),
    INDEX idx_hod_decision (hod_decision),
    INDEX idx_submitted_at (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- APPLICATION DOCUMENTS
-- ============================================
CREATE TABLE IF NOT EXISTS application_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    document_type ENUM('grade_12_certificate', 'transcript', 'birth_certificate', 'medical_certificate', 'police_clearance', 'passport_photo', 'other') NOT NULL,
    document_name VARCHAR(200) NOT NULL,
    file_path VARCHAR(500),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified BOOLEAN DEFAULT FALSE,
    verified_by INT,
    verified_at TIMESTAMP NULL,
    notes TEXT,
    
    FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_application_id (application_id),
    INDEX idx_document_type (document_type),
    INDEX idx_verified (verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MANDATORY CHECKS
-- ============================================
CREATE TABLE IF NOT EXISTS mandatory_checks (
    check_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    check_type ENUM('medical', 'police_clearance', 'academic_verification', 'identity_verification', 'financial_clearance', 'other') NOT NULL,
    check_name VARCHAR(200) NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'failed') DEFAULT 'pending',
    completed_date DATE,
    verified_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_application_id (application_id),
    INDEX idx_status (status),
    INDEX idx_check_type (check_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CORRESPONDENCE
-- ============================================
CREATE TABLE IF NOT EXISTS correspondence (
    correspondence_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    correspondence_type ENUM('email', 'letter', 'phone', 'invoice', 'rejection_letter', 'acceptance_letter', 'requirements_letter') NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    sent_by INT,
    sent_date DATE NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attachment_path VARCHAR(500),
    status ENUM('draft', 'sent', 'delivered', 'failed') DEFAULT 'draft',
    
    FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE,
    FOREIGN KEY (sent_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_application_id (application_id),
    INDEX idx_sent_date (sent_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- APPLICATION NOTES/COMMENTS
-- ============================================
CREATE TABLE IF NOT EXISTS application_notes (
    note_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    user_id INT NOT NULL,
    note_text TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_application_id (application_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

