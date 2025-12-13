<?php
/**
 * Create Fee Management Tables
 * This script creates all necessary tables for the comprehensive fee management system
 * 
 * Access via: http://localhost/sms2/database/create_fee_management_tables.php
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
        // 1. Fee Plans Table
        $sql = "CREATE TABLE IF NOT EXISTS fee_plans (
            plan_id INT AUTO_INCREMENT PRIMARY KEY,
            plan_name VARCHAR(255) NOT NULL,
            plan_type ENUM('admission', 'program', 'service', 'exam', 'lodging', 'other') NOT NULL,
            description TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_plan_type (plan_type),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            $tables_created[] = "fee_plans";
        }
        
        // 2. Fee Items Table (individual fees within a plan)
        $sql = "CREATE TABLE IF NOT EXISTS fee_items (
            item_id INT AUTO_INCREMENT PRIMARY KEY,
            plan_id INT NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            item_type ENUM('tuition', 'admission_fee', 'admin_fee', 'exam_fee', 'lodging_fee', 'service_fee', 'penalty', 'discount', 'scholarship', 'sponsorship', 'other') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            is_percentage BOOLEAN DEFAULT FALSE,
            applies_to ENUM('all', 'program', 'batch', 'student') DEFAULT 'all',
            program_id INT NULL,
            batch_id INT NULL,
            student_id INT NULL,
            due_date_offset INT DEFAULT 0 COMMENT 'Days from trigger point',
            is_optional BOOLEAN DEFAULT FALSE,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (plan_id) REFERENCES fee_plans(plan_id) ON DELETE CASCADE,
            INDEX idx_plan_id (plan_id),
            INDEX idx_item_type (item_type),
            INDEX idx_program_id (program_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            $tables_created[] = "fee_items";
        }
        
        // 3. Program Fee Plans Link Table
        $sql = "CREATE TABLE IF NOT EXISTS program_fee_plans (
            link_id INT AUTO_INCREMENT PRIMARY KEY,
            program_id INT NOT NULL,
            plan_id INT NOT NULL,
            batch_year YEAR NULL,
            effective_from DATE NULL,
            effective_to DATE NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (plan_id) REFERENCES fee_plans(plan_id) ON DELETE CASCADE,
            UNIQUE KEY unique_program_plan_batch (program_id, plan_id, batch_year),
            INDEX idx_program_id (program_id),
            INDEX idx_plan_id (plan_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            $tables_created[] = "program_fee_plans";
        }
        
        // 4. Student Fees Table (tracks fees assigned to students)
        $sql = "CREATE TABLE IF NOT EXISTS student_fees (
            fee_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            invoice_id INT NULL,
            plan_id INT NOT NULL,
            item_id INT NOT NULL,
            fee_type ENUM('tuition', 'admission_fee', 'admin_fee', 'exam_fee', 'lodging_fee', 'service_fee', 'penalty', 'discount', 'scholarship', 'sponsorship', 'other') NOT NULL,
            description VARCHAR(500),
            amount DECIMAL(10,2) NOT NULL,
            discount_amount DECIMAL(10,2) DEFAULT 0,
            scholarship_amount DECIMAL(10,2) DEFAULT 0,
            sponsorship_amount DECIMAL(10,2) DEFAULT 0,
            net_amount DECIMAL(10,2) NOT NULL COMMENT 'amount - discounts + penalties',
            due_date DATE NOT NULL,
            paid_amount DECIMAL(10,2) DEFAULT 0,
            outstanding_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'partial', 'paid', 'overdue', 'waived', 'cancelled') DEFAULT 'pending',
            payment_due_date DATE NULL,
            paid_date DATE NULL,
            late_fee_applied DECIMAL(10,2) DEFAULT 0,
            early_bird_discount DECIMAL(10,2) DEFAULT 0,
            trigger_point ENUM('admission', 'enrollment', 'semester_start', 'semester_end', 'exam_registration', 'graduation', 'custom') NULL,
            trigger_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
            FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE SET NULL,
            FOREIGN KEY (plan_id) REFERENCES fee_plans(plan_id) ON DELETE CASCADE,
            INDEX idx_student_id (student_id),
            INDEX idx_invoice_id (invoice_id),
            INDEX idx_status (status),
            INDEX idx_due_date (due_date),
            INDEX idx_outstanding (outstanding_amount),
            INDEX idx_trigger_point (trigger_point)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            $tables_created[] = "student_fees";
        }
        
        // 5. Automated Invoice Triggers Table
        $sql = "CREATE TABLE IF NOT EXISTS automated_triggers (
            trigger_id INT AUTO_INCREMENT PRIMARY KEY,
            trigger_name VARCHAR(255) NOT NULL,
            trigger_point ENUM('admission', 'enrollment', 'semester_start', 'semester_end', 'exam_registration', 'graduation', 'custom') NOT NULL,
            plan_id INT NOT NULL,
            days_before INT DEFAULT 0 COMMENT 'Days before trigger point to generate invoice',
            days_after INT DEFAULT 0 COMMENT 'Days after trigger point to generate invoice',
            red_day INT DEFAULT 0 COMMENT 'Days before due date for RED alert',
            green_day INT DEFAULT 0 COMMENT 'Days before due date for GREEN alert',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (plan_id) REFERENCES fee_plans(plan_id) ON DELETE CASCADE,
            INDEX idx_trigger_point (trigger_point),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            $tables_created[] = "automated_triggers";
        }
        
        // 6. Payment Reminders Table
        $sql = "CREATE TABLE IF NOT EXISTS payment_reminders (
            reminder_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            fee_id INT NOT NULL,
            reminder_type ENUM('email', 'sms', 'letter', 'system_notification') NOT NULL,
            reminder_date DATE NOT NULL,
            message TEXT,
            sent_at TIMESTAMP NULL,
            status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
            recipient_email VARCHAR(255),
            recipient_phone VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
            FOREIGN KEY (fee_id) REFERENCES student_fees(fee_id) ON DELETE CASCADE,
            INDEX idx_student_id (student_id),
            INDEX idx_fee_id (fee_id),
            INDEX idx_status (status),
            INDEX idx_reminder_date (reminder_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            $tables_created[] = "payment_reminders";
        }
        
        // 7. Student Holds Table (for automatic holds based on outstanding fees)
        $sql = "CREATE TABLE IF NOT EXISTS student_holds (
            hold_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            hold_type ENUM('financial', 'academic', 'administrative', 'other') NOT NULL,
            reason TEXT NOT NULL,
            outstanding_amount DECIMAL(10,2) DEFAULT 0,
            is_automatic BOOLEAN DEFAULT FALSE COMMENT 'True if automatically created due to outstanding fees',
            is_active BOOLEAN DEFAULT TRUE,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolved_at TIMESTAMP NULL,
            resolved_by INT NULL,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
            FOREIGN KEY (resolved_by) REFERENCES users(user_id) ON DELETE SET NULL,
            INDEX idx_student_id (student_id),
            INDEX idx_is_active (is_active),
            INDEX idx_hold_type (hold_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            $tables_created[] = "student_holds";
        }
        
        // 8. Fee Payment History Table (for detailed tracking)
        $sql = "CREATE TABLE IF NOT EXISTS fee_payment_history (
            history_id INT AUTO_INCREMENT PRIMARY KEY,
            fee_id INT NOT NULL,
            payment_id INT NULL,
            student_id INT NOT NULL,
            amount_paid DECIMAL(10,2) NOT NULL,
            payment_date DATE NOT NULL,
            payment_method ENUM('cash', 'bank_transfer', 'check', 'card', 'online', 'other') NOT NULL,
            reference_number VARCHAR(100),
            notes TEXT,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (fee_id) REFERENCES student_fees(fee_id) ON DELETE CASCADE,
            FOREIGN KEY (payment_id) REFERENCES payments(payment_id) ON DELETE SET NULL,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
            INDEX idx_fee_id (fee_id),
            INDEX idx_student_id (student_id),
            INDEX idx_payment_date (payment_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            $tables_created[] = "fee_payment_history";
        }
        
        // Add columns to existing students table if they don't exist
        $columns_to_add = [
            'total_fees_due' => "DECIMAL(10,2) DEFAULT 0",
            'total_fees_paid' => "DECIMAL(10,2) DEFAULT 0",
            'total_outstanding' => "DECIMAL(10,2) DEFAULT 0",
            'has_financial_hold' => "BOOLEAN DEFAULT FALSE",
            'last_fee_update' => "TIMESTAMP NULL"
        ];
        
        foreach ($columns_to_add as $column => $definition) {
            $check = $conn->query("SHOW COLUMNS FROM students LIKE '$column'");
            if ($check->num_rows == 0) {
                $conn->query("ALTER TABLE students ADD COLUMN $column $definition");
            }
        }
        
        if (!empty($tables_created)) {
            $message = "Fee management tables created successfully: " . implode(', ', $tables_created);
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
    <title>Create Fee Management Tables</title>
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
        <h1>Create Fee Management Tables</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <p>This script will create the following tables for the comprehensive fee management system:</p>
        
        <div class="table-list">
            <ul>
                <li><strong>fee_plans</strong> - Fee plan templates (admission, program, service, exam, lodging)</li>
                <li><strong>fee_items</strong> - Individual fee items within plans</li>
                <li><strong>program_fee_plans</strong> - Links fee plans to programs and batches</li>
                <li><strong>student_fees</strong> - Tracks fees assigned to students</li>
                <li><strong>automated_triggers</strong> - Automated invoice trigger points (RED/GREEN DAYS)</li>
                <li><strong>payment_reminders</strong> - Payment reminder system</li>
                <li><strong>student_holds</strong> - Automatic student holds based on outstanding fees</li>
                <li><strong>fee_payment_history</strong> - Detailed payment tracking</li>
            </ul>
        </div>
        
        <p><strong>Note:</strong> This will also add columns to the existing <code>students</code> table for fee tracking.</p>
        
        <form method="POST">
            <button type="submit" name="create_tables">Create Fee Management Tables</button>
        </form>
    </div>
</body>
</html>

