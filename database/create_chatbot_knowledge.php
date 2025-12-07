<?php
/**
 * Create Chatbot Knowledge Base Table
 * Stores chatbot responses and knowledge for different departments
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$conn = getDBConnection();

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create chatbot_knowledge table
$sql = "CREATE TABLE IF NOT EXISTS `chatbot_knowledge` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `role` VARCHAR(50) NOT NULL COMMENT 'User role: admin, finance, studentservices, hod',
    `keyword` VARCHAR(255) NOT NULL COMMENT 'Keyword or topic',
    `title` VARCHAR(255) NOT NULL COMMENT 'Response title',
    `content` TEXT NOT NULL COMMENT 'Response content (HTML allowed)',
    `priority` INT(11) DEFAULT 0 COMMENT 'Priority for matching (higher = more important)',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Whether this knowledge entry is active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_role` (`role`),
    KEY `idx_keyword` (`keyword`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chatbot knowledge base for different user roles';";

if ($conn->query($sql) === TRUE) {
    echo "Table 'chatbot_knowledge' created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Create chatbot_conversations table (optional - for conversation history)
$sql2 = "CREATE TABLE IF NOT EXISTS `chatbot_conversations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) DEFAULT NULL COMMENT 'User ID if logged in',
    `user_role` VARCHAR(50) DEFAULT NULL COMMENT 'User role',
    `session_id` VARCHAR(255) DEFAULT NULL COMMENT 'Session ID for anonymous users',
    `message` TEXT NOT NULL COMMENT 'User message',
    `response` TEXT NOT NULL COMMENT 'Bot response',
    `response_source` VARCHAR(50) DEFAULT 'database' COMMENT 'Source: database, ai_api, fallback',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chatbot conversation history';";

if ($conn->query($sql2) === TRUE) {
    echo "Table 'chatbot_conversations' created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Insert some default knowledge entries
$defaultKnowledge = [
    // Admin role
    ['admin', 'workflow', 'Workflow Monitoring', '<p><strong>As Administrator, you can monitor the entire workflow:</strong></p><ul><li><strong>Workflow Monitor:</strong> View overall application statistics, status breakdown, and department-wise distribution</li><li><strong>View Applications:</strong> You can view all applications but cannot perform actions (read-only mode)</li><li><strong>Track Progress:</strong> Monitor how applications move through different departments</li></ul><p><strong>Note:</strong> Administration role is read-only.</p>', 10],
    ['admin', 'application', 'Viewing Applications', '<p><strong>To view applications:</strong></p><ol><li>Go to the Workflow Monitor from your dashboard</li><li>Click on any application to view details</li><li>You can see the complete workflow history and status</li></ol>', 5],
    
    // Finance role
    ['finance', 'invoice', 'Generate Invoice', '<p><strong>How to generate an invoice:</strong></p><ol><li>When an application is approved by HOD, you\'ll receive a notification</li><li>Go to the application details page</li><li>Click "Print Proforma Invoice" button</li><li>The invoice will automatically include fees based on the program</li></ol>', 10],
    ['finance', 'payment', 'View Payments', '<p><strong>To view payments:</strong></p><ol><li>Go to your Finance dashboard</li><li>Check the "Payments" section</li><li>View payment history and outstanding balances</li></ol>', 5],
    
    // Student Services role
    ['studentservices', 'review', 'Review Applications', '<p><strong>Application Review Process:</strong></p><ol><li>New applications appear in your dashboard when submitted</li><li>Click on an application to review details</li><li>Check all submitted documents</li><li>Forward to appropriate HOD for final decision</li></ol>', 10],
    ['studentservices', 'enroll', 'Enroll Student', '<p><strong>Student Enrollment Process:</strong></p><ol><li>Application must be accepted by HOD</li><li>All mandatory checks must be completed</li><li>Go to application details page</li><li>Click "Enroll Student" button</li><li>Student account will be automatically created</li></ol>', 10],
    
    // HOD role
    ['hod', 'review', 'Review Applications', '<p><strong>HOD Review Process:</strong></p><ol><li>Applications forwarded to you appear in your dashboard</li><li>Click on an application to review complete details</li><li>Check all submitted documents</li><li>Make your decision: Approve or Reject</li></ol>', 10],
    ['hod', 'approve', 'Approve Application', '<p><strong>To approve an application:</strong></p><ol><li>Review the application thoroughly</li><li>Click "Approve" button on application details page</li><li>Add any comments or notes</li><li>Submit - Finance and Student Admin Service will be notified</li></ol>', 10],
];

$stmt = $conn->prepare("INSERT IGNORE INTO chatbot_knowledge (role, keyword, title, content, priority) VALUES (?, ?, ?, ?, ?)");

foreach ($defaultKnowledge as $entry) {
    $stmt->bind_param("ssssi", $entry[0], $entry[1], $entry[2], $entry[3], $entry[4]);
    $stmt->execute();
}

echo "Default knowledge entries inserted.\n";

$stmt->close();
$conn->close();

echo "\nChatbot knowledge base setup completed successfully!\n";

?>

