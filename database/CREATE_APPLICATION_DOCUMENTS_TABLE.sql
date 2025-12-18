-- Create application_documents table if it doesn't exist
-- Run this if the table is missing

USE sms2_db;

CREATE TABLE IF NOT EXISTS application_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    document_type ENUM(
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
    ) NOT NULL,
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

