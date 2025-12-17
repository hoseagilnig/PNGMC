<?php
/**
 * Create Missing Requirements for Existing Applications
 * This script creates requirements for applications that don't have them yet
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed!");
}

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'continuing_student_requirements'");
if ($table_check->num_rows === 0) {
    die("The continuing_student_requirements table does not exist. Please run continuing_students_tables.sql first!");
}

echo "<h2>Creating Missing Requirements for Existing Applications</h2>";

// Get all applications that don't have requirements yet
$sql = "SELECT a.application_id, a.application_type 
        FROM applications a
        LEFT JOIN continuing_student_requirements r ON a.application_id = r.application_id
        WHERE r.application_id IS NULL
        ORDER BY a.application_id";

$result = $conn->query($sql);

if (!$result) {
    die("Error: " . $conn->error);
}

$count = 0;
$errors = 0;

require_once __DIR__ . '/../pages/includes/workflow_helper.php';

while ($row = $result->fetch_assoc()) {
    $application_id = $row['application_id'];
    $application_type = $row['application_type'] ?? 'new_student';
    
    echo "<p>Processing Application ID: {$application_id} (Type: {$application_type})... ";
    
    if (createApplicationRequirements($application_id, $application_type)) {
        echo "<strong style='color: green;'>✓ Created</strong></p>";
        $count++;
    } else {
        echo "<strong style='color: red;'>✗ Failed</strong></p>";
        $errors++;
    }
}

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<p><strong>Successfully created requirements for {$count} applications.</strong></p>";
if ($errors > 0) {
    echo "<p><strong style='color: red;'>Failed to create requirements for {$errors} applications.</strong></p>";
}

$conn->close();
?>
