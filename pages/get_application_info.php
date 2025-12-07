<?php
/**
 * Get Application Info API
 * Returns application information for modals
 */

session_start();
require_once __DIR__ . '/includes/db_config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'studentservices', 'hod', 'finance'])) {
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

// Fetch application
$sql = "SELECT application_id, application_number, first_name, last_name, email, phone, 
               program_interest, course_type, application_type, status, 
               student_id, invoice_id
        FROM applications 
        WHERE application_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $application_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => false, 'error' => 'Application not found']);
    exit;
}

$application = $result->fetch_assoc();
$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'application' => $application
]);

?>

