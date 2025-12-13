<?php
/**
 * Chatbot API Key Setup Page
 * Simple interface to configure API keys
 */

session_start();

// Simple auth check - redirect to login if not admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    if (file_exists(__DIR__ . '/login.php')) {
        header('Location: login.php');
        exit;
    }
    // If login.php doesn't exist, just show a message
    die('Access denied. Admin access required.');
}

$message = '';
$error = '';
$config_file = __DIR__ . '/includes/chatbot_config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_api_key'])) {
    $api_provider = $_POST['api_provider'] ?? 'openai';
    $api_key = trim($_POST['api_key'] ?? '');
    
    if (empty($api_key)) {
        $error = "API key cannot be empty!";
    } else {
        // Read current config file
        $config_content = file_get_contents($config_file);
        
        if ($config_content === false) {
            $error = "Could not read config file. Please check file permissions.";
        } else {
            // Update chatbot mode
            $config_content = preg_replace(
                "/define\s*\(\s*['\"]CHATBOT_MODE['\"]\s*,\s*['\"][^'\"]*['\"]\s*\)/",
                "define('CHATBOT_MODE', 'ai_api')",
                $config_content
            );
            
            // Update API provider
            $config_content = preg_replace(
                "/define\s*\(\s*['\"]AI_API_PROVIDER['\"]\s*,\s*['\"][^'\"]*['\"]\s*\)/",
                "define('AI_API_PROVIDER', '" . addslashes($api_provider) . "')",
                $config_content
            );
            
            // Update the appropriate API key based on provider
            switch ($api_provider) {
                case 'openai':
                    $config_content = preg_replace(
                        "/define\s*\(\s*['\"]OPENAI_API_KEY['\"]\s*,\s*['\"][^'\"]*['\"]\s*\)/",
                        "define('OPENAI_API_KEY', '" . addslashes($api_key) . "')",
                        $config_content
                    );
                    break;
                case 'google':
                    $config_content = preg_replace(
                        "/define\s*\(\s*['\"]GEMINI_API_KEY['\"]\s*,\s*['\"][^'\"]*['\"]\s*\)/",
                        "define('GEMINI_API_KEY', '" . addslashes($api_key) . "')",
                        $config_content
                    );
                    break;
                case 'anthropic':
                    $config_content = preg_replace(
                        "/define\s*\(\s*['\"]ANTHROPIC_API_KEY['\"]\s*,\s*['\"][^'\"]*['\"]\s*\)/",
                        "define('ANTHROPIC_API_KEY', '" . addslashes($api_key) . "')",
                        $config_content
                    );
                    break;
                case 'custom':
                    $api_url = trim($_POST['api_url'] ?? '');
                    if (!empty($api_url)) {
                        $config_content = preg_replace(
                            "/define\s*\(\s*['\"]CUSTOM_API_URL['\"]\s*,\s*['\"][^'\"]*['\"]\s*\)/",
                            "define('CUSTOM_API_URL', '" . addslashes($api_url) . "')",
                            $config_content
                        );
                    }
                    $config_content = preg_replace(
                        "/define\s*\(\s*['\"]CUSTOM_API_KEY['\"]\s*,\s*['\"][^'\"]*['\"]\s*\)/",
                        "define('CUSTOM_API_KEY', '" . addslashes($api_key) . "')",
                        $config_content
                    );
                    break;
            }
            
            // Write back to file
            if (file_put_contents($config_file, $config_content) !== false) {
                $message = "API key saved successfully! The chatbot is now configured to use AI API.";
            } else {
                $error = "Could not write to config file. Please check file permissions. The file needs to be writable.";
            }
        }
    }
}

// Read current config to show current values
$current_mode = 'database';
$current_provider = 'openai';
$current_openai_key = '';
$current_gemini_key = '';
$current_anthropic_key = '';
$current_custom_url = '';
$current_custom_key = '';

if (file_exists($config_file)) {
    $config_content = file_get_contents($config_file);
    
    // Extract current values using regex
    if (preg_match("/define\s*\(\s*['\"]CHATBOT_MODE['\"]\s*,\s*['\"]([^'\"]*)['\"]/", $config_content, $matches)) {
        $current_mode = $matches[1];
    }
    if (preg_match("/define\s*\(\s*['\"]AI_API_PROVIDER['\"]\s*,\s*['\"]([^'\"]*)['\"]/", $config_content, $matches)) {
        $current_provider = $matches[1];
    }
    if (preg_match("/define\s*\(\s*['\"]OPENAI_API_KEY['\"]\s*,\s*['\"]([^'\"]*)['\"]/", $config_content, $matches)) {
        $current_openai_key = $matches[1];
    }
    if (preg_match("/define\s*\(\s*['\"]GEMINI_API_KEY['\"]\s*,\s*['\"]([^'\"]*)['\"]/", $config_content, $matches)) {
        $current_gemini_key = $matches[1];
    }
    if (preg_match("/define\s*\(\s*['\"]ANTHROPIC_API_KEY['\"]\s*,\s*['\"]([^'\"]*)['\"]/", $config_content, $matches)) {
        $current_anthropic_key = $matches[1];
    }
    if (preg_match("/define\s*\(\s*['\"]CUSTOM_API_URL['\"]\s*,\s*['\"]([^'\"]*)['\"]/", $config_content, $matches)) {
        $current_custom_url = $matches[1];
    }
    if (preg_match("/define\s*\(\s*['\"]CUSTOM_API_KEY['\"]\s*,\s*['\"]([^'\"]*)['\"]/", $config_content, $matches)) {
        $current_custom_key = $matches[1];
    }
}

// Mask API keys for display
function maskKey($key) {
    if (empty($key)) return '';
    if (strlen($key) <= 8) return '****';
    return substr($key, 0, 4) . '...' . substr($key, -4);
}

// Simple header if header.php doesn't exist
if (!file_exists(__DIR__ . '/includes/header.php')) {
    ?>
<!DOCTYPE html>
<html>
<head>
    <title>Chatbot API Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
    </style>
</head>
<body>
<?php
} else {
    include __DIR__ . '/includes/header.php';
}
?>

<div class="container">
    <h1>Chatbot API Key Setup</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Configure AI API</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label><strong>Current Mode:</strong></label>
                            <div class="alert alert-info">
                                <?php echo $current_mode === 'ai_api' ? '✅ AI API Mode (Active)' : '⚠️ Database Mode (AI API not active)'; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>AI Provider:</label>
                            <select name="api_provider" id="api_provider" class="form-control" required>
                                <option value="openai" <?php echo $current_provider === 'openai' ? 'selected' : ''; ?>>OpenAI (GPT-3.5, GPT-4)</option>
                                <option value="google" <?php echo $current_provider === 'google' ? 'selected' : ''; ?>>Google Gemini</option>
                                <option value="anthropic" <?php echo $current_provider === 'anthropic' ? 'selected' : ''; ?>>Anthropic Claude</option>
                                <option value="custom" <?php echo $current_provider === 'custom' ? 'selected' : ''; ?>>Custom API</option>
                            </select>
                        </div>
                        
                        <div id="openai-section" class="provider-section">
                            <div class="form-group">
                                <label>OpenAI API Key:</label>
                                <input type="text" name="api_key" class="form-control" 
                                       value="<?php echo htmlspecialchars($current_openai_key); ?>" 
                                       placeholder="sk-..." required>
                                <small class="form-text text-muted">
                                    Get your key from: <a href="https://platform.openai.com/api-keys" target="_blank">https://platform.openai.com/api-keys</a>
                                </small>
                            </div>
                        </div>
                        
                        <div id="google-section" class="provider-section" style="display:none;">
                            <div class="form-group">
                                <label>Google Gemini API Key:</label>
                                <input type="text" name="api_key" class="form-control" 
                                       value="<?php echo htmlspecialchars($current_gemini_key); ?>" 
                                       placeholder="Your Gemini API key">
                                <small class="form-text text-muted">
                                    Get your key from: <a href="https://makersuite.google.com/app/apikey" target="_blank">https://makersuite.google.com/app/apikey</a>
                                </small>
                            </div>
                        </div>
                        
                        <div id="anthropic-section" class="provider-section" style="display:none;">
                            <div class="form-group">
                                <label>Anthropic Claude API Key:</label>
                                <input type="text" name="api_key" class="form-control" 
                                       value="<?php echo htmlspecialchars($current_anthropic_key); ?>" 
                                       placeholder="Your Anthropic API key">
                                <small class="form-text text-muted">
                                    Get your key from: <a href="https://console.anthropic.com/" target="_blank">https://console.anthropic.com/</a>
                                </small>
                            </div>
                        </div>
                        
                        <div id="custom-section" class="provider-section" style="display:none;">
                            <div class="form-group">
                                <label>Custom API URL:</label>
                                <input type="text" name="api_url" class="form-control" 
                                       value="<?php echo htmlspecialchars($current_custom_url); ?>" 
                                       placeholder="https://your-api-endpoint.com/chat">
                            </div>
                            <div class="form-group">
                                <label>Custom API Key:</label>
                                <input type="text" name="api_key" class="form-control" 
                                       value="<?php echo htmlspecialchars($current_custom_key); ?>" 
                                       placeholder="Your custom API key">
                            </div>
                        </div>
                        
                        <button type="submit" name="save_api_key" class="btn btn-primary">Save API Key</button>
                        <a href="test_chatbot_api.php" class="btn btn-secondary">Test Configuration</a>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Current Configuration</h3>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Mode:</th>
                            <td><?php echo htmlspecialchars($current_mode); ?></td>
                        </tr>
                        <tr>
                            <th>Provider:</th>
                            <td><?php echo htmlspecialchars($current_provider); ?></td>
                        </tr>
                        <tr>
                            <th>OpenAI Key:</th>
                            <td><?php echo !empty($current_openai_key) ? maskKey($current_openai_key) : '<span class="text-muted">Not set</span>'; ?></td>
                        </tr>
                        <tr>
                            <th>Gemini Key:</th>
                            <td><?php echo !empty($current_gemini_key) ? maskKey($current_gemini_key) : '<span class="text-muted">Not set</span>'; ?></td>
                        </tr>
                        <tr>
                            <th>Anthropic Key:</th>
                            <td><?php echo !empty($current_anthropic_key) ? maskKey($current_anthropic_key) : '<span class="text-muted">Not set</span>'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3>Quick Guide</h3>
                </div>
                <div class="card-body">
                    <h5>How to Get API Keys:</h5>
                    <ul>
                        <li><strong>OpenAI:</strong><br>
                            <a href="https://platform.openai.com/api-keys" target="_blank">Get Key →</a><br>
                            <small>Free $5 credit for new accounts</small>
                        </li>
                        <li><strong>Google Gemini:</strong><br>
                            <a href="https://makersuite.google.com/app/apikey" target="_blank">Get Key →</a><br>
                            <small>Free tier available</small>
                        </li>
                        <li><strong>Anthropic Claude:</strong><br>
                            <a href="https://console.anthropic.com/" target="_blank">Get Key →</a><br>
                            <small>Pay-as-you-go</small>
                        </li>
                    </ul>
                    
                    <hr>
                    
                    <h5>Security Note:</h5>
                    <p class="text-muted small">
                        ⚠️ API keys are sensitive. Never share them publicly or commit to version control.
                    </p>
                    
                    <hr>
                    
                    <h5>After Setup:</h5>
                    <ol class="small">
                        <li>Click "Save API Key"</li>
                        <li>Go to "Test Configuration"</li>
                        <li>Verify API key status</li>
                        <li>Test the chatbot</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('api_provider').addEventListener('change', function() {
    // Hide all sections
    document.querySelectorAll('.provider-section').forEach(function(section) {
        section.style.display = 'none';
        section.querySelectorAll('input').forEach(function(input) {
            input.removeAttribute('required');
        });
    });
    
    // Show selected section
    const provider = this.value;
    const section = document.getElementById(provider + '-section');
    if (section) {
        section.style.display = 'block';
        section.querySelectorAll('input[name="api_key"]').forEach(function(input) {
            if (provider !== 'custom') {
                input.setAttribute('required', 'required');
            }
        });
    }
});

// Initialize on page load
document.getElementById('api_provider').dispatchEvent(new Event('change'));
</script>

<?php 
if (!file_exists(__DIR__ . '/includes/footer.php')) {
    echo '</body></html>';
} else {
    include __DIR__ . '/includes/footer.php';
}
?>

