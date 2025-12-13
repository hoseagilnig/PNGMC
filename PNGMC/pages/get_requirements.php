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
if ($table_check->num_rows === 0) {
    $conn->close();
    echo json_encode([
        'success' => false, 
        'error' => 'Requirements table does not exist. Please run database migration.',
        'requirements' => []
    ]);
    exit;
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

