<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'finance') {
    http_response_code(403);
    exit;
}

require_once 'includes/db_config.php';

$student_id = intval($_GET['student_id'] ?? 0);
$conn = getDBConnection();

header('Content-Type: application/json');

if ($conn && $student_id) {
    $tables_exist = $conn->query("SHOW TABLES LIKE 'student_fees'")->num_rows > 0;
    
    if ($tables_exist) {
        $result = $conn->query("SELECT fee_id, description, outstanding_amount, due_date 
            FROM student_fees 
            WHERE student_id = $student_id AND outstanding_amount > 0 
            ORDER BY due_date ASC");
        
        $fees = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $fees[] = $row;
            }
        }
        
        echo json_encode($fees);
    } else {
        echo json_encode([]);
    }
    
    $conn->close();
} else {
    echo json_encode([]);
}
?>

