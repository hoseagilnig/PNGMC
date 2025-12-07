<?php
/**
 * Chatbot API Test Page
 * Use this page to test and diagnose chatbot API issues
 */

session_start();
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/chatbot_config.php';

// Set a test role if not logged in
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'admin';
    $_SESSION['user_id'] = 1;
    $_SESSION['name'] = 'Test User';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Chatbot API Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1, h2 {
            color: #1d4e89;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            border: 1px solid #dee2e6;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        button {
            background: #1d4e89;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #163c6a;
        }
        input[type="text"] {
            width: 300px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .response-box {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <h1>Chatbot API Test & Diagnostics</h1>
    
    <div class="container">
        <h2>Configuration Status</h2>
        
        <div class="test-section">
            <h3>Chatbot Mode</h3>
            <div class="status <?php echo CHATBOT_MODE === 'ai_api' ? 'info' : 'warning'; ?>">
                <strong>Current Mode:</strong> <?php echo CHATBOT_MODE; ?>
            </div>
        </div>
        
        <div class="test-section">
            <h3>AI API Provider</h3>
            <div class="status <?php echo CHATBOT_MODE === 'ai_api' ? 'info' : 'warning'; ?>">
                <strong>Provider:</strong> <?php echo AI_API_PROVIDER; ?>
            </div>
        </div>
        
        <div class="test-section">
            <h3>API Key Status</h3>
            <?php
            $api_key_status = 'Not configured';
            $api_key_class = 'error';
            
            switch (AI_API_PROVIDER) {
                case 'openai':
                    if (!empty(OPENAI_API_KEY)) {
                        $api_key_status = 'Configured (' . substr(OPENAI_API_KEY, 0, 10) . '...)';
                        $api_key_class = 'success';
                    }
                    break;
                case 'google':
                    if (!empty(GEMINI_API_KEY)) {
                        $api_key_status = 'Configured (' . substr(GEMINI_API_KEY, 0, 10) . '...)';
                        $api_key_class = 'success';
                    }
                    break;
                case 'anthropic':
                    if (!empty(ANTHROPIC_API_KEY)) {
                        $api_key_status = 'Configured (' . substr(ANTHROPIC_API_KEY, 0, 10) . '...)';
                        $api_key_class = 'success';
                    }
                    break;
                case 'custom':
                    if (!empty(CUSTOM_API_URL) && !empty(CUSTOM_API_KEY)) {
                        $api_key_status = 'Configured';
                        $api_key_class = 'success';
                    }
                    break;
            }
            ?>
            <div class="status <?php echo $api_key_class; ?>">
                <strong>API Key:</strong> <?php echo $api_key_status; ?>
            </div>
        </div>
        
        <div class="test-section">
            <h3>PHP Extensions</h3>
            <?php
            $curl_available = function_exists('curl_init');
            $json_available = function_exists('json_encode');
            ?>
            <div class="status <?php echo $curl_available ? 'success' : 'error'; ?>">
                <strong>cURL:</strong> <?php echo $curl_available ? 'Available' : 'NOT AVAILABLE - Required for AI API'; ?>
            </div>
            <div class="status <?php echo $json_available ? 'success' : 'error'; ?>">
                <strong>JSON:</strong> <?php echo $json_available ? 'Available' : 'NOT AVAILABLE'; ?>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Database Connection</h3>
            <?php
            $conn = getDBConnection();
            if ($conn) {
                // Check if tables exist
                $tables_exist = true;
                $result = $conn->query("SHOW TABLES LIKE 'chatbot_knowledge'");
                if ($result->num_rows === 0) {
                    $tables_exist = false;
                }
                $conn->close();
                
                if ($tables_exist) {
                    echo '<div class="status success"><strong>Database:</strong> Connected - Tables exist</div>';
                } else {
                    echo '<div class="status warning"><strong>Database:</strong> Connected - Tables missing (run create_chatbot_knowledge.php)</div>';
                }
            } else {
                echo '<div class="status error"><strong>Database:</strong> Connection failed</div>';
            }
            ?>
        </div>
    </div>
    
    <div class="container">
        <h2>Test Chatbot API</h2>
        
        <div class="test-section">
            <input type="text" id="test-message" placeholder="Enter test message..." value="How do I generate an invoice?">
            <button onclick="testChatbot()">Test API</button>
            <button onclick="testWithDebug()">Test with Debug</button>
        </div>
        
        <div id="test-response" class="response-box" style="display:none;">
            <h3>Response:</h3>
            <div id="response-content"></div>
        </div>
    </div>
    
    <div class="container">
        <h2>Check PHP Error Log</h2>
        <p>If the AI API is not working, check your PHP error log for detailed error messages.</p>
        <p><strong>Common locations:</strong></p>
        <ul>
            <li>XAMPP: <code>C:\xampp\php\logs\php_error_log</code></li>
            <li>Apache error log: <code>C:\xampp\apache\logs\error.log</code></li>
        </ul>
        <p>Errors will be logged with prefix: <code>Chatbot</code></p>
    </div>
    
    <script>
        function testChatbot() {
            const message = document.getElementById('test-message').value;
            if (!message) {
                alert('Please enter a message');
                return;
            }
            
            const responseBox = document.getElementById('test-response');
            const responseContent = document.getElementById('response-content');
            responseBox.style.display = 'block';
            responseContent.innerHTML = '<p>Testing...</p>';
            
            fetch('api/chatbot_query.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: message })
            })
            .then(response => response.json())
            .then(data => {
                let html = '<div class="status ' + (data.success ? 'success' : 'error') + '">';
                html += '<strong>Success:</strong> ' + (data.success ? 'Yes' : 'No') + '<br>';
                html += '<strong>Source:</strong> ' + (data.source || 'unknown') + '<br>';
                if (data.title) {
                    html += '<strong>Title:</strong> ' + data.title + '<br>';
                }
                if (data.content) {
                    html += '<strong>Content:</strong> ' + data.content;
                }
                if (data.error) {
                    html += '<strong>Error:</strong> ' + data.error;
                }
                if (data.debug) {
                    html += '<br><strong>Debug:</strong> ' + data.debug;
                }
                html += '</div>';
                html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                responseContent.innerHTML = html;
            })
            .catch(error => {
                responseContent.innerHTML = '<div class="status error">Error: ' + error.message + '</div>';
            });
        }
        
        function testWithDebug() {
            // Enable debug mode temporarily
            const message = document.getElementById('test-message').value;
            if (!message) {
                alert('Please enter a message');
                return;
            }
            
            const responseBox = document.getElementById('test-response');
            const responseContent = document.getElementById('response-content');
            responseBox.style.display = 'block';
            responseContent.innerHTML = '<p>Testing with debug mode...</p>';
            
            fetch('api/chatbot_query.php?debug=1', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: message })
            })
            .then(response => response.json())
            .then(data => {
                let html = '<div class="status ' + (data.success ? 'success' : 'error') + '">';
                html += '<strong>Success:</strong> ' + (data.success ? 'Yes' : 'No') + '<br>';
                html += '<strong>Source:</strong> ' + (data.source || 'unknown') + '<br>';
                if (data.title) {
                    html += '<strong>Title:</strong> ' + data.title + '<br>';
                }
                if (data.content) {
                    html += '<strong>Content:</strong> ' + data.content;
                }
                if (data.error) {
                    html += '<br><strong>Error:</strong> ' + data.error;
                }
                if (data.debug) {
                    html += '<br><strong>Debug Info:</strong> ' + data.debug;
                }
                html += '</div>';
                html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                responseContent.innerHTML = html;
            })
            .catch(error => {
                responseContent.innerHTML = '<div class="status error">Error: ' + error.message + '</div>';
            });
        }
    </script>
</body>
</html>

