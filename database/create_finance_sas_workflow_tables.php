<?php
/**
 * Create Finance-SAS Workflow Tables
 * This script creates tables for the workflow between Finance and Student Admin Services
 * 
 * Access via: http://localhost/sms2/database/create_finance_sas_workflow_tables.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$message = '';
$message_type = '';
$tables_created = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_tables'])) {
    $conn = getDBConnection();
    
    if (!$conn) {
        $message = "Database connection failed!";
        $message_type = "error";
    } else {
        // 1. Proforma Invoices Table
        $sql = "CREATE TABLE IF NOT EXISTS proforma_invoices (
            pi_id INT AUTO_INCREMENT PRIMARY KEY,
            pi_number VARCHAR(50) NOT NULL UNIQUE,
            date DATE NOT NULL,
            revised_pi_number VARCHAR(50) NULL,
            revised_date DATE NULL,
            student_id INT NOT NULL,
            student_name VARCHAR(255) NOT NULL,
            forwarding_address TEXT,
            telephone VARCHAR(50),
            mobile_number VARCHAR(50),
            course_name VARCHAR(255) NOT NULL,
            course_fee DECIMAL(10,2) NOT NULL,
            total_payments DECIMAL(10,2) DEFAULT 0,
            amendment_amount DECIMAL(10,2) DEFAULT 0,
            amendment_reason ENUM('withdrawal', 'disciplinary', 'other') NULL,
            balance DECIMAL(10,2) NOT NULL,
            status ENUM('outstanding', 'refund_due') NOT NULL,
            pi_issuing_officer INT NULL,
            approval_by_registrar INT NULL,
            approval_date DATE NULL,
            remarks TEXT,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
            FOREIGN KEY (pi_issuing_officer) REFERENCES users(user_id) ON DELETE SET NULL,
            FOREIGN KEY (approval_by_registrar) REFERENCES users(user_id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_pi_number (pi_number),
            INDEX idx_student_id (student_id),
            INDEX idx_date (date),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            $tables_created[] = "proforma_invoices";
        }
        
        // 2. Proforma Invoice Payments Table
        $sql = "CREATE TABLE IF NOT EXISTS proforma_invoice_payments (
            payment_id INT AUTO_INCREMENT PRIMARY KEY,
            pi_id INT NOT NULL,
            receipt_number VARCHAR(100),
            payment_date DATE NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method ENUM('self', 'govt_funding', 'shipping_company', 'diversion', 'other') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pi_id) REFERENCES proforma_invoices(pi_id) ON DELETE CASCADE,
            INDEX idx_pi_id (pi_id),
            INDEX idx_payment_date (payment_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            $tables_created[] = "proforma_invoice_payments";
        }
        
        // 3. Withdrawal Advice Table
        $sql = "CREATE TABLE IF NOT EXISTS withdrawal_advice (
            withdrawal_id INT AUTO_INCREMENT PRIMARY KEY,
            advice_number VARCHAR(50) NOT NULL UNIQUE,
            student_id INT NOT NULL,
            student_name VARCHAR(255) NOT NULL,
            program_course_name VARCHAR(255) NOT NULL,
            program_course_fee DECIMAL(10,2) NOT NULL,
            reason ENUM('disciplinary', 'incomplete_fees', 'sickness', 'death', 'other') NOT NULL,
            reason_details TEXT,
            action_taken ENUM('returning', 'charged', 'black_list', 'none') NOT NULL,
            fee_amendment DECIMAL(10,2) DEFAULT 0 COMMENT 'reduction or addition',
            amendment_type ENUM('reduction', 'addition') NULL,
            start_date DATE NOT NULL,
            ending_date DATE NULL,
            paid_fees DECIMAL(10,2) DEFAULT 0,
            balance DECIMAL(10,2) NOT NULL,
            remarks TEXT,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_student_id (student_id),
            INDEX idx_advice_number (advice_number),
            INDEX idx_reason (reason)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            $tables_created[] = "withdrawal_advice";
        }
        
        // 4. Disciplinary Advice Table
        $sql = "CREATE TABLE IF NOT EXISTS disciplinary_advice (
            disciplinary_id INT AUTO_INCREMENT PRIMARY KEY,
            advice_number VARCHAR(50) NOT NULL UNIQUE,
            student_id INT NOT NULL,
            student_name VARCHAR(255) NOT NULL,
            student_address TEXT,
            program_course_name VARCHAR(255) NOT NULL,
            program_course_fee DECIMAL(10,2) NOT NULL,
            reason ENUM('drinking', 'fighting', 'swearing', 'night_out', 'drugs', 'relationship', 'other') NOT NULL,
            reason_details TEXT,
            action_taken ENUM('suspension', 'withdraw', 'charge', 'black_list', 'none') NOT NULL,
            fee_amendment DECIMAL(10,2) DEFAULT 0 COMMENT 'reduction or addition',
            amendment_type ENUM('reduction', 'addition') NULL,
            start_date DATE NOT NULL,
            ending_date DATE NULL,
            paid_fees DECIMAL(10,2) DEFAULT 0,
            balance DECIMAL(10,2) NOT NULL,
            remarks TEXT,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_student_id (student_id),
            INDEX idx_advice_number (advice_number),
            INDEX idx_reason (reason)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            $tables_created[] = "disciplinary_advice";
        }
        
        // 5. Student Schedules Table
        $sql = "CREATE TABLE IF NOT EXISTS student_schedules (
            schedule_id INT AUTO_INCREMENT PRIMARY KEY,
            schedule_type ENUM('currently_attending', 'withdrawal', 'disciplinary', 'non_attending', 'fees_monitor', 'red_green_days') NOT NULL,
            student_id INT NOT NULL,
            student_number VARCHAR(50) NOT NULL,
            student_name VARCHAR(255) NOT NULL,
            student_address TEXT,
            sponsor_name VARCHAR(255),
            sponsor_type ENUM('self', 'govt_sponsors', 'shipping_company_sponsors', 'other') NULL,
            program_course_name VARCHAR(255) NOT NULL,
            program_course_start_date DATE,
            program_course_ending_date DATE,
            program_course_tuition_fee DECIMAL(10,2) NOT NULL,
            amount_paid DECIMAL(10,2) DEFAULT 0,
            balance DECIMAL(10,2) NOT NULL,
            dates_fees_paid TEXT COMMENT 'Comma-separated dates',
            proforma_invoice_number VARCHAR(50),
            revised_pi_number VARCHAR(50),
            -- For disciplinary students
            disciplinary_reason ENUM('drinking', 'fighting', 'swearing', 'night_out', 'drugs', 'relationship', 'other') NULL,
            disciplinary_action ENUM('suspension', 'withdraw', 'charge', 'black_list', 'none') NULL,
            -- For withdrawal students
            withdrawal_reason ENUM('disciplinary', 'incomplete_fees', 'sickness', 'death', 'other') NULL,
            withdrawal_action ENUM('returning', 'charged', 'black_list', 'none') NULL,
            -- Fee amendments
            fee_amendment DECIMAL(10,2) DEFAULT 0,
            amendment_type ENUM('reduction', 'addition') NULL,
            -- For red & green days
            daily_rate DECIMAL(10,2) NULL COMMENT 'Daily rate to determine days paid/non paid',
            days_paid INT DEFAULT 0,
            days_non_paid INT DEFAULT 0,
            overstayed_days INT DEFAULT 0,
            unpaid_days INT DEFAULT 0,
            alert_flag ENUM('green', 'yellow', 'red') DEFAULT 'green',
            remarks TEXT,
            generated_by INT NULL,
            generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
            FOREIGN KEY (generated_by) REFERENCES users(user_id) ON DELETE SET NULL,
            INDEX idx_schedule_type (schedule_type),
            INDEX idx_student_id (student_id),
            INDEX idx_alert_flag (alert_flag)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            $tables_created[] = "student_schedules";
        }
        
        // 6. Finance to SAS Data Transfer Table
        $sql = "CREATE TABLE IF NOT EXISTS finance_to_sas_transfers (
            transfer_id INT AUTO_INCREMENT PRIMARY KEY,
            receipt_number VARCHAR(100) NOT NULL,
            payment_date DATE NOT NULL,
            student_id INT NULL,
            amount DECIMAL(10,2) NOT NULL,
            ar_record_summary TEXT COMMENT 'MYOB AR Summary',
            ar_record_individual TEXT COMMENT 'MYOB AR Individual Record',
            transferred_by INT NOT NULL,
            transferred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE SET NULL,
            FOREIGN KEY (transferred_by) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_receipt_number (receipt_number),
            INDEX idx_payment_date (payment_date),
            INDEX idx_student_id (student_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            $tables_created[] = "finance_to_sas_transfers";
        }
        
        if (!empty($tables_created)) {
            $message = "Finance-SAS workflow tables created successfully: " . implode(', ', $tables_created);
            $message_type = "success";
        } else {
            $message = "All tables already exist or there was an error.";
            $message_type = "info";
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
    <title>Create Finance-SAS Workflow Tables</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d4e89;
        }
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        button {
            background: #1d4e89;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #163c6a;
        }
        .table-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .table-list ul {
            list-style: none;
            padding: 0;
        }
        .table-list li {
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }
        .table-list li:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create Finance-SAS Workflow Tables</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <p>This script will create the following tables for the Finance-SAS workflow system:</p>
        
        <div class="table-list">
            <ul>
                <li><strong>proforma_invoices</strong> - Proforma Invoice management with all required fields</li>
                <li><strong>proforma_invoice_payments</strong> - Payment records linked to Proforma Invoices</li>
                <li><strong>withdrawal_advice</strong> - Withdrawal Advice from Student Admin Services</li>
                <li><strong>disciplinary_advice</strong> - Disciplinary Advice from Student Admin Services</li>
                <li><strong>student_schedules</strong> - All student schedules (Currently Attending, Withdrawal, Disciplinary, Non Attending, Fees Monitor, Red & Green Days)</li>
                <li><strong>finance_to_sas_transfers</strong> - Data transfer from Finance to SAS (Payment Receipts, AR Records)</li>
            </ul>
        </div>
        
        <p><strong>Note:</strong> These tables support the complete workflow between Finance and Student Admin Services as described in the workflow diagrams.</p>
        
        <form method="POST">
            <button type="submit" name="create_tables">Create Finance-SAS Workflow Tables</button>
        </form>
    </div>
</body>
</html>

