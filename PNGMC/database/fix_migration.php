<?php
/**
 * Auto-Run Migration Script
 * This script automatically runs the migration when accessed
 * Access via: http://localhost/sms2/database/fix_migration.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Auto Run</title>
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
            line-height: 1.6;
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
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 0.9rem;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #1d4e89;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: bold;
        }
        .btn:hover {
            background: #163c6a;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Migration - Auto Run</h1>
        
        <?php
        $conn = getDBConnection();
        
        if (!$conn) {
            echo '<div class="message error">ERROR: Database connection failed! Please check your database configuration.</div>';
        } else {
            echo '<div class="message info">Running database migration...</div>';
            echo '<pre>';
            
            $errors = [];
            $success = [];
            
            // Add application_type column
            $check = $conn->query("SHOW COLUMNS FROM applications LIKE 'application_type'");
            if ($check->num_rows === 0) {
                $sql = "ALTER TABLE applications ADD COLUMN application_type ENUM('new_student', 'continuing_student_solas', 'continuing_student_next_level') DEFAULT 'new_student' AFTER program_interest";
                if ($conn->query($sql)) {
                    $success[] = "Added column: application_type";
                    echo "✓ Added column: application_type\n";
                } else {
                    $errors[] = "Error adding application_type: " . $conn->error;
                    echo "✗ Error: " . $conn->error . "\n";
                }
            } else {
                echo "✓ Column application_type already exists\n";
            }
            
            // Add course_type column
            $check = $conn->query("SHOW COLUMNS FROM applications LIKE 'course_type'");
            if ($check->num_rows === 0) {
                $sql = "ALTER TABLE applications ADD COLUMN course_type ENUM('Nautical', 'Engineering') NULL AFTER application_type";
                if ($conn->query($sql)) {
                    $success[] = "Added column: course_type";
                    echo "✓ Added column: course_type\n";
                } else {
                    $errors[] = "Error adding course_type: " . $conn->error;
                    echo "✗ Error: " . $conn->error . "\n";
                }
            } else {
                echo "✓ Column course_type already exists\n";
            }
            
            // Add education_level column
            $check = $conn->query("SHOW COLUMNS FROM applications LIKE 'education_level'");
            if ($check->num_rows === 0) {
                $sql = "ALTER TABLE applications ADD COLUMN education_level ENUM('Grade 10', 'Grade 12') NULL AFTER province";
                if ($conn->query($sql)) {
                    $success[] = "Added column: education_level";
                    echo "✓ Added column: education_level\n";
                } else {
                    $errors[] = "Error adding education_level: " . $conn->error;
                    echo "✗ Error: " . $conn->error . "\n";
                }
            } else {
                echo "✓ Column education_level already exists\n";
            }
            
            // Add other required columns
            $other_columns = [
                'nmsa_approval_letter_path' => "VARCHAR(500) NULL",
                'sea_service_record_path' => "VARCHAR(500) NULL",
                'coc_number' => "VARCHAR(100) NULL",
                'coc_expiry_date' => "DATE NULL",
                'previous_student_id' => "INT NULL",
                'signature_path' => "VARCHAR(500) NULL",
                'requirements_met' => "BOOLEAN DEFAULT FALSE",
                'requirements_notes' => "TEXT NULL",
                'shortfalls_identified' => "TEXT NULL",
                'shortfalls_addressed' => "BOOLEAN DEFAULT FALSE"
            ];
            
            foreach ($other_columns as $col_name => $col_def) {
                $check = $conn->query("SHOW COLUMNS FROM applications LIKE '$col_name'");
                if ($check->num_rows === 0) {
                    $sql = "ALTER TABLE applications ADD COLUMN $col_name $col_def";
                    if ($conn->query($sql)) {
                        $success[] = "Added column: $col_name";
                        echo "✓ Added column: $col_name\n";
                    } else {
                        $errors[] = "Error adding $col_name: " . $conn->error;
                        echo "✗ Error adding $col_name: " . $conn->error . "\n";
                    }
                } else {
                    echo "→ Column $col_name already exists\n";
                }
            }
            
            // Add indexes
            $indexes = [
                'idx_application_type' => "CREATE INDEX idx_application_type ON applications(application_type)",
                'idx_course_type' => "CREATE INDEX idx_course_type ON applications(course_type)"
            ];
            
            foreach ($indexes as $idx_name => $sql) {
                $check = $conn->query("SHOW INDEX FROM applications WHERE Key_name = '$idx_name'");
                if ($check->num_rows === 0) {
                    if ($conn->query($sql)) {
                        $success[] = "Added index: $idx_name";
                        echo "✓ Added index: $idx_name\n";
                    } else {
                        if (strpos($conn->error, 'Duplicate key name') === false) {
                            echo "→ Index $idx_name: " . $conn->error . "\n";
                        }
                    }
                } else {
                    echo "→ Index $idx_name already exists\n";
                }
            }
            
            $conn->close();
            
            echo "\n=== Migration Complete ===\n";
            echo '</pre>';
            
            if (empty($errors)) {
                echo '<div class="message success">';
                echo '<strong>SUCCESS!</strong> All database columns have been added successfully!<br><br>';
                echo 'You can now submit all forms without errors.';
                echo '</div>';
            } else {
                echo '<div class="message error">';
                echo '<strong>WARNING:</strong> Some errors occurred. Please check the output above for details.';
                echo '</div>';
            }
        }
        ?>
        
        <p>
            <a href="../enroll_engine_rating1.php" class="btn">← Back to Enrollment Forms</a>
        </p>
    </div>
</body>
</html>

