<?php
session_start();
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['finance', 'studentservices', 'admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/db_config.php';
require_once 'includes/workflow_helper.php';

$checks = [];
$conn = getDBConnection();

// Check 1: Database Connection
$checks['database_connection'] = [
    'name' => 'Database Connection',
    'status' => $conn ? 'success' : 'error',
    'message' => $conn ? 'Database connection successful' : 'Database connection failed'
];

// Check 2: finance_to_sas_transfers table exists
if ($conn) {
    $table_exists = $conn->query("SHOW TABLES LIKE 'finance_to_sas_transfers'")->num_rows > 0;
    $checks['transfer_table'] = [
        'name' => 'Finance to SAS Transfers Table',
        'status' => $table_exists ? 'success' : 'error',
        'message' => $table_exists ? 'Table exists' : 'Table does not exist. Run: database/create_finance_sas_workflow_tables.php'
    ];
    
    // Check 3: workflow_notifications table exists
    $notif_table_exists = $conn->query("SHOW TABLES LIKE 'workflow_notifications'")->num_rows > 0;
    $checks['notification_table'] = [
        'name' => 'Workflow Notifications Table',
        'status' => $notif_table_exists ? 'success' : 'error',
        'message' => $notif_table_exists ? 'Table exists' : 'Table does not exist. Run: database/create_workflow_tables.php'
    ];
    
    // Check 4: Sample data retrieval
    if ($table_exists) {
        $sample_result = $conn->query("SELECT COUNT(*) as count FROM finance_to_sas_transfers");
        $sample_count = $sample_result ? $sample_result->fetch_assoc()['count'] : 0;
        $checks['sample_data'] = [
            'name' => 'Sample Data Retrieval',
            'status' => 'success',
            'message' => "Found {$sample_count} transfer record(s) in database"
        ];
    }
    
    // Check 5: Test notification creation
    if ($notif_table_exists && $table_exists) {
        $test_notification = createWorkflowNotification(
            0,
            'finance',
            'studentservices',
            '🧪 Test Notification - Finance to SAS',
            'This is a test notification to verify the workflow system is working.',
            'information',
            'sas_received_data.php',
            $_SESSION['user_id']
        );
        $checks['notification_creation'] = [
            'name' => 'Notification Creation Test',
            'status' => $test_notification ? 'success' : 'error',
            'message' => $test_notification ? 'Test notification created successfully' : 'Failed to create test notification'
        ];
    }
    
    // Check 6: Get recent transfers
    if ($table_exists) {
        $recent_result = $conn->query("SELECT * FROM finance_to_sas_transfers ORDER BY transferred_at DESC LIMIT 5");
        $recent_transfers = $recent_result ? $recent_result->fetch_all(MYSQLI_ASSOC) : [];
        $checks['recent_transfers'] = [
            'name' => 'Recent Transfers',
            'status' => 'info',
            'message' => count($recent_transfers) . ' recent transfer(s) found',
            'data' => $recent_transfers
        ];
    }
    
    // Check 7: Get unread notifications for studentservices
    if ($notif_table_exists) {
        $notif_count = getNotificationCount('studentservices');
        $checks['sas_notifications'] = [
            'name' => 'SAS Unread Notifications',
            'status' => 'info',
            'message' => "{$notif_count} unread notification(s) for Student Admin Services"
        ];
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Finance to SAS Workflow Test</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      max-width: 1000px;
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
      margin-bottom: 30px;
    }
    .check-item {
      padding: 15px;
      margin-bottom: 15px;
      border-radius: 5px;
      border-left: 4px solid #ddd;
    }
    .check-item.success {
      background: #d4edda;
      border-left-color: #28a745;
    }
    .check-item.error {
      background: #f8d7da;
      border-left-color: #dc3545;
    }
    .check-item.info {
      background: #d1ecf1;
      border-left-color: #17a2b8;
    }
    .check-item h3 {
      margin: 0 0 5px 0;
      color: #333;
    }
    .check-item p {
      margin: 5px 0 0 0;
      color: #666;
    }
    .status-icon {
      font-size: 1.5rem;
      margin-right: 10px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    table th {
      background: #1d4e89;
      color: white;
      padding: 10px;
      text-align: left;
    }
    table td {
      padding: 10px;
      border-bottom: 1px solid #ddd;
    }
    .summary {
      background: #e7f3ff;
      padding: 20px;
      border-radius: 5px;
      margin-bottom: 30px;
    }
    .summary h2 {
      margin-top: 0;
      color: #1d4e89;
    }
    .btn {
      display: inline-block;
      padding: 10px 20px;
      background: #1d4e89;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      margin-top: 20px;
    }
    .btn:hover {
      background: #163c6a;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>🧪 Finance to SAS Workflow Test</h1>
    
    <div class="summary">
      <h2>System Status Summary</h2>
      <?php
      $success_count = 0;
      $error_count = 0;
      $info_count = 0;
      foreach ($checks as $check) {
          if ($check['status'] === 'success') $success_count++;
          elseif ($check['status'] === 'error') $error_count++;
          else $info_count++;
      }
      ?>
      <p><strong>✅ Success:</strong> <?php echo $success_count; ?> | 
         <strong>❌ Errors:</strong> <?php echo $error_count; ?> | 
         <strong>ℹ️ Info:</strong> <?php echo $info_count; ?></p>
      
      <?php if ($error_count === 0): ?>
        <p style="color: #28a745; font-weight: bold;">✓ All critical checks passed! The workflow system should be working.</p>
      <?php else: ?>
        <p style="color: #dc3545; font-weight: bold;">⚠️ Some checks failed. Please review the details below.</p>
      <?php endif; ?>
    </div>
    
    <h2>Detailed Checks</h2>
    <?php foreach ($checks as $key => $check): ?>
      <div class="check-item <?php echo $check['status']; ?>">
        <h3>
          <span class="status-icon">
            <?php 
            if ($check['status'] === 'success') echo '✅';
            elseif ($check['status'] === 'error') echo '❌';
            else echo 'ℹ️';
            ?>
          </span>
          <?php echo htmlspecialchars($check['name']); ?>
        </h3>
        <p><?php echo htmlspecialchars($check['message']); ?></p>
        
        <?php if (isset($check['data']) && !empty($check['data'])): ?>
          <table>
            <thead>
              <tr>
                <th>Receipt Number</th>
                <th>Payment Date</th>
                <th>Amount</th>
                <th>Transferred At</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($check['data'] as $transfer): ?>
                <tr>
                  <td><?php echo htmlspecialchars($transfer['receipt_number']); ?></td>
                  <td><?php echo date('M d, Y', strtotime($transfer['payment_date'])); ?></td>
                  <td>PGK <?php echo number_format($transfer['amount'], 2); ?></td>
                  <td><?php echo date('M d, Y H:i', strtotime($transfer['transferred_at'])); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ddd;">
      <h3>Next Steps</h3>
      <ul>
        <li><strong>If all checks pass:</strong> The workflow is ready to use. Finance can transfer data, and SAS will receive notifications.</li>
        <li><strong>If tables are missing:</strong> Run the database setup scripts:
          <ul>
            <li><a href="../database/create_workflow_tables.php" target="_blank">Create Workflow Tables</a></li>
            <li><a href="../database/create_finance_sas_workflow_tables.php" target="_blank">Create Finance-SAS Workflow Tables</a></li>
          </ul>
        </li>
        <li><strong>To test the workflow:</strong>
          <ul>
            <li>Login as Finance user → Go to "Finance to SAS" → Transfer payment data</li>
            <li>Login as Student Services user → Check dashboard for notification bubble → View "Received from Finance"</li>
          </ul>
        </li>
      </ul>
      
      <a href="<?php echo $_SESSION['role'] === 'finance' ? 'finance_dashboard.php' : 'student_service_dashboard.php'; ?>" class="btn">← Back to Dashboard</a>
      <a href="finance_to_sas.php" class="btn" style="background: #28a745;">Test Transfer (Finance)</a>
      <a href="sas_received_data.php" class="btn" style="background: #17a2b8;">View Received Data (SAS)</a>
    </div>
  </div>
</body>
</html>

