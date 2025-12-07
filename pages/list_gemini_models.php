<?php
/**
 * List Available Gemini Models
 * Helps identify which models are available for your API key
 */

require_once __DIR__ . '/includes/chatbot_config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>List Gemini Models</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #1d4e89; color: white; }
    </style>
</head>
<body>
    <h1>Available Google Gemini Models</h1>
    
    <?php
    if (empty(GEMINI_API_KEY)) {
        echo '<div class="error">ERROR: Gemini API key is not configured!</div>';
        exit;
    }
    
    // Try v1beta API first
    $api_versions = ['v1beta', 'v1'];
    $models_found = false;
    
    foreach ($api_versions as $version) {
        echo '<h2>Trying API Version: ' . $version . '</h2>';
        
        $url = 'https://generativelanguage.googleapis.com/' . $version . '/models?key=' . GEMINI_API_KEY;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            echo '<div class="error">cURL Error: ' . htmlspecialchars($curlError) . '</div>';
            continue;
        }
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            
            if (isset($result['models']) && is_array($result['models'])) {
                echo '<div class="success">✅ Successfully retrieved models from ' . $version . ' API!</div>';
                
                echo '<table>';
                echo '<tr><th>Model Name</th><th>Display Name</th><th>Supported Methods</th><th>Description</th></tr>';
                
                foreach ($result['models'] as $model) {
                    $name = $model['name'] ?? 'N/A';
                    $display_name = $model['displayName'] ?? 'N/A';
                    $description = $model['description'] ?? '';
                    $methods = isset($model['supportedGenerationMethods']) ? implode(', ', $model['supportedGenerationMethods']) : 'N/A';
                    
                    // Extract short name from full path
                    $short_name = basename($name);
                    
                    echo '<tr>';
                    echo '<td><strong>' . htmlspecialchars($short_name) . '</strong><br><small>' . htmlspecialchars($name) . '</small></td>';
                    echo '<td>' . htmlspecialchars($display_name) . '</td>';
                    echo '<td>' . htmlspecialchars($methods) . '</td>';
                    echo '<td>' . htmlspecialchars($description) . '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
                
                // Find models that support generateContent
                $generate_content_models = [];
                foreach ($result['models'] as $model) {
                    if (isset($model['supportedGenerationMethods']) && 
                        in_array('generateContent', $model['supportedGenerationMethods'])) {
                        $generate_content_models[] = basename($model['name']);
                    }
                }
                
                if (!empty($generate_content_models)) {
                    echo '<div class="info">';
                    echo '<strong>✅ Models that support generateContent:</strong><br>';
                    echo '<ul>';
                    foreach ($generate_content_models as $model_name) {
                        echo '<li><code>' . htmlspecialchars($model_name) . '</code></li>';
                    }
                    echo '</ul>';
                    echo '<strong>Recommended:</strong> Use one of these models in your config.<br>';
                    echo 'Example: <code>define(\'GEMINI_MODEL\', \'' . $generate_content_models[0] . '\');</code>';
                    echo '</div>';
                }
                
                $models_found = true;
                break; // Stop after first successful API call
            } else {
                echo '<div class="error">Unexpected response structure</div>';
                echo '<pre>' . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . '</pre>';
            }
        } else {
            echo '<div class="error">HTTP Error: ' . $httpCode . '</div>';
            $error_result = json_decode($response, true);
            if ($error_result && isset($error_result['error'])) {
                echo '<pre>' . htmlspecialchars(json_encode($error_result['error'], JSON_PRETTY_PRINT)) . '</pre>';
            } else {
                echo '<pre>' . htmlspecialchars(substr($response, 0, 500)) . '</pre>';
            }
        }
    }
    
    if (!$models_found) {
        echo '<div class="error">';
        echo '<strong>⚠️ Could not retrieve models from either API version.</strong><br><br>';
        echo 'Possible issues:<br>';
        echo '<ul>';
        echo '<li>API key might be invalid or expired</li>';
        echo '<li>API key might not have access to Gemini API</li>';
        echo '<li>Network/firewall issues</li>';
        echo '<li>Google API service might be down</li>';
        echo '</ul>';
        echo '<br>';
        echo 'Try:<br>';
        echo '<ol>';
        echo '<li>Verify your API key at: <a href="https://makersuite.google.com/app/apikey" target="_blank">https://makersuite.google.com/app/apikey</a></li>';
        echo '<li>Make sure Gemini API is enabled for your project</li>';
        echo '<li>Check if there are any API quotas or restrictions</li>';
        echo '</ol>';
        echo '</div>';
    }
    ?>
    
    <hr>
    <p><a href="test_gemini_api.php">← Back to Gemini API Test</a> | <a href="test_chatbot_api.php">Chatbot Test Page</a></p>
</body>
</html>

