<?php
/**
 * Get Requirements API
 * Returns requirements for a specific application
 */

session_start();
require_once __DIR__ . '/includes/db_config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'studentservices', 'hod'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get application ID
$application_id = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0;

if ($application_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid application ID']);
    exit;
}

// Get database connection
$conn = getDBConnection();

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'continuing_student_requirements'");
if (!$table_check || $table_check->num_rows === 0) {
    // Table doesn't exist - return empty requirements list instead of error
    // This allows the page to load without breaking
    $conn->close();
    echo json_encode([
        'success' => true, 
        'requirements' => [],
        'message' => 'Requirements table not found. Please import database/continuing_students_tables.sql to enable requirements tracking.'
    ]);
    exit;
}

// First, check if requirements exist, if not, create them automatically
$check_sql = "SELECT COUNT(*) as count FROM continuing_student_requirements WHERE application_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('i', $application_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$check_row = $check_result->fetch_assoc();
$check_stmt->close();

// If no requirements exist, try to create them automatically
if ($check_row['count'] == 0) {
    require_once __DIR__ . '/includes/workflow_helper.php';
    
    // Get application type
    $app_sql = "SELECT application_type FROM applications WHERE application_id = ?";
    $app_stmt = $conn->prepare($app_sql);
    $app_stmt->bind_param('i', $application_id);
    $app_stmt->execute();
    $app_result = $app_stmt->get_result();
    $app_row = $app_result->fetch_assoc();
    $app_stmt->close();
    
    $application_type = $app_row['application_type'] ?? null;
    
    // Try to create requirements
    if (function_exists('createApplicationRequirements')) {
        createApplicationRequirements($application_id, $application_type);
    }
}

// Fetch requirements
$sql = "SELECT requirement_id, requirement_type, requirement_name, status, notes, verified_date 
        FROM continuing_student_requirements 
        WHERE application_id = ? 
        ORDER BY requirement_id ASC";
        
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $conn->close();
    echo json_encode([
        'success' => false, 
        'error' => 'Database query error: ' . $conn->error,
        'requirements' => []
    ]);
    exit;
}

$stmt->bind_param('i', $application_id);

if (!$stmt->execute()) {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    echo json_encode([
        'success' => false, 
        'error' => 'Query execution failed: ' . $error,
        'requirements' => []
    ]);
    exit;
}

$result = $stmt->get_result();

$requirements = [];
while ($row = $result->fetch_assoc()) {
    $requirements[] = [
        'requirement_id' => $row['requirement_id'],
        'requirement_type' => $row['requirement_type'],
        'requirement_name' => $row['requirement_name'],
        'status' => $row['status'],
        'notes' => $row['notes'] ?? '',
        'verified_date' => $row['verified_date']
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'requirements' => $requirements
]);

?>

