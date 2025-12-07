<?php
/**
 * Clear All Invoice Data
 * This script will delete all invoice records and related data
 * 
 * WARNING: This will permanently delete all invoice data!
 * 
 * Access via: http://localhost/sms2/database/clear_all_invoices.php
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
                'invoice_items' => 'invoice_items',
                'payments' => 'payments',
                'invoices' => 'invoices',
                'proforma_invoice_payments' => 'proforma_invoice_payments',
                'proforma_invoices' => 'proforma_invoices'
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
            
            // Delete invoice items (child of invoices)
            $table_check = $conn->query("SHOW TABLES LIKE 'invoice_items'");
            if ($table_check->num_rows > 0) {
                $result = $conn->query("DELETE FROM invoice_items");
                $deleted_counts['invoice_items'] = $conn->affected_rows;
            }
            
            // Delete payments (references invoices)
            $table_check = $conn->query("SHOW TABLES LIKE 'payments'");
            if ($table_check->num_rows > 0) {
                $result = $conn->query("DELETE FROM payments");
                $deleted_counts['payments'] = $conn->affected_rows;
            }
            
            // Delete proforma invoice payments (child of proforma_invoices)
            $table_check = $conn->query("SHOW TABLES LIKE 'proforma_invoice_payments'");
            if ($table_check->num_rows > 0) {
                $result = $conn->query("DELETE FROM proforma_invoice_payments");
                $deleted_counts['proforma_invoice_payments'] = $conn->affected_rows;
            }
            
            // Delete proforma invoices
            $table_check = $conn->query("SHOW TABLES LIKE 'proforma_invoices'");
            if ($table_check->num_rows > 0) {
                $result = $conn->query("DELETE FROM proforma_invoices");
                $deleted_counts['proforma_invoices'] = $conn->affected_rows;
            }
            
            // Finally, delete all invoices
            $table_check = $conn->query("SHOW TABLES LIKE 'invoices'");
            if ($table_check->num_rows > 0) {
                $result = $conn->query("DELETE FROM invoices");
                $deleted_counts['invoices'] = $conn->affected_rows;
            }
            
            // Update applications table to clear invoice references
            $table_check = $conn->query("SHOW TABLES LIKE 'applications'");
            if ($table_check->num_rows > 0) {
                $col_check = $conn->query("SHOW COLUMNS FROM applications LIKE 'invoice_id'");
                if ($col_check->num_rows > 0) {
                    $result = $conn->query("UPDATE applications SET invoice_id = NULL, invoice_sent = FALSE WHERE invoice_id IS NOT NULL");
                    $deleted_counts['applications_updated'] = $conn->affected_rows;
                }
            }
            
            // Reset AUTO_INCREMENT for invoice tables
            $invoice_tables = ['invoices', 'invoice_items', 'payments', 'proforma_invoices', 'proforma_invoice_payments'];
            foreach ($invoice_tables as $table) {
                $table_check = $conn->query("SHOW TABLES LIKE '$table'");
                if ($table_check->num_rows > 0) {
                    $conn->query("ALTER TABLE $table AUTO_INCREMENT = 1");
                }
            }
            
            // Re-enable foreign key checks
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            
            $message = "All invoice data has been successfully deleted!";
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
            'invoice_items' => 'invoice_items',
            'payments' => 'payments',
            'invoices' => 'invoices',
            'proforma_invoice_payments' => 'proforma_invoice_payments',
            'proforma_invoices' => 'proforma_invoices'
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
        
        // Count outstanding invoices
        $table_check = $conn->query("SHOW TABLES LIKE 'invoices'");
        if ($table_check->num_rows > 0) {
            $count_result = $conn->query("SELECT COUNT(*) as count FROM invoices WHERE status != 'paid'");
            if ($count_result) {
                $counts['outstanding_invoices'] = $count_result->fetch_assoc()['count'];
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
    <title>Clear All Invoices</title>
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
        <h1>🗑️ Clear All Invoice Data</h1>
        
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
                <a href="clear_all_invoices.php" class="btn btn-secondary">Refresh</a>
            </p>
        <?php else: ?>
            <div class="warning">
                <strong>⚠️ WARNING:</strong> This action will permanently delete ALL invoice data including:
                <ul style="margin: 10px 0 0 20px;">
                    <li>All invoices (regular and proforma)</li>
                    <li>All invoice items</li>
                    <li>All payment records</li>
                    <li>All proforma invoice payments</li>
                </ul>
                <p style="margin-top: 10px;"><strong>This action cannot be undone!</strong></p>
            </div>
            
            <div class="info-box">
                <strong>Note:</strong> This will:
                <ul style="margin: 10px 0 0 20px;">
                    <li>Set invoice_id to NULL in applications (applications will NOT be deleted)</li>
                    <li>NOT delete staff user accounts</li>
                    <li>NOT delete students (but their invoices will be deleted)</li>
                    <li>NOT delete system configuration</li>
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
                        if ($table !== 'outstanding_invoices') {
                            $total_records += $count;
                        }
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
                <?php if (isset($counts['outstanding_invoices'])): ?>
                    <p style="margin-top: 15px; font-weight: 600; color: #d9534f;">
                        ⚠️ Outstanding Invoices: <strong><?php echo $counts['outstanding_invoices']; ?></strong>
                    </p>
                <?php endif; ?>
                <p style="margin-top: 15px; font-weight: 600; color: #333;">
                    Total records to be deleted: <strong><?php echo $total_records; ?></strong>
                </p>
            <?php endif; ?>
            
            <form method="POST" action="" style="margin-top: 30px;">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="confirm_delete" required>
                        I understand that this will permanently delete all invoice data and I want to proceed.
                    </label>
                </div>
                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you absolutely sure you want to delete ALL invoice data? This cannot be undone!');">
                    🗑️ Delete All Invoice Data
                </button>
                <a href="../index.html" class="btn btn-secondary">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

