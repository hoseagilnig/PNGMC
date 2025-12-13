<?php
/**
 * Remove Test Student Account
 * Removes the test.student account from the database
 * 
 * Access via: http://localhost/sms2/database/remove_test_student.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$message = '';
$message_type = '';
$deleted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_account'])) {
    $conn = getDBConnection();
    
    if (!$conn) {
        $message = "Database connection failed!";
        $message_type = "error";
    } else {
        // Find test student by email
        $email = 'test.student@pngmc.ac.pg';
        $result = $conn->query("SELECT * FROM students WHERE email = '$email' OR student_number LIKE 'TEST-%'");
        
        if ($result && $result->num_rows > 0) {
            $students = [];
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
            
            $deleted_count = 0;
            foreach ($students as $student) {
                $student_id = $student['student_id'];
                
                // Delete student account
                $conn->query("DELETE FROM student_accounts WHERE student_id = $student_id");
                
                // Delete course history if table exists
                $table_check = $conn->query("SHOW TABLES LIKE 'student_course_history'");
                if ($table_check->num_rows > 0) {
                    $conn->query("DELETE FROM student_course_history WHERE student_id = $student_id");
                }
                
                // Delete student notifications if table exists
                $table_check = $conn->query("SHOW TABLES LIKE 'student_notifications'");
                if ($table_check->num_rows > 0) {
                    $conn->query("DELETE FROM student_notifications WHERE student_id = $student_id");
                }
                
                // Delete student
                $conn->query("DELETE FROM students WHERE student_id = $student_id");
                $deleted_count++;
            }
            
            $message = "Successfully removed $deleted_count test student account(s) from the database!";
            $message_type = "success";
            $deleted = true;
        } else {
            $message = "No test student account found with email 'test.student@pngmc.ac.pg' or student number starting with 'TEST-'.";
            $message_type = "info";
        }
        
        $conn->close();
    }
} else {
    // Just check if account exists
    $conn = getDBConnection();
    if ($conn) {
        $email = 'test.student@pngmc.ac.pg';
        $result = $conn->query("SELECT s.*, sa.username FROM students s LEFT JOIN student_accounts sa ON s.student_id = sa.student_id WHERE s.email = '$email' OR s.student_number LIKE 'TEST-%'");
        
        if ($result && $result->num_rows > 0) {
            $found_accounts = [];
            while ($row = $result->fetch_assoc()) {
                $found_accounts[] = $row;
            }
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Remove Test Student Account</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 800px; margin: 0 auto; background: #f5f5f5; }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #1d4e89; margin-bottom: 20px; }
        .message { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        .btn { padding: 12px 24px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; font-weight: 600; }
        .btn:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗑️ Remove Test Student Account</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($found_accounts) && count($found_accounts) > 0): ?>
            <div class="message warning">
                <strong>Found <?php echo count($found_accounts); ?> test account(s):</strong>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Number</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Username</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($found_accounts as $account): ?>
                        <tr>
                            <td><?php echo $account['student_id']; ?></td>
                            <td><?php echo htmlspecialchars($account['student_number']); ?></td>
                            <td><?php echo htmlspecialchars($account['first_name'] . ' ' . $account['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($account['email'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($account['username'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <form method="POST" style="margin-top: 20px;">
                <button type="submit" name="remove_account" class="btn" onclick="return confirm('Are you sure you want to delete all test student accounts? This action cannot be undone!');">
                    🗑️ Remove All Test Accounts
                </button>
            </form>
        <?php elseif ($deleted): ?>
            <div style="margin-top: 20px;">
                <a href="remove_test_student.php" class="btn btn-secondary">Check Again</a>
            </div>
        <?php else: ?>
            <div class="message info">
                No test student accounts found in the database.
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <h3>What this script does:</h3>
            <ul style="line-height: 1.8;">
                <li>Finds all students with email "test.student@pngmc.ac.pg" or student number starting with "TEST-"</li>
                <li>Deletes the student account from <code>student_accounts</code> table</li>
                <li>Deletes course history from <code>student_course_history</code> table</li>
                <li>Deletes notifications from <code>student_notifications</code> table</li>
                <li>Deletes the student record from <code>students</code> table</li>
            </ul>
            <p style="margin-top: 15px; color: #666; font-size: 0.9rem;">
                <strong>Note:</strong> This action is permanent and cannot be undone. Make sure you want to delete these accounts before proceeding.
            </p>
        </div>
    </div>
</body>
</html>

