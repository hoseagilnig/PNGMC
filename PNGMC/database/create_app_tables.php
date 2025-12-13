<?php
/**
 * Simple Script to Create Application Workflow Tables
 * This script directly creates the tables without parsing SQL
 * 
 * Access via: http://localhost/sms2/database/create_app_tables.php
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
        // Table creation SQL statements
        $tables = [
            'applications' => "CREATE TABLE IF NOT EXISTS applications (
                application_id INT AUTO_INCREMENT PRIMARY KEY,
                application_number VARCHAR(50) UNIQUE NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                middle_name VARCHAR(100),
                date_of_birth DATE,
                gender ENUM('Male', 'Female', 'Other'),
                email VARCHAR(100),
                phone VARCHAR(20),
                address TEXT,
                city VARCHAR(100),
                province VARCHAR(100),
                country VARCHAR(100) DEFAULT 'Papua New Guinea',
                grade_12_passed BOOLEAN DEFAULT FALSE,
                maths_grade VARCHAR(10),
                physics_grade VARCHAR(10),
                english_grade VARCHAR(10),
                overall_gpa DECIMAL(4,2),
                program_interest VARCHAR(200) DEFAULT 'Cadet Officers Program',
                expression_date DATE NOT NULL,
                submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('submitted', 'under_review', 'hod_review', 'accepted', 'rejected', 'correspondence_sent', 'checks_pending', 'checks_completed', 'enrolled', 'ineligible') DEFAULT 'submitted',
                assessed_by INT,
                assessment_date DATE,
                assessment_notes TEXT,
                hod_decision ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                hod_decision_by INT,
                hod_decision_date DATE,
                hod_decision_notes TEXT,
                correspondence_sent BOOLEAN DEFAULT FALSE,
                correspondence_date DATE,
                invoice_sent BOOLEAN DEFAULT FALSE,
                invoice_id INT,
                enrollment_ready BOOLEAN DEFAULT FALSE,
                enrolled BOOLEAN DEFAULT FALSE,
                enrollment_date DATE,
                student_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (assessed_by) REFERENCES users(user_id) ON DELETE SET NULL,
                FOREIGN KEY (hod_decision_by) REFERENCES users(user_id) ON DELETE SET NULL,
                FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE SET NULL,
                FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE SET NULL,
                INDEX idx_application_number (application_number),
                INDEX idx_status (status),
                INDEX idx_hod_decision (hod_decision),
                INDEX idx_submitted_at (submitted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'application_documents' => "CREATE TABLE IF NOT EXISTS application_documents (
                document_id INT AUTO_INCREMENT PRIMARY KEY,
                application_id INT NOT NULL,
                document_type ENUM('grade_12_certificate', 'transcript', 'birth_certificate', 'medical_certificate', 'police_clearance', 'passport_photo', 'other') NOT NULL,
                document_name VARCHAR(200) NOT NULL,
                file_path VARCHAR(500),
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                verified BOOLEAN DEFAULT FALSE,
                verified_by INT,
                verified_at TIMESTAMP NULL,
                notes TEXT,
                FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE,
                FOREIGN KEY (verified_by) REFERENCES users(user_id) ON DELETE SET NULL,
                INDEX idx_application_id (application_id),
                INDEX idx_document_type (document_type),
                INDEX idx_verified (verified)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'mandatory_checks' => "CREATE TABLE IF NOT EXISTS mandatory_checks (
                check_id INT AUTO_INCREMENT PRIMARY KEY,
                application_id INT NOT NULL,
                check_type ENUM('medical', 'police_clearance', 'academic_verification', 'identity_verification', 'financial_clearance', 'other') NOT NULL,
                check_name VARCHAR(200) NOT NULL,
                status ENUM('pending', 'in_progress', 'completed', 'failed') DEFAULT 'pending',
                completed_date DATE,
                verified_by INT,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE,
                FOREIGN KEY (verified_by) REFERENCES users(user_id) ON DELETE SET NULL,
                INDEX idx_application_id (application_id),
                INDEX idx_status (status),
                INDEX idx_check_type (check_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'correspondence' => "CREATE TABLE IF NOT EXISTS correspondence (
                correspondence_id INT AUTO_INCREMENT PRIMARY KEY,
                application_id INT NOT NULL,
                correspondence_type ENUM('email', 'letter', 'phone', 'invoice', 'rejection_letter', 'acceptance_letter', 'requirements_letter') NOT NULL,
                subject VARCHAR(200) NOT NULL,
                message TEXT NOT NULL,
                sent_by INT,
                sent_date DATE NOT NULL,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                attachment_path VARCHAR(500),
                status ENUM('draft', 'sent', 'delivered', 'failed') DEFAULT 'draft',
                FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE,
                FOREIGN KEY (sent_by) REFERENCES users(user_id) ON DELETE SET NULL,
                INDEX idx_application_id (application_id),
                INDEX idx_sent_date (sent_date),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'application_notes' => "CREATE TABLE IF NOT EXISTS application_notes (
                note_id INT AUTO_INCREMENT PRIMARY KEY,
                application_id INT NOT NULL,
                user_id INT NOT NULL,
                note_text TEXT NOT NULL,
                is_internal BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                INDEX idx_application_id (application_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        
        foreach ($tables as $table_name => $sql) {
            if ($conn->query($sql)) {
                $tables_created[] = $table_name;
                $success_count++;
            } else {
                // Check if table already exists
                if ($conn->errno == 1050) {
                    // Table already exists, that's okay
                    $success_count++;
                } else {
                    $error_count++;
                    $errors[] = "$table_name: " . $conn->error;
                }
            }
        }
        
        if ($success_count == count($tables)) {
            $message = "✓ Successfully created all " . count($tables_created) . " application workflow tables!";
            $message_type = "success";
        } else if (count($errors) > 0) {
            $message = "Some errors occurred:<br>" . implode('<br>', $errors);
            $message_type = "error";
        } else {
            $message = "Tables processed successfully!";
            $message_type = "success";
        }
        
        $conn->close();
    }
}

// Check current status
$conn = getDBConnection();
$tables_status = [];
$required_tables = ['applications', 'application_documents', 'mandatory_checks', 'correspondence', 'application_notes'];

if ($conn) {
    foreach ($required_tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        $tables_status[$table] = $result->num_rows > 0;
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Application Tables</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d4e89;
            margin-bottom: 20px;
        }
        .status {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            font-weight: bold;
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
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .btn {
            padding: 12px 24px;
            background: #1d4e89;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            margin-top: 20px;
        }
        .btn:hover {
            background: #163c6a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #1d4e89;
            color: white;
        }
        .check-mark {
            color: #28a745;
            font-weight: bold;
        }
        .cross-mark {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create Application Workflow Tables</h1>
        
        <?php if ($message): ?>
          <div class="status <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="info">
            <strong>Current Table Status:</strong>
            <table>
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables_status as $table => $exists): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($table); ?></td>
                            <td>
                                <?php if ($exists): ?>
                                    <span class="check-mark">✓ Exists</span>
                                <?php else: ?>
                                    <span class="cross-mark">✗ Missing</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (in_array(false, $tables_status)): ?>
            <div class="info">
                <strong>Missing tables detected!</strong><br><br>
                Click the button below to automatically create all missing application workflow tables.
            </div>

            <form method="POST">
                <button type="submit" name="create_tables" class="btn">Create Missing Tables</button>
            </form>
        <?php else: ?>
            <div class="status success">
                ✓ All application workflow tables exist! The system is ready to use.
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <a href="../apply.php" class="btn" style="text-decoration: none; display: inline-block; margin-right: 10px;">← Back to Application Form</a>
            <a href="../pages/login.php" class="btn" style="text-decoration: none; display: inline-block;">Go to Login</a>
        </div>
    </div>
</body>
</html>

