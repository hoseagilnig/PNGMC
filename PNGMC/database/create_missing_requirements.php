<?php
/**
 * Create Missing Requirements for Existing Applications
 * This script creates requirements for continuing student applications that don't have any
 * 
 * Access via: http://localhost/sms2/database/create_missing_requirements.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$message = '';
$message_type = '';
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_requirements'])) {
    $conn = getDBConnection();
    
    if (!$conn) {
        $message = "Database connection failed!";
        $message_type = "error";
    } else {
        // Check if table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'continuing_student_requirements'");
        if ($table_check->num_rows === 0) {
            $message = "The continuing_student_requirements table does not exist. Please run the migration script first: <a href='add_continuing_students_fields.php'>Add Continuing Students Fields</a>";
            $message_type = "error";
        } else {
            // Find all continuing student applications without requirements
            $sql = "SELECT a.application_id, a.application_type 
                    FROM applications a 
                    WHERE (a.application_type = 'continuing_student_solas' OR a.application_type = 'continuing_student_next_level')
                    AND NOT EXISTS (
                        SELECT 1 FROM continuing_student_requirements csr 
                        WHERE csr.application_id = a.application_id
                    )";
            
            $result = $conn->query($sql);
            $applications = [];
            if ($result) {
                $applications = $result->fetch_all(MYSQLI_ASSOC);
            }
            
            if (empty($applications)) {
                $message = "All continuing student applications already have requirements!";
                $message_type = "success";
            } else {
                // Create requirements for each application
                $req_sql = "INSERT INTO continuing_student_requirements (application_id, requirement_type, requirement_name, status) VALUES (?, ?, ?, 'pending')";
                $req_stmt = $conn->prepare($req_sql);
                
                $created = 0;
                $errors = 0;
                
                foreach ($applications as $app) {
                    $application_id = $app['application_id'];
                    
                    // Default requirements for all continuing students
                    $requirements = [
                        ['nmsa_approval', 'NMSA Approval Letter'],
                        ['sea_service_record', 'Record of Sea Service'],
                        ['expression_of_interest', 'Expression of Interest Application']
                    ];
                    
                    // Add COC requirement if applicable
                    if ($app['application_type'] === 'continuing_student_next_level') {
                        $requirements[] = ['coc_validity', 'Certificate of Competency (COC) Validity'];
                    }
                    
                    foreach ($requirements as $req) {
                        $req_stmt->bind_param('iss', $application_id, $req[0], $req[1]);
                        if ($req_stmt->execute()) {
                            $created++;
                        } else {
                            $errors++;
                            $results[] = "Error creating requirement for Application #$application_id: " . $req_stmt->error;
                        }
                    }
                }
                
                $req_stmt->close();
                
                if ($errors === 0) {
                    $message = "Successfully created $created requirements for " . count($applications) . " application(s)!";
                    $message_type = "success";
                } else {
                    $message = "Created $created requirements, but encountered $errors errors. See details below.";
                    $message_type = "warning";
                }
            }
            
            $conn->close();
        }
    }
} else {
    // Check current status
    $conn = getDBConnection();
    if ($conn) {
        $table_check = $conn->query("SHOW TABLES LIKE 'continuing_student_requirements'");
        $table_exists = $table_check->num_rows > 0;
        
        if ($table_exists) {
            // Count applications without requirements
            $sql = "SELECT COUNT(*) as count 
                    FROM applications a 
                    WHERE (a.application_type = 'continuing_student_solas' OR a.application_type = 'continuing_student_next_level')
                    AND NOT EXISTS (
                        SELECT 1 FROM continuing_student_requirements csr 
                        WHERE csr.application_id = a.application_id
                    )";
            $result = $conn->query($sql);
            $missing_count = $result ? $result->fetch_assoc()['count'] : 0;
            
            // Count total continuing student applications
            $total_sql = "SELECT COUNT(*) as count 
                         FROM applications 
                         WHERE (application_type = 'continuing_student_solas' OR application_type = 'continuing_student_next_level')";
            $total_result = $conn->query($total_sql);
            $total_count = $total_result ? $total_result->fetch_assoc()['count'] : 0;
            
            $results[] = "Table exists: Yes";
            $results[] = "Total continuing student applications: $total_count";
            $results[] = "Applications missing requirements: $missing_count";
        } else {
            $results[] = "Table exists: No - Please run migration first";
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
    <title>Create Missing Requirements</title>
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
        .info-list {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info-list li {
            margin: 5px 0;
            padding: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create Missing Requirements</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($results)): ?>
            <div class="info-list">
                <strong>Status:</strong>
                <ul>
                    <?php foreach ($results as $result): ?>
                        <li><?php echo htmlspecialchars($result); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <p>This script will create default requirements for continuing student applications that don't have any requirements yet.</p>
        
        <p><strong>Default Requirements Created:</strong></p>
        <ul>
            <li>NMSA Approval Letter</li>
            <li>Record of Sea Service</li>
            <li>Expression of Interest Application</li>
            <li>Certificate of Competency (COC) Validity (for Next Level applications only)</li>
        </ul>
        
        <form method="POST">
            <button type="submit" name="create_requirements">Create Missing Requirements</button>
        </form>
        
        <p style="margin-top: 30px;">
            <a href="add_continuing_students_fields.php">← Run Migration First</a><br>
            <a href="../pages/continuing_students.php">← View Continuing Students Page</a>
        </p>
    </div>
</body>
</html>

