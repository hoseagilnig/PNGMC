<?php
/**
 * CLI Migration Script to Add Continuing Students Fields
 * Run this via: php database/run_migration.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

echo "Starting database migration...\n\n";

$conn = getDBConnection();

if (!$conn) {
    echo "ERROR: Database connection failed!\n";
    exit(1);
}

$changes_made = [];

// Check and add columns
$columns_to_add = [
    'application_type' => "ALTER TABLE applications ADD COLUMN application_type ENUM('new_student', 'continuing_student_solas', 'continuing_student_next_level') DEFAULT 'new_student' AFTER program_interest",
    'course_type' => "ALTER TABLE applications ADD COLUMN course_type ENUM('Nautical', 'Engineering') NULL AFTER application_type",
    'nmsa_approval_letter_path' => "ALTER TABLE applications ADD COLUMN nmsa_approval_letter_path VARCHAR(500) NULL",
    'sea_service_record_path' => "ALTER TABLE applications ADD COLUMN sea_service_record_path VARCHAR(500) NULL",
    'coc_number' => "ALTER TABLE applications ADD COLUMN coc_number VARCHAR(100) NULL",
    'coc_expiry_date' => "ALTER TABLE applications ADD COLUMN coc_expiry_date DATE NULL",
    'previous_student_id' => "ALTER TABLE applications ADD COLUMN previous_student_id INT NULL",
    'requirements_met' => "ALTER TABLE applications ADD COLUMN requirements_met BOOLEAN DEFAULT FALSE",
    'requirements_notes' => "ALTER TABLE applications ADD COLUMN requirements_notes TEXT NULL",
    'shortfalls_identified' => "ALTER TABLE applications ADD COLUMN shortfalls_identified TEXT NULL",
    'shortfalls_addressed' => "ALTER TABLE applications ADD COLUMN shortfalls_addressed BOOLEAN DEFAULT FALSE"
];

foreach ($columns_to_add as $col_name => $sql) {
    $check = $conn->query("SHOW COLUMNS FROM applications LIKE '$col_name'");
    if ($check->num_rows === 0) {
        if ($conn->query($sql)) {
            echo "✓ Added column: $col_name\n";
            $changes_made[] = "Added column: $col_name";
        } else {
            echo "✗ Error adding $col_name: " . $conn->error . "\n";
            $changes_made[] = "Error adding $col_name: " . $conn->error;
        }
    } else {
        echo "→ Column $col_name already exists\n";
        $changes_made[] = "Column $col_name already exists";
    }
}

// Add indexes
$indexes = [
    'idx_application_type' => "CREATE INDEX idx_application_type ON applications(application_type)",
    'idx_course_type' => "CREATE INDEX idx_course_type ON applications(course_type)",
    'idx_requirements_met' => "CREATE INDEX idx_requirements_met ON applications(requirements_met)"
];

foreach ($indexes as $idx_name => $sql) {
    $check = $conn->query("SHOW INDEX FROM applications WHERE Key_name = '$idx_name'");
    if ($check->num_rows === 0) {
        if ($conn->query($sql)) {
            echo "✓ Added index: $idx_name\n";
            $changes_made[] = "Added index: $idx_name";
        } else {
            echo "✗ Error adding index $idx_name: " . $conn->error . "\n";
        }
    } else {
        echo "→ Index $idx_name already exists\n";
    }
}

// Update application_documents document_type enum
$result = $conn->query("ALTER TABLE application_documents MODIFY COLUMN document_type ENUM('grade_12_certificate', 'transcript', 'birth_certificate', 'medical_certificate', 'police_clearance', 'passport_photo', 'nmsa_approval_letter', 'sea_service_record', 'coc_certificate', 'previous_certificates', 'other') NOT NULL");
if ($result) {
    echo "✓ Updated application_documents document_type enum\n";
    $changes_made[] = "Updated application_documents document_type enum";
} else {
    echo "→ application_documents document_type enum update: " . ($conn->error ?: "No changes needed") . "\n";
}

// Create continuing_student_requirements table
$check_table = $conn->query("SHOW TABLES LIKE 'continuing_student_requirements'");
if ($check_table->num_rows === 0) {
    $create_table = "CREATE TABLE IF NOT EXISTS continuing_student_requirements (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($create_table)) {
        echo "✓ Created table: continuing_student_requirements\n";
        $changes_made[] = "Created table: continuing_student_requirements";
    } else {
        echo "✗ Error creating table: " . $conn->error . "\n";
        $changes_made[] = "Error creating table: " . $conn->error;
    }
} else {
    echo "→ Table continuing_student_requirements already exists\n";
    $changes_made[] = "Table continuing_student_requirements already exists";
}

$conn->close();

echo "\nMigration completed!\n";
echo "You can now submit enrollment forms without errors.\n";

