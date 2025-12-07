<?php
/**
 * Test Requirements API
 * Use this page to test if the requirements API is working
 */

session_start();
require_once __DIR__ . '/includes/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    die('Please login first: <a href="login.php">Login</a>');
}

$conn = getDBConnection();
if (!$conn) {
    die('Database connection failed');
}

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'continuing_student_requirements'");
$table_exists = $table_check->num_rows > 0;

// Get a test application ID
$test_app = $conn->query("SELECT application_id FROM applications WHERE (application_type = 'continuing_student_solas' OR application_type = 'continuing_student_next_level') LIMIT 1");
$test_app_id = $test_app && $test_app->num_rows > 0 ? $test_app->fetch_assoc()['application_id'] : 0;

// Get requirements count for test app
$req_count = 0;
if ($test_app_id > 0 && $table_exists) {
    $req_result = $conn->query("SELECT COUNT(*) as count FROM continuing_student_requirements WHERE application_id = $test_app_id");
    if ($req_result) {
        $req_count = $req_result->fetch_assoc()['count'];
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Requirements API</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Requirements API Test</h1>
    
    <h2>System Checks</h2>
    <ul>
        <li>Session Status: <span class="<?php echo isset($_SESSION['loggedin']) ? 'success' : 'error'; ?>">
            <?php echo isset($_SESSION['loggedin']) ? 'Logged In' : 'Not Logged In'; ?>
        </span></li>
        <li>User Role: <span class="info"><?php echo $_SESSION['role'] ?? 'N/A'; ?></span></li>
        <li>Table Exists: <span class="<?php echo $table_exists ? 'success' : 'error'; ?>">
            <?php echo $table_exists ? 'Yes' : 'No'; ?>
        </span></li>
        <li>Test Application ID: <span class="info"><?php echo $test_app_id ?: 'None found'; ?></span></li>
        <li>Requirements for Test App: <span class="info"><?php echo $req_count; ?></span></li>
    </ul>
    
    <h2>Test API Call</h2>
    <?php if ($test_app_id > 0): ?>
        <p>Testing with Application ID: <strong><?php echo $test_app_id; ?></strong></p>
        <button onclick="testAPI(<?php echo $test_app_id; ?>)">Test API</button>
        <div id="result" style="margin-top: 20px;"></div>
    <?php else: ?>
        <p class="error">No continuing student applications found to test with.</p>
    <?php endif; ?>
    
    <h2>Manual Test</h2>
    <p>Enter an Application ID to test:</p>
    <input type="number" id="appId" placeholder="Application ID" value="<?php echo $test_app_id; ?>">
    <button onclick="testAPI(document.getElementById('appId').value)">Test</button>
    
    <h2>File Check</h2>
    <p>API File Path: <code>pages/get_requirements.php</code></p>
    <p>File Exists: <span class="<?php echo file_exists(__DIR__ . '/get_requirements.php') ? 'success' : 'error'; ?>">
        <?php echo file_exists(__DIR__ . '/get_requirements.php') ? 'Yes' : 'No'; ?>
    </span></p>
    
    <script>
        function testAPI(appId) {
            if (!appId || appId <= 0) {
                alert('Please enter a valid Application ID');
                return;
            }
            
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p>Loading...</p>';
            
            fetch('get_requirements.php?application_id=' + appId, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                return response.text().then(text => {
                    resultDiv.innerHTML = '<h3>Response Status: ' + response.status + '</h3>';
                    resultDiv.innerHTML += '<h3>Raw Response:</h3><pre>' + escapeHtml(text) + '</pre>';
                    
                    try {
                        const data = JSON.parse(text);
                        resultDiv.innerHTML += '<h3>Parsed JSON:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                        
                        if (data.success) {
                            resultDiv.innerHTML += '<p class="success">✓ API call successful!</p>';
                            resultDiv.innerHTML += '<p>Found ' + (data.requirements ? data.requirements.length : 0) + ' requirements</p>';
                        } else {
                            resultDiv.innerHTML += '<p class="error">✗ API returned error: ' + (data.error || 'Unknown error') + '</p>';
                        }
                    } catch (e) {
                        resultDiv.innerHTML += '<p class="error">✗ Failed to parse JSON: ' + e.message + '</p>';
                    }
                });
            })
            .catch(error => {
                resultDiv.innerHTML = '<p class="error">✗ Error: ' + error.message + '</p>';
                console.error('Error:', error);
            });
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>

