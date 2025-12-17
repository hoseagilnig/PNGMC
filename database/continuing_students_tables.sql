-- Additional fields and tables for Continuing Students (Returning after Sea Service)
-- Based on the PNG Maritime College continuing students flowchart
-- Run this after the main application_workflow_tables.sql

USE sms2_db;

-- ============================================
-- ALTER APPLICATIONS TABLE FOR CONTINUING STUDENTS
-- ============================================
ALTER TABLE applications 
ADD COLUMN IF NOT EXISTS application_type ENUM('new_student', 'continuing_student_solas', 'continuing_student_next_level') DEFAULT 'new_student' AFTER program_interest,
ADD COLUMN IF NOT EXISTS course_type ENUM('Nautical', 'Engineering') NULL AFTER application_type,
ADD COLUMN IF NOT EXISTS nmsa_approval_letter_path VARCHAR(500) NULL,
ADD COLUMN IF NOT EXISTS sea_service_record_path VARCHAR(500) NULL,
ADD COLUMN IF NOT EXISTS coc_number VARCHAR(100) NULL,
ADD COLUMN IF NOT EXISTS coc_expiry_date DATE NULL,
ADD COLUMN IF NOT EXISTS previous_student_id INT NULL,
ADD COLUMN IF NOT EXISTS requirements_met BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS requirements_notes TEXT NULL,
ADD COLUMN IF NOT EXISTS shortfalls_identified TEXT NULL,
ADD COLUMN IF NOT EXISTS shortfalls_addressed BOOLEAN DEFAULT FALSE,
ADD INDEX idx_application_type (application_type),
ADD INDEX idx_course_type (course_type),
ADD INDEX idx_requirements_met (requirements_met);

-- ============================================
-- UPDATE APPLICATION DOCUMENTS FOR CONTINUING STUDENTS
-- ============================================
ALTER TABLE application_documents
MODIFY COLUMN document_type ENUM(
    'grade_12_certificate', 
    'transcript', 
    'birth_certificate', 
    'medical_certificate', 
    'police_clearance', 
    'passport_photo',
    'nmsa_approval_letter',
    'sea_service_record',
    'coc_certificate',
    'previous_certificates',
    'other'
) NOT NULL;

-- ============================================
-- CONTINUING STUDENT REQUIREMENTS TRACKING
-- ============================================
CREATE TABLE IF NOT EXISTS continuing_student_requirements (
    requirement_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    requirement_type ENUM('nmsa_approval', 'sea_service_record', 'expression_of_interest', 'coc_validity', 'academic_prerequisites', 'financial_clearance', 'other') NOT NULL,
    requirement_name VARCHAR(200) NOT NULL,
    status ENUM('pending', 'met', 'not_met', 'shortfall_identified') DEFAULT 'pending',
    verified_by INT,
    verified_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_application_id (application_id),
    INDEX idx_status (status),
    INDEX idx_requirement_type (requirement_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

