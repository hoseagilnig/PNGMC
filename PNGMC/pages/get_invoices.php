<?php
// AJAX endpoint to get invoices for a student
header('Content-Type: application/json');
require_once 'includes/db_config.php';

if (!isset($_GET['student_id'])) {
    echo json_encode([]);
    exit;
}

$student_id = intval($_GET['student_id']);
$conn = getDBConnection();

if ($conn) {
    $stmt = $conn->prepare("SELECT invoice_id, invoice_number, balance_amount FROM invoices WHERE student_id = ? AND balance_amount > 0 ORDER BY due_date");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $invoices = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    echo json_encode($invoices);
} else {
    echo json_encode([]);
}

