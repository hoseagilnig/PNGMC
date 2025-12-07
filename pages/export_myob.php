<?php
session_start();
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['finance', 'studentservices', 'admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/db_config.php';

$pi_id = intval($_GET['pi_id'] ?? 0);
$format = $_GET['format'] ?? 'csv';

$conn = getDBConnection();
$pi = null;
$payments = [];

if ($conn && $pi_id) {
    $tables_exist = $conn->query("SHOW TABLES LIKE 'proforma_invoices'")->num_rows > 0;
    
    if ($tables_exist) {
        // Get PI details
        $result = $conn->query("SELECT * FROM proforma_invoices WHERE pi_id = $pi_id");
        if ($result) {
            $pi = $result->fetch_assoc();
        }
        
        // Get payments
        if ($pi) {
            $result = $conn->query("SELECT * FROM proforma_invoice_payments WHERE pi_id = $pi_id ORDER BY payment_date DESC");
            if ($result) {
                $payments = $result->fetch_all(MYSQLI_ASSOC);
            }
        }
    }
    
    $conn->close();
}

if (!$pi) {
    header('Location: proforma_invoices.php');
    exit;
}

if ($format === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="PI_' . $pi['pi_number'] . '_MYOB_' . date('Y-m-d') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 (helps Excel recognize encoding)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header row
    fputcsv($output, ['Field', 'Value'], ',');
    
    // Invoice details
    fputcsv($output, ['Invoice Number', $pi['pi_number']], ',');
    fputcsv($output, ['Date', date('d/m/Y', strtotime($pi['date']))], ',');
    if ($pi['revised_pi_number']) {
        fputcsv($output, ['Revised PI Number', $pi['revised_pi_number']], ',');
        fputcsv($output, ['Revised Date', date('d/m/Y', strtotime($pi['revised_date']))], ',');
    }
    fputcsv($output, ['Student Name', $pi['student_name']], ',');
    fputcsv($output, ['Student Address', $pi['forwarding_address'] ?? ''], ',');
    fputcsv($output, ['Telephone', $pi['telephone'] ?? ''], ',');
    fputcsv($output, ['Mobile', $pi['mobile_number'] ?? ''], ',');
    fputcsv($output, ['Course Name', $pi['course_name']], ',');
    fputcsv($output, ['Course Fee (PGK)', number_format($pi['course_fee'], 2)], ',');
    fputcsv($output, ['Total Payments (PGK)', number_format($pi['total_payments'], 2)], ',');
    if ($pi['amendment_amount'] != 0) {
        fputcsv($output, ['Amendment Amount (PGK)', number_format($pi['amendment_amount'], 2)], ',');
        fputcsv($output, ['Amendment Reason', ucfirst($pi['amendment_reason'])], ',');
    }
    fputcsv($output, ['Balance (PGK)', number_format($pi['balance'], 2)], ',');
    fputcsv($output, ['Status', ucfirst(str_replace('_', ' ', $pi['status']))], ',');
    fputcsv($output, ['PI Issuing Officer', $pi['pi_issuing_officer'] ?? ''], ',');
    if ($pi['approval_by_registrar']) {
        fputcsv($output, ['Approved By Registrar', 'Yes'], ',');
        fputcsv($output, ['Approval Date', date('d/m/Y', strtotime($pi['approval_date']))], ',');
    }
    if ($pi['remarks']) {
        fputcsv($output, ['Remarks', $pi['remarks']], ',');
    }
    
    // Payments section
    if (!empty($payments)) {
        fputcsv($output, [], ','); // Empty row
        fputcsv($output, ['PAYMENTS'], ',');
        fputcsv($output, ['Receipt Number', 'Payment Date', 'Amount (PGK)', 'Payment Method'], ',');
        foreach ($payments as $payment) {
            fputcsv($output, [
                $payment['receipt_number'],
                date('d/m/Y', strtotime($payment['payment_date'])),
                number_format($payment['amount'], 2),
                ucfirst(str_replace('_', ' ', $payment['payment_method']))
            ], ',');
        }
    }
    
    fclose($output);
    exit;
}
?>

