<?php
/**
 * Quick Fix - Run Migration
 * Execute this file to automatically add missing database columns
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed!\n");
}

echo "Running database migration...\n\n";

// Add application_type column
$check = $conn->query("SHOW COLUMNS FROM applications LIKE 'application_type'");
if ($check->num_rows === 0) {
    $sql = "ALTER TABLE applications ADD COLUMN application_type ENUM('new_student', 'continuing_student_solas', 'continuing_student_next_level') DEFAULT 'new_student' AFTER program_interest";
    if ($conn->query($sql)) {
        echo "✓ Added column: application_type\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
} else {
    echo "✓ Column application_type already exists\n";
}

// Add course_type column
$check = $conn->query("SHOW COLUMNS FROM applications LIKE 'course_type'");
if ($check->num_rows === 0) {
    $sql = "ALTER TABLE applications ADD COLUMN course_type ENUM('Nautical', 'Engineering') NULL AFTER application_type";
    if ($conn->query($sql)) {
        echo "✓ Added column: course_type\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
} else {
    echo "✓ Column course_type already exists\n";
}

// Add education_level column
$check = $conn->query("SHOW COLUMNS FROM applications LIKE 'education_level'");
if ($check->num_rows === 0) {
    $sql = "ALTER TABLE applications ADD COLUMN education_level ENUM('Grade 10', 'Grade 12') NULL AFTER province";
    if ($conn->query($sql)) {
        echo "✓ Added column: education_level\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
} else {
    echo "✓ Column education_level already exists\n";
}

// Add other columns
$columns = [
    'nmsa_approval_letter_path' => "VARCHAR(500) NULL",
    'sea_service_record_path' => "VARCHAR(500) NULL",
    'coc_number' => "VARCHAR(100) NULL",
    'coc_expiry_date' => "DATE NULL",
    'previous_student_id' => "INT NULL",
    'signature_path' => "VARCHAR(500) NULL",
    'requirements_met' => "BOOLEAN DEFAULT FALSE",
    'requirements_notes' => "TEXT NULL",
    'shortfalls_identified' => "TEXT NULL",
    'shortfalls_addressed' => "BOOLEAN DEFAULT FALSE"
];

foreach ($columns as $col_name => $col_def) {
    $check = $conn->query("SHOW COLUMNS FROM applications LIKE '$col_name'");
    if ($check->num_rows === 0) {
        $sql = "ALTER TABLE applications ADD COLUMN $col_name $col_def";
        if ($conn->query($sql)) {
            echo "✓ Added column: $col_name\n";
        } else {
            echo "✗ Error adding $col_name: " . $conn->error . "\n";
        }
    }
}

// Add indexes
$indexes = [
    'idx_application_type' => "CREATE INDEX idx_application_type ON applications(application_type)",
    'idx_course_type' => "CREATE INDEX idx_course_type ON applications(course_type)"
];

foreach ($indexes as $idx_name => $sql) {
    $check = $conn->query("SHOW INDEX FROM applications WHERE Key_name = '$idx_name'");
    if ($check->num_rows === 0) {
        if ($conn->query($sql)) {
            echo "✓ Added index: $idx_name\n";
        }
    }
}

$conn->close();

echo "\nMigration complete! You can now submit forms.\n";

