<?php
/**
 * Automatic Archiving Script
 * PNG Maritime College - Student Management System
 * 
 * This script automatically archives records based on configured settings.
 * Can be run via cron job or manually.
 * 
 * Usage:
 * - Via browser: http://localhost/sms2/pages/auto_archive.php
 * - Via command line: php pages/auto_archive.php
 * - Via cron: 0 2 * * * php /path/to/pages/auto_archive.php
 */

require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/archive_helper.php';

// Set execution time limit for long-running script
set_time_limit(300); // 5 minutes

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed!\n");
}

$results = [
    'applications' => ['archived' => 0, 'errors' => 0],
    'students' => ['archived' => 0, 'errors' => 0],
    'invoices' => ['archived' => 0, 'errors' => 0]
];

// Get archive settings
$auto_archive_apps = getArchiveSetting('auto_archive_applications', 'false');
$archive_apps_days = intval(getArchiveSetting('archive_applications_after_days', '365'));
$auto_archive_students = getArchiveSetting('auto_archive_students', 'false');
$archive_students_days = intval(getArchiveSetting('archive_students_after_days', '730'));
$auto_archive_invoices = getArchiveSetting('auto_archive_invoices', 'false');
$archive_invoices_days = intval(getArchiveSetting('archive_invoices_after_days', '180'));

// System user ID for archiving (use admin user or create system user)
$system_user_id = 1; // Default to first admin user

// Get system user
$user_stmt = $conn->prepare("SELECT user_id FROM users WHERE role = 'admin' AND status = 'active' LIMIT 1");
$user_stmt->execute();
$user_result = $user_stmt->get_result();
if ($user_result->num_rows > 0) {
    $system_user_id = $user_result->fetch_assoc()['user_id'];
}
$user_stmt->close();

// 1. Auto-archive Applications
if ($auto_archive_apps === 'true' && $archive_apps_days > 0) {
    $cutoff_date = date('Y-m-d', strtotime("-{$archive_apps_days} days"));
    
    // Archive completed/rejected applications older than cutoff
    $stmt = $conn->prepare("
        SELECT application_id 
        FROM applications 
        WHERE status IN ('enrolled', 'rejected', 'ineligible')
        AND (enrollment_date < ? OR updated_at < ?)
        AND enrolled = TRUE
    ");
    $stmt->bind_param("ss", $cutoff_date, $cutoff_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $reason = "Auto-archived: Application completed/rejected more than {$archive_apps_days} days ago";
        $archive_result = archiveApplication($row['application_id'], $system_user_id, $reason, 'Automatic archiving');
        
        if ($archive_result['success']) {
            $results['applications']['archived']++;
        } else {
            $results['applications']['errors']++;
            error_log("Auto-archive error for application {$row['application_id']}: " . $archive_result['message']);
        }
    }
    $stmt->close();
}

// 2. Auto-archive Students
if ($auto_archive_students === 'true' && $archive_students_days > 0) {
    $cutoff_date = date('Y-m-d', strtotime("-{$archive_students_days} days"));
    
    // Archive inactive/graduated students older than cutoff
    $stmt = $conn->prepare("
        SELECT student_id 
        FROM students 
        WHERE status IN ('inactive', 'graduated', 'withdrawn')
        AND (graduation_date < ? OR updated_at < ?)
    ");
    $stmt->bind_param("ss", $cutoff_date, $cutoff_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $reason = "Auto-archived: Student inactive/graduated more than {$archive_students_days} days ago";
        $archive_result = archiveStudent($row['student_id'], $system_user_id, $reason, 'Automatic archiving');
        
        if ($archive_result['success']) {
            $results['students']['archived']++;
        } else {
            $results['students']['errors']++;
            error_log("Auto-archive error for student {$row['student_id']}: " . $archive_result['message']);
        }
    }
    $stmt->close();
}

// 3. Auto-archive Invoices
if ($auto_archive_invoices === 'true' && $archive_invoices_days > 0) {
    $cutoff_date = date('Y-m-d', strtotime("-{$archive_invoices_days} days"));
    
    // Archive paid invoices older than cutoff
    $stmt = $conn->prepare("
        SELECT invoice_id 
        FROM invoices 
        WHERE status = 'paid'
        AND payment_date < ?
    ");
    $stmt->bind_param("s", $cutoff_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $reason = "Auto-archived: Paid invoice older than {$archive_invoices_days} days";
        $archive_result = archiveInvoice($row['invoice_id'], $system_user_id, $reason, 'Automatic archiving');
        
        if ($archive_result['success']) {
            $results['invoices']['archived']++;
        } else {
            $results['invoices']['errors']++;
            error_log("Auto-archive error for invoice {$row['invoice_id']}: " . $archive_result['message']);
        }
    }
    $stmt->close();
}

$conn->close();

// Output results
if (php_sapi_name() === 'cli') {
    // Command line output
    echo "=== Auto-Archive Results ===\n";
    echo "Applications: {$results['applications']['archived']} archived, {$results['applications']['errors']} errors\n";
    echo "Students: {$results['students']['archived']} archived, {$results['students']['errors']} errors\n";
    echo "Invoices: {$results['invoices']['archived']} archived, {$results['invoices']['errors']} errors\n";
    echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
} else {
    // Web output
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Auto-Archive Results</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
            h1 { color: #1d4e89; }
            .result-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .success { color: #28a745; font-weight: bold; }
            .error { color: #dc3545; font-weight: bold; }
            .summary { background: #e7f3ff; padding: 20px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <h1>Automatic Archiving Results</h1>
        <div class="summary">
            <h2>Summary</h2>
            <p><strong>Applications:</strong> <span class="success"><?php echo $results['applications']['archived']; ?> archived</span>
            <?php if ($results['applications']['errors'] > 0): ?>
                , <span class="error"><?php echo $results['applications']['errors']; ?> errors</span>
            <?php endif; ?>
            </p>
            <p><strong>Students:</strong> <span class="success"><?php echo $results['students']['archived']; ?> archived</span>
            <?php if ($results['students']['errors'] > 0): ?>
                , <span class="error"><?php echo $results['students']['errors']; ?> errors</span>
            <?php endif; ?>
            </p>
            <p><strong>Invoices:</strong> <span class="success"><?php echo $results['invoices']['archived']; ?> archived</span>
            <?php if ($results['invoices']['errors'] > 0): ?>
                , <span class="error"><?php echo $results['invoices']['errors']; ?> errors</span>
            <?php endif; ?>
            </p>
            <p><strong>Completed at:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
        <div class="result-box">
            <h3>Settings Used</h3>
            <p>Auto-archive Applications: <?php echo $auto_archive_apps === 'true' ? 'Enabled' : 'Disabled'; ?> 
            <?php if ($auto_archive_apps === 'true'): ?>
                (<?php echo $archive_apps_days; ?> days)
            <?php endif; ?>
            </p>
            <p>Auto-archive Students: <?php echo $auto_archive_students === 'true' ? 'Enabled' : 'Disabled'; ?>
            <?php if ($auto_archive_students === 'true'): ?>
                (<?php echo $archive_students_days; ?> days)
            <?php endif; ?>
            </p>
            <p>Auto-archive Invoices: <?php echo $auto_archive_invoices === 'true' ? 'Enabled' : 'Disabled'; ?>
            <?php if ($auto_archive_invoices === 'true'): ?>
                (<?php echo $archive_invoices_days; ?> days)
            <?php endif; ?>
            </p>
        </div>
        <p><a href="archive_management.php">← Back to Archive Management</a></p>
    </body>
    </html>
    <?php
}
?>

