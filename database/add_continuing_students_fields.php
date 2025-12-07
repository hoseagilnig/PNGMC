<?php
/**
 * Migration Script to Add Continuing Students Fields
 * This script adds fields needed for continuing students (returning after sea service)
 * 
 * Access via: http://localhost/sms2/database/add_continuing_students_fields.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$message = '';
$message_type = '';
$changes_made = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fields'])) {
    $conn = getDBConnection();
    
    if (!$conn) {
        $message = "Database connection failed!";
        $message_type = "error";
    } else {
        // Check and add columns
        $columns_to_add = [
            'application_type' => "ALTER TABLE applications ADD COLUMN application_type ENUM('new_student', 'continuing_student_solas', 'continuing_student_next_level') DEFAULT 'new_student' AFTER program_interest",
            'course_type' => "ALTER TABLE applications ADD COLUMN course_type ENUM('Nautical', 'Engineering') NULL AFTER application_type",
            'nmsa_approval_letter_path' => "ALTER TABLE applications ADD COLUMN nmsa_approval_letter_path VARCHAR(500) NULL",
            'sea_service_record_path' => "ALTER TABLE applications ADD COLUMN sea_service_record_path VARCHAR(500) NULL",
            'coc_number' => "ALTER TABLE applications ADD COLUMN coc_number VARCHAR(100) NULL",
            'coc_expiry_date' => "ALTER TABLE applications ADD COLUMN coc_expiry_date DATE NULL",
            'previous_student_id' => "ALTER TABLE applications ADD COLUMN previous_student_id INT NULL",
            'requirements_met' => "ALTER TABLE applications ADD COLUMN requirements_met BOOLEAN DEFAULT FALSE",
            'requirements_notes' => "ALTER TABLE applications ADD COLUMN requirements_notes TEXT NULL",
            'shortfalls_identified' => "ALTER TABLE applications ADD COLUMN shortfalls_identified TEXT NULL",
            'shortfalls_addressed' => "ALTER TABLE applications ADD COLUMN shortfalls_addressed BOOLEAN DEFAULT FALSE"
        ];
        
        foreach ($columns_to_add as $col_name => $sql) {
            $check = $conn->query("SHOW COLUMNS FROM applications LIKE '$col_name'");
            if ($check->num_rows === 0) {
                if ($conn->query($sql)) {
                    $changes_made[] = "Added column: $col_name";
                } else {
                    $changes_made[] = "Error adding $col_name: " . $conn->error;
                }
            } else {
                $changes_made[] = "Column $col_name already exists";
            }
        }
        
        // Add indexes
        $indexes = [
            'idx_application_type' => "CREATE INDEX idx_application_type ON applications(application_type)",
            'idx_course_type' => "CREATE INDEX idx_course_type ON applications(course_type)",
            'idx_requirements_met' => "CREATE INDEX idx_requirements_met ON applications(requirements_met)"
        ];
        
        foreach ($indexes as $idx_name => $sql) {
            $check = $conn->query("SHOW INDEX FROM applications WHERE Key_name = '$idx_name'");
            if ($check->num_rows === 0) {
                if ($conn->query($sql)) {
                    $changes_made[] = "Added index: $idx_name";
                }
            }
        }
        
        // Update application_documents document_type enum
        $conn->query("ALTER TABLE application_documents MODIFY COLUMN document_type ENUM('grade_12_certificate', 'transcript', 'birth_certificate', 'medical_certificate', 'police_clearance', 'passport_photo', 'nmsa_approval_letter', 'sea_service_record', 'coc_certificate', 'previous_certificates', 'other') NOT NULL");
        $changes_made[] = "Updated application_documents document_type enum";
        
        // Create continuing_student_requirements table
        $check_table = $conn->query("SHOW TABLES LIKE 'continuing_student_requirements'");
        if ($check_table->num_rows === 0) {
            $create_table = "CREATE TABLE IF NOT EXISTS continuing_student_requirements (
                requirement_id INT AUTO_INCREMENT PRIMARY KEY,
                application_id INT NOT NULL,
                requirement_type ENUM('nmsa_approval', 'sea_service_record', 'expression_of_interest', 'coc_validity', 'academic_prerequisites', 'financial_clearance', 'other') NOT NULL,
                requirement_name VARCHAR(200) NOT NULL,
                status ENUM('pending', 'met', 'not_met', 'shortfall_identified') DEFAULT 'pending',
                verified_by INT,
                verified_date DATE,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE,
                FOREIGN KEY (verified_by) REFERENCES users(user_id) ON DELETE SET NULL,
                INDEX idx_application_id (application_id),
                INDEX idx_status (status),
                INDEX idx_requirement_type (requirement_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if ($conn->query($create_table)) {
                $changes_made[] = "Created table: continuing_student_requirements";
            } else {
                $changes_made[] = "Error creating table: " . $conn->error;
            }
        } else {
            $changes_made[] = "Table continuing_student_requirements already exists";
        }
        
        if (empty(array_filter($changes_made, function($m) { return strpos($m, 'Error') !== false; }))) {
            $message = "Successfully added continuing students fields!";
            $message_type = "success";
        } else {
            $message = "Some changes were made, but there were errors. See details below.";
            $message_type = "warning";
        }
        
        $conn->close();
    }
} else {
    // Check current status
    $conn = getDBConnection();
    if ($conn) {
        $check_cols = ['application_type', 'course_type', 'nmsa_approval_letter_path', 'sea_service_record_path'];
        $existing_cols = [];
        foreach ($check_cols as $col) {
            $result = $conn->query("SHOW COLUMNS FROM applications LIKE '$col'");
            if ($result->num_rows > 0) {
                $existing_cols[] = $col;
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
    <title>Add Continuing Students Fields</title>
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
            margin-bottom: 20px;
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
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        button {
            background: #1d4e89;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }
        button:hover {
            background: #163c6a;
        }
        .changes-list {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            max-height: 400px;
            overflow-y: auto;
        }
        .changes-list li {
            margin: 5px 0;
            padding: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Continuing Students Fields</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($changes_made)): ?>
            <div class="changes-list">
                <strong>Changes Made:</strong>
                <ul>
                    <?php foreach ($changes_made as $change): ?>
                        <li><?php echo htmlspecialchars($change); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <p>This script adds the necessary database fields and tables to support continuing students (candidates returning after sea service).</p>
        
        <p><strong>Fields to be added:</strong></p>
        <ul>
            <li>application_type (new_student, continuing_student_solas, continuing_student_next_level)</li>
            <li>course_type (Nautical, Engineering)</li>
            <li>nmsa_approval_letter_path</li>
            <li>sea_service_record_path</li>
            <li>coc_number</li>
            <li>coc_expiry_date</li>
            <li>previous_student_id</li>
            <li>requirements_met</li>
            <li>requirements_notes</li>
            <li>shortfalls_identified</li>
            <li>shortfalls_addressed</li>
        </ul>
        
        <p><strong>Table to be created:</strong></p>
        <ul>
            <li>continuing_student_requirements</li>
        </ul>
        
        <form method="POST">
            <button type="submit" name="add_fields">Add Continuing Students Fields</button>
        </form>
        
        <p style="margin-top: 30px;">
            <a href="../apply_continuing.php">← View Continuing Students Application Form</a><br>
            <a href="../pages/continuing_students.php">← View Student Admin Processing Page</a>
        </p>
    </div>
</body>
</html>

