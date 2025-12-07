<?php
/**
 * Direct Gemini API Test
 * Tests the Gemini API connection directly
 */

require_once __DIR__ . '/includes/chatbot_config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gemini API Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Google Gemini API Test</h1>
    
    <?php
    echo '<div class="info">';
    echo '<strong>Configuration:</strong><br>';
    echo 'Mode: ' . CHATBOT_MODE . '<br>';
    echo 'Provider: ' . AI_API_PROVIDER . '<br>';
    echo 'Model: ' . GEMINI_MODEL . '<br>';
    echo 'API Key: ' . (empty(GEMINI_API_KEY) ? '<span style="color:red;">NOT SET</span>' : substr(GEMINI_API_KEY, 0, 10) . '...') . '<br>';
    echo 'cURL Available: ' . (function_exists('curl_init') ? 'Yes' : '<span style="color:red;">NO</span>');
    echo '</div>';
    
    if (empty(GEMINI_API_KEY)) {
        echo '<div class="error">ERROR: Gemini API key is not configured!</div>';
        exit;
    }
    
    if (!function_exists('curl_init')) {
        echo '<div class="error">ERROR: cURL is not available. Please enable the curl extension in php.ini</div>';
        exit;
    }
    
    // Test message
    $test_message = "What is artificial intelligence?";
    $context = CHATBOT_SYSTEM_CONTEXT;
    $full_prompt = $context . "\n\nUser: " . $test_message . "\n\nAssistant:";
    
    // Use v1beta API (more compatible)
    $api_version = defined('GEMINI_API_VERSION') ? GEMINI_API_VERSION : 'v1beta';
    $api_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent';
    $url = $api_endpoint . '?key=' . GEMINI_API_KEY;
    
    echo '<div class="info">';
    echo '<strong>Test Message:</strong> ' . htmlspecialchars($test_message) . '<br>';
    echo '<strong>API Version:</strong> ' . $api_version . '<br>';
    echo '<strong>API Endpoint:</strong> ' . $api_endpoint;
    echo '</div>';
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $full_prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 1024,
        ]
    ];
    
    echo '<h2>Making API Request...</h2>';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo '<h2>Response:</h2>';
    
    if ($curlError) {
        echo '<div class="error">';
        echo '<strong>cURL Error:</strong><br>';
        echo htmlspecialchars($curlError);
        echo '</div>';
    } else {
        echo '<div class="info">';
        echo '<strong>HTTP Status Code:</strong> ' . $httpCode . '<br>';
        echo '</div>';
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo '<div class="error">';
                echo '<strong>JSON Parse Error:</strong> ' . json_last_error_msg() . '<br>';
                echo '<strong>Raw Response:</strong><br>';
                echo '<pre>' . htmlspecialchars(substr($response, 0, 1000)) . '</pre>';
                echo '</div>';
            } else {
                echo '<div class="success">';
                echo '<strong>✅ API Call Successful!</strong><br><br>';
                
                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    $ai_response = $result['candidates'][0]['content']['parts'][0]['text'];
                    echo '<strong>AI Response:</strong><br>';
                    echo '<div style="background: white; padding: 15px; border-radius: 5px; margin-top: 10px;">';
                    echo nl2br(htmlspecialchars($ai_response));
                    echo '</div>';
                } else {
                    echo '<strong>⚠️ Response structure unexpected:</strong><br>';
                    echo '<pre>' . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . '</pre>';
                }
                echo '</div>';
            }
        } else {
            echo '<div class="error">';
            echo '<strong>HTTP Error:</strong> ' . $httpCode . '<br><br>';
            echo '<strong>Response:</strong><br>';
            $error_result = json_decode($response, true);
            if ($error_result && isset($error_result['error'])) {
                echo '<pre>' . htmlspecialchars(json_encode($error_result['error'], JSON_PRETTY_PRINT)) . '</pre>';
            } else {
                echo '<pre>' . htmlspecialchars(substr($response, 0, 500)) . '</pre>';
            }
            echo '</div>';
        }
    }
    
    echo '<h2>Full Response (for debugging):</h2>';
    echo '<pre>' . htmlspecialchars(substr($response, 0, 2000)) . '</pre>';
    ?>
    
    <hr>
    <p><a href="test_chatbot_api.php">← Back to Chatbot Test Page</a></p>
</body>
</html>

