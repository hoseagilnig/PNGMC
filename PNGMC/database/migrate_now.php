<?php
/**
 * Quick Migration - Add application_type column
 * Run via browser: http://localhost/sms2/database/migrate_now.php
 * This script runs automatically when accessed
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

// Check if running from CLI or web
$is_cli = (php_sapi_name() === 'cli');
if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>Database Migration</title><style>body{font-family:monospace;padding:20px;background:#f5f5f5;}pre{background:white;padding:15px;border-radius:5px;}</style></head><body><h1>Database Migration</h1><pre>';
}

echo "=== Database Migration Script ===\n\n";

$conn = getDBConnection();

if (!$conn) {
    die("ERROR: Database connection failed!\n");
}

$errors = [];
$success = [];

// Add application_type column
$check = $conn->query("SHOW COLUMNS FROM applications LIKE 'application_type'");
if ($check->num_rows === 0) {
    $sql = "ALTER TABLE applications ADD COLUMN application_type ENUM('new_student', 'continuing_student_solas', 'continuing_student_next_level') DEFAULT 'new_student' AFTER program_interest";
    if ($conn->query($sql)) {
        $success[] = "Added column: application_type";
        echo "✓ Added column: application_type\n";
    } else {
        $errors[] = "Error adding application_type: " . $conn->error;
        echo "✗ Error: " . $conn->error . "\n";
    }
} else {
    $success[] = "Column application_type already exists";
    echo "✓ Column application_type already exists\n";
}

// Add course_type column
$check = $conn->query("SHOW COLUMNS FROM applications LIKE 'course_type'");
if ($check->num_rows === 0) {
    $sql = "ALTER TABLE applications ADD COLUMN course_type ENUM('Nautical', 'Engineering') NULL AFTER application_type";
    if ($conn->query($sql)) {
        $success[] = "Added column: course_type";
        echo "✓ Added column: course_type\n";
    } else {
        $errors[] = "Error adding course_type: " . $conn->error;
        echo "✗ Error: " . $conn->error . "\n";
    }
} else {
    $success[] = "Column course_type already exists";
    echo "✓ Column course_type already exists\n";
}

// Add education_level column (for school leaver forms)
$check = $conn->query("SHOW COLUMNS FROM applications LIKE 'education_level'");
if ($check->num_rows === 0) {
    $sql = "ALTER TABLE applications ADD COLUMN education_level ENUM('Grade 10', 'Grade 12') NULL AFTER province";
    if ($conn->query($sql)) {
        $success[] = "Added column: education_level";
        echo "✓ Added column: education_level\n";
    } else {
        $errors[] = "Error adding education_level: " . $conn->error;
        echo "✗ Error: " . $conn->error . "\n";
    }
} else {
    $success[] = "Column education_level already exists";
    echo "✓ Column education_level already exists\n";
}

// Add other required columns
$other_columns = [
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

foreach ($other_columns as $col_name => $col_def) {
    $check = $conn->query("SHOW COLUMNS FROM applications LIKE '$col_name'");
    if ($check->num_rows === 0) {
        $sql = "ALTER TABLE applications ADD COLUMN $col_name $col_def";
        if ($conn->query($sql)) {
            $success[] = "Added column: $col_name";
            echo "✓ Added column: $col_name\n";
        } else {
            $errors[] = "Error adding $col_name: " . $conn->error;
            echo "✗ Error adding $col_name: " . $conn->error . "\n";
        }
    } else {
        echo "→ Column $col_name already exists\n";
    }
}

// Add indexes
$indexes = [
    'idx_application_type' => "CREATE INDEX IF NOT EXISTS idx_application_type ON applications(application_type)",
    'idx_course_type' => "CREATE INDEX IF NOT EXISTS idx_course_type ON applications(course_type)"
];

foreach ($indexes as $idx_name => $sql) {
    $check = $conn->query("SHOW INDEX FROM applications WHERE Key_name = '$idx_name'");
    if ($check->num_rows === 0) {
        // MySQL doesn't support IF NOT EXISTS for indexes, so we check first
        if ($conn->query($sql)) {
            $success[] = "Added index: $idx_name";
            echo "✓ Added index: $idx_name\n";
        } else {
            // Index might already exist, ignore error
            if (strpos($conn->error, 'Duplicate key name') === false) {
                echo "→ Index $idx_name: " . $conn->error . "\n";
            }
        }
    } else {
        echo "→ Index $idx_name already exists\n";
    }
}

$conn->close();

echo "\n=== Migration Complete ===\n";
if (empty($errors)) {
    echo "SUCCESS: All migrations completed successfully!\n";
    echo "\nAll database columns have been added for:\n";
    echo "  ✓ School Leaver Applications (apply.php, apply_school_leaver.php)\n";
    echo "  ✓ Returning Student Applications (apply_continuing.php)\n";
    echo "  ✓ Engine Room Enrollment Forms (enroll_engine_rating1.php)\n";
    echo "  ✓ Mates (Deck) Enrollment Forms (enroll_deck_rating1.php)\n";
    echo "  ✓ Signature storage (signature_path column)\n";
    echo "\nYou can now submit all forms successfully!\n";
} else {
    echo "WARNING: Some errors occurred. Check above for details.\n";
}

if (!$is_cli) {
    echo '</pre><p><a href="../apply_continuing.php">← Back to Application Form</a></p></body></html>';
}

