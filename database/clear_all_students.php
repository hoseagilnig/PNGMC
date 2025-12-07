<?php
/**
 * Clear All Student Data
 * This script will delete all student records and related data
 * 
 * WARNING: This will permanently delete all student records and related data!
 * 
 * Access via: http://localhost/sms2/database/clear_all_students.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$message = '';
$message_type = '';
$deleted_counts = [];
$executed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $conn = getDBConnection();
    
    if (!$conn) {
        $message = "Database connection failed!";
        $message_type = "error";
    } else {
        // Disable foreign key checks temporarily to avoid constraint issues
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        
        try {
            // Get counts before deletion for reporting
            $counts = [];
            
            // Count records in each table
            $tables_to_clear = [
                'student_accounts' => 'student_accounts',
                'student_course_history' => 'student_course_history',
                'student_notifications' => 'student_notifications',
                'enrollments' => 'enrollments',
                'dormitory_assignments' => 'dormitory_assignments',
                'proforma_invoices' => 'proforma_invoices',
                'proforma_invoice_payments' => 'proforma_invoice_payments',
                'withdrawal_advice' => 'withdrawal_advice',
                'disciplinary_advice' => 'disciplinary_advice',
                'student_schedules' => 'student_schedules',
                'student_fees' => 'student_fees',
                'student_holds' => 'student_holds',
                'fee_payment_history' => 'fee_payment_history',
                'students' => 'students'
            ];
            
            foreach ($tables_to_clear as $table_name => $table_query) {
                $table_check = $conn->query("SHOW TABLES LIKE '$table_name'");
                if ($table_check->num_rows > 0) {
                    $count_result = $conn->query("SELECT COUNT(*) as count FROM $table_query");
                    if ($count_result) {
                        $counts[$table_name] = $count_result->fetch_assoc()['count'];
                    }
                }
            }
            
            // Delete in order (child tables first, then parent)
            // Note: With CASCADE, deleting from students should delete related records,
            // but we'll delete explicitly to ensure everything is cleared
            
            // Delete student-related records
            $tables_order = [
                'student_accounts',
                'student_course_history',
                'student_notifications',
                'enrollments',
                'dormitory_assignments',
                'proforma_invoice_payments',
                'proforma_invoices',
                'withdrawal_advice',
                'disciplinary_advice',
                'student_schedules',
                'fee_payment_history',
                'student_holds',
                'student_fees'
            ];
            
            foreach ($tables_order as $table) {
                $table_check = $conn->query("SHOW TABLES LIKE '$table'");
                if ($table_check->num_rows > 0) {
                    $result = $conn->query("DELETE FROM $table");
                    $deleted_counts[$table] = $conn->affected_rows;
                }
            }
            
            // Update applications table to set student_id to NULL (not delete applications)
            $table_check = $conn->query("SHOW TABLES LIKE 'applications'");
            if ($table_check->num_rows > 0) {
                $col_check = $conn->query("SHOW COLUMNS FROM applications LIKE 'student_id'");
                if ($col_check->num_rows > 0) {
                    $result = $conn->query("UPDATE applications SET student_id = NULL, enrolled = FALSE WHERE student_id IS NOT NULL");
                    $deleted_counts['applications_updated'] = $conn->affected_rows;
                }
            }
            
            // Finally, delete all students
            $result = $conn->query("DELETE FROM students");
            $deleted_counts['students'] = $conn->affected_rows;
            
            // Reset AUTO_INCREMENT for students table
            $conn->query("ALTER TABLE students AUTO_INCREMENT = 1");
            
            // Re-enable foreign key checks
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            
            $message = "All student data has been successfully deleted!";
            $message_type = "success";
            $executed = true;
            
        } catch (Exception $e) {
            // Re-enable foreign key checks even on error
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            $message = "Error deleting data: " . $e->getMessage();
            $message_type = "error";
        }
        
        $conn->close();
    }
} else {
    // Get current counts for display
    $conn = getDBConnection();
    if ($conn) {
        $counts = [];
        
        $tables_to_check = [
            'student_accounts' => 'student_accounts',
            'student_course_history' => 'student_course_history',
            'student_notifications' => 'student_notifications',
            'enrollments' => 'enrollments',
            'dormitory_assignments' => 'dormitory_assignments',
            'proforma_invoices' => 'proforma_invoices',
            'proforma_invoice_payments' => 'proforma_invoice_payments',
            'withdrawal_advice' => 'withdrawal_advice',
            'disciplinary_advice' => 'disciplinary_advice',
            'student_schedules' => 'student_schedules',
            'student_fees' => 'student_fees',
            'student_holds' => 'student_holds',
            'fee_payment_history' => 'fee_payment_history',
            'students' => 'students'
        ];
        
        foreach ($tables_to_check as $table_name => $table_query) {
            $table_check = $conn->query("SHOW TABLES LIKE '$table_name'");
            if ($table_check->num_rows > 0) {
                $count_result = $conn->query("SELECT COUNT(*) as count FROM $table_query");
                if ($count_result) {
                    $counts[$table_name] = $count_result->fetch_assoc()['count'];
                } else {
                    $counts[$table_name] = 0;
                }
            } else {
                $counts[$table_name] = 0;
            }
        }
        
        // Check applications with student_id
        $table_check = $conn->query("SHOW TABLES LIKE 'applications'");
        if ($table_check->num_rows > 0) {
            $col_check = $conn->query("SHOW COLUMNS FROM applications LIKE 'student_id'");
            if ($col_check->num_rows > 0) {
                $count_result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE student_id IS NOT NULL");
                if ($count_result) {
                    $counts['applications_linked'] = $count_result->fetch_assoc()['count'];
                }
            }
        }
        
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clear All Students</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        
        .warning strong {
            color: #d9534f;
            font-size: 18px;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .counts-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 0.9rem;
        }
        
        .counts-table th,
        .counts-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .counts-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .counts-table tr:hover {
            background: #f5f5f5;
        }
        
        .count-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .count-badge.zero {
            background: #d4edda;
            color: #155724;
        }
        
        .count-badge.has-data {
            background: #f8d7da;
            color: #721c24;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            margin: 10px 5px;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .form-group {
            margin: 20px 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }
        
        .deleted-summary {
            background: #d1ecf1;
            border-left: 4px solid #0c5460;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .deleted-summary h3 {
            color: #0c5460;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗑️ Clear All Student Data</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($executed && !empty($deleted_counts)): ?>
            <div class="deleted-summary">
                <h3>Deletion Summary:</h3>
                <table class="counts-table">
                    <tr>
                        <th>Table</th>
                        <th>Records Deleted</th>
                    </tr>
                    <?php foreach ($deleted_counts as $table => $count): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($table); ?></td>
                            <td><span class="count-badge"><?php echo $count; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <p style="margin-top: 20px;">
                <a href="clear_all_students.php" class="btn btn-secondary">Refresh</a>
            </p>
        <?php else: ?>
            <div class="warning">
                <strong>⚠️ WARNING:</strong> This action will permanently delete ALL student data including:
                <ul style="margin: 10px 0 0 20px;">
                    <li>All student records</li>
                    <li>All student accounts</li>
                    <li>All enrollments</li>
                    <li>All student notifications</li>
                    <li>All proforma invoices and payments</li>
                    <li>All student schedules, fees, and holds</li>
                    <li>All course history</li>
                    <li>All dormitory assignments</li>
                </ul>
                <p style="margin-top: 10px;"><strong>This action cannot be undone!</strong></p>
            </div>
            
            <div class="info-box">
                <strong>Note:</strong> This will:
                <ul style="margin: 10px 0 0 20px;">
                    <li>Set student_id to NULL in applications (applications will NOT be deleted)</li>
                    <li>NOT delete staff user accounts</li>
                    <li>NOT delete system configuration</li>
                    <li>NOT delete chatbot knowledge base</li>
                </ul>
            </div>
            
            <?php if (isset($counts)): ?>
                <h2 style="margin-top: 30px; color: #333;">Current Data Counts:</h2>
                <table class="counts-table">
                    <tr>
                        <th>Table</th>
                        <th>Current Records</th>
                    </tr>
                    <?php 
                    $total_records = 0;
                    foreach ($counts as $table => $count): 
                        $total_records += $count;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($table); ?></td>
                            <td>
                                <span class="count-badge <?php echo $count > 0 ? 'has-data' : 'zero'; ?>">
                                    <?php echo $count; ?> record(s)
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <p style="margin-top: 15px; font-weight: 600; color: #333;">
                    Total records to be deleted: <strong><?php echo $total_records; ?></strong>
                </p>
            <?php endif; ?>
            
            <form method="POST" action="" style="margin-top: 30px;">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="confirm_delete" required>
                        I understand that this will permanently delete all student data and I want to proceed.
                    </label>
                </div>
                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you absolutely sure you want to delete ALL student data? This cannot be undone!');">
                    🗑️ Delete All Student Data
                </button>
                <a href="../index.html" class="btn btn-secondary">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

