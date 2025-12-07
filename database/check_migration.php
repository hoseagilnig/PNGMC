<?php
/**
 * Check if migration was successful
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$conn = getDBConnection();

if (!$conn) {
    echo "Database connection failed!\n";
    exit(1);
}

echo "Checking database columns...\n\n";

$required_columns = [
    'application_type',
    'course_type',
    'nmsa_approval_letter_path',
    'sea_service_record_path',
    'coc_number',
    'coc_expiry_date',
    'previous_student_id',
    'requirements_met',
    'requirements_notes',
    'shortfalls_identified',
    'shortfalls_addressed'
];

$missing = [];
$exists = [];

foreach ($required_columns as $col) {
    $result = $conn->query("SHOW COLUMNS FROM applications LIKE '$col'");
    if ($result->num_rows > 0) {
        $exists[] = $col;
        echo "✓ $col exists\n";
    } else {
        $missing[] = $col;
        echo "✗ $col MISSING\n";
    }
}

$conn->close();

echo "\n";
if (empty($missing)) {
    echo "SUCCESS: All required columns exist!\n";
    exit(0);
} else {
    echo "ERROR: Missing columns: " . implode(', ', $missing) . "\n";
    echo "Please run the migration script.\n";
    exit(1);
}

