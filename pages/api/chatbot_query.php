<?php
/**
 * Chatbot Query API Endpoint
 * Handles chatbot queries from the frontend
 * Supports both database lookup and AI API integration
 */

session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/chatbot_config.php';

// Enable debug mode if requested
if (isset($_GET['debug']) || (defined('CHATBOT_DEBUG') && CHATBOT_DEBUG)) {
    if (!defined('CHATBOT_DEBUG')) {
        define('CHATBOT_DEBUG', true);
    }
}

header('Content-Type: application/json');

// Get user role
$user_role = $_SESSION['role'] ?? 'admin';
$user_id = $_SESSION['user_id'] ?? null;
$session_id = session_id();

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');

if (empty($message)) {
    echo json_encode([
        'success' => false,
        'error' => 'No message provided'
    ]);
    exit;
}

$response = null;
$response_source = 'fallback';
$error_message = null;

// Try AI API first if enabled
if (CHATBOT_MODE === 'ai_api') {
    // Check if API key is configured for the selected provider
    $has_api_key = false;
    switch (AI_API_PROVIDER) {
        case 'openai':
            $has_api_key = !empty(OPENAI_API_KEY);
            break;
        case 'google':
            $has_api_key = !empty(GEMINI_API_KEY);
            break;
        case 'anthropic':
            $has_api_key = !empty(ANTHROPIC_API_KEY);
            break;
        case 'custom':
            $has_api_key = !empty(CUSTOM_API_URL) && !empty(CUSTOM_API_KEY);
            break;
    }
    
    if ($has_api_key) {
        $response = getAIResponse($message, $user_role, $user_id, $session_id);
        if ($response) {
            $response_source = 'ai_api';
        } else {
            $error_message = 'AI API returned no response';
        }
    } else {
        $error_message = 'API key not configured for ' . AI_API_PROVIDER;
    }
}

// Fallback to database if AI API failed or not enabled
if (!$response && (CHATBOT_MODE === 'database' || (CHATBOT_MODE === 'ai_api' && FALLBACK_TO_DATABASE))) {
    $response = getDatabaseResponse($message, $user_role);
    if ($response) {
        $response_source = 'database';
    }
}

// Final fallback
if (!$response) {
    $response = [
        'title' => "I'm here to help!",
        'content' => '<p>I received: "' . htmlspecialchars($message) . '"</p><p>Try asking about:</p><ul><li>How to review applications</li><li>How to generate invoices</li><li>Application workflow</li><li>Or click the quick topics in the chatbot!</li></ul>'
    ];
    $response_source = 'fallback';
}

// Save conversation if enabled
if (SAVE_CONVERSATIONS) {
    saveConversation($user_id, $user_role, $session_id, $message, $response, $response_source);
}

// Return response
$output = [
    'success' => true,
    'title' => $response['title'],
    'content' => $response['content'],
    'source' => $response_source
];

// Include error message in debug mode (remove in production)
if ($error_message && (defined('CHATBOT_DEBUG') && CHATBOT_DEBUG)) {
    $output['debug'] = $error_message;
}

// Include additional debug info
if (defined('CHATBOT_DEBUG') && CHATBOT_DEBUG) {
    $has_api_key = false;
    if (CHATBOT_MODE === 'ai_api') {
        switch (AI_API_PROVIDER) {
            case 'openai':
                $has_api_key = !empty(OPENAI_API_KEY);
                break;
            case 'google':
                $has_api_key = !empty(GEMINI_API_KEY);
                break;
            case 'anthropic':
                $has_api_key = !empty(ANTHROPIC_API_KEY);
                break;
            case 'custom':
                $has_api_key = !empty(CUSTOM_API_URL) && !empty(CUSTOM_API_KEY);
                break;
        }
    }
    
    $output['debug_info'] = [
        'mode' => CHATBOT_MODE,
        'provider' => CHATBOT_MODE === 'ai_api' ? AI_API_PROVIDER : 'N/A',
        'has_api_key' => $has_api_key,
        'curl_available' => function_exists('curl_init'),
        'error_message' => $error_message
    ];
}

echo json_encode($output);

/**
 * Get response from database
 */
function getDatabaseResponse($message, $user_role) {
    $conn = getDBConnection();
    if (!$conn) {
        return null;
    }
    
    $lowerMessage = strtolower($message);
    
    // Search for matching keywords
    $stmt = $conn->prepare("
        SELECT keyword, title, content, priority 
        FROM chatbot_knowledge 
        WHERE role = ? AND is_active = 1 
        AND (LOWER(?) LIKE CONCAT('%', keyword, '%') OR keyword IN (?, ?, ?, ?, ?))
        ORDER BY priority DESC, LENGTH(keyword) DESC
        LIMIT 1
    ");
    
    // Extract potential keywords from message
    $keywords = extractKeywords($lowerMessage);
    $keyword1 = $keywords[0] ?? '';
    $keyword2 = $keywords[1] ?? '';
    $keyword3 = $keywords[2] ?? '';
    $keyword4 = $keywords[3] ?? '';
    $keyword5 = $keywords[4] ?? '';
    
    $stmt->bind_param("sssssss", $user_role, $lowerMessage, $keyword1, $keyword2, $keyword3, $keyword4, $keyword5);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        $conn->close();
        return [
            'title' => $row['title'],
            'content' => $row['content']
        ];
    }
    
    // Try broader search across all roles
    $stmt2 = $conn->prepare("
        SELECT keyword, title, content, priority 
        FROM chatbot_knowledge 
        WHERE is_active = 1 
        AND LOWER(?) LIKE CONCAT('%', keyword, '%')
        ORDER BY priority DESC
        LIMIT 1
    ");
    
    $stmt2->bind_param("s", $lowerMessage);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    if ($row2 = $result2->fetch_assoc()) {
        $stmt2->close();
        $conn->close();
        return [
            'title' => $row2['title'],
            'content' => $row2['content']
        ];
    }
    
    $stmt->close();
    $stmt2->close();
    $conn->close();
    return null;
}

/**
 * Get response from AI API
 */
function getAIResponse($message, $user_role, $user_id, $session_id) {
    // Get conversation history for context
    $conversation_history = [];
    if (SAVE_CONVERSATIONS && MAX_CONVERSATION_HISTORY > 0) {
        $conversation_history = getRecentConversations($user_id, $session_id, MAX_CONVERSATION_HISTORY);
    }
    
    // Build context
    $context = CHATBOT_SYSTEM_CONTEXT . "\n\nUser role: " . $user_role;
    
    // Add conversation history
    if (!empty($conversation_history)) {
        $context .= "\n\nRecent conversation:\n";
        foreach ($conversation_history as $conv) {
            $context .= "User: " . $conv['message'] . "\n";
            $context .= "Assistant: " . strip_tags($conv['response']) . "\n\n";
        }
    }
    
    // Call appropriate AI API
    switch (AI_API_PROVIDER) {
        case 'openai':
            return getOpenAIResponse($message, $context);
        case 'google':
            return getGeminiResponse($message, $context);
        case 'anthropic':
            return getAnthropicResponse($message, $context);
        case 'custom':
            return getCustomAPIResponse($message, $context);
        default:
            return null;
    }
}

/**
 * Get response from OpenAI
 */
function getOpenAIResponse($message, $context) {
    if (empty(OPENAI_API_KEY)) {
        return null;
    }
    
    $url = 'https://api.openai.com/v1/chat/completions';
    
    $data = [
        'model' => OPENAI_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => $context],
            ['role' => 'user', 'content' => $message]
        ],
        'max_tokens' => OPENAI_MAX_TOKENS,
        'temperature' => 0.7
    ];
    
    // Check if curl is available
    if (!function_exists('curl_init')) {
        error_log('Chatbot: cURL is not available');
        return null;
    }
    
    $ch = curl_init($url);
    if ($ch === false) {
        error_log('Chatbot: Failed to initialize cURL');
        return null;
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Log errors
    if ($curlError) {
        error_log('Chatbot OpenAI cURL Error: ' . $curlError);
        return null;
    }
    
    if ($httpCode !== 200) {
        error_log('Chatbot OpenAI HTTP Error: ' . $httpCode . ' - Response: ' . substr($response, 0, 200));
        return null;
    }
    
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Chatbot OpenAI JSON Error: ' . json_last_error_msg());
        return null;
    }
    
    if (isset($result['error'])) {
        error_log('Chatbot OpenAI API Error: ' . json_encode($result['error']));
        return null;
    }
    
    if (isset($result['choices'][0]['message']['content'])) {
        $content = $result['choices'][0]['message']['content'];
        // Convert markdown to HTML if needed
        $content = nl2br(htmlspecialchars($content));
        return [
            'title' => 'AI Assistant',
            'content' => '<p>' . $content . '</p>'
        ];
    }
    
    error_log('Chatbot OpenAI: Unexpected response structure: ' . substr($response, 0, 200));
    return null;
}

/**
 * Get response from Google Gemini
 */
function getGeminiResponse($message, $context) {
    if (empty(GEMINI_API_KEY)) {
        error_log('Chatbot Gemini: API key is empty');
        return null;
    }
    
    // Use v1beta API (more compatible and supports all models)
    $api_version = defined('GEMINI_API_VERSION') ? GEMINI_API_VERSION : 'v1beta';
    
    // Always use v1beta API endpoint (more reliable)
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY;
    
    // Build the prompt with system context
    $full_prompt = $context . "\n\nUser: " . $message . "\n\nAssistant:";
    
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
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 1024,
        ]
    ];
    
    // Check if curl is available
    if (!function_exists('curl_init')) {
        error_log('Chatbot: cURL is not available');
        return null;
    }
    
    $ch = curl_init($url);
    if ($ch === false) {
        error_log('Chatbot: Failed to initialize cURL');
        return null;
    }
    
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
    
    if ($curlError) {
        error_log('Chatbot Gemini cURL Error: ' . $curlError);
        return null;
    }
    
    if ($httpCode !== 200) {
        $error_detail = substr($response, 0, 500);
        error_log('Chatbot Gemini HTTP Error: ' . $httpCode . ' - Response: ' . $error_detail);
        // Try to parse error message
        $error_result = json_decode($response, true);
        if (isset($error_result['error']['message'])) {
            error_log('Chatbot Gemini Error Message: ' . $error_result['error']['message']);
        }
        return null;
    }
    
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Chatbot Gemini JSON Error: ' . json_last_error_msg() . ' - Raw response: ' . substr($response, 0, 200));
        return null;
    }
    
    if (isset($result['error'])) {
        error_log('Chatbot Gemini API Error: ' . json_encode($result['error']));
        return null;
    }
    
    // Check for response in candidates
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $content = $result['candidates'][0]['content']['parts'][0]['text'];
        $content = nl2br(htmlspecialchars($content));
        return [
            'title' => 'AI Assistant',
            'content' => '<p>' . $content . '</p>'
        ];
    }
    
    // Log full response structure for debugging
    error_log('Chatbot Gemini: Unexpected response structure. Full response: ' . json_encode($result));
    return null;
}

/**
 * Get response from Anthropic Claude
 */
function getAnthropicResponse($message, $context) {
    if (empty(ANTHROPIC_API_KEY)) {
        return null;
    }
    
    $url = 'https://api.anthropic.com/v1/messages';
    
    $data = [
        'model' => ANTHROPIC_MODEL,
        'max_tokens' => OPENAI_MAX_TOKENS,
        'system' => $context,
        'messages' => [
            ['role' => 'user', 'content' => $message]
        ]
    ];
    
    // Check if curl is available
    if (!function_exists('curl_init')) {
        error_log('Chatbot: cURL is not available');
        return null;
    }
    
    $ch = curl_init($url);
    if ($ch === false) {
        error_log('Chatbot: Failed to initialize cURL');
        return null;
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . ANTHROPIC_API_KEY,
        'anthropic-version: 2023-06-01'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log('Chatbot Anthropic cURL Error: ' . $curlError);
        return null;
    }
    
    if ($httpCode !== 200) {
        error_log('Chatbot Anthropic HTTP Error: ' . $httpCode . ' - Response: ' . substr($response, 0, 200));
        return null;
    }
    
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Chatbot Anthropic JSON Error: ' . json_last_error_msg());
        return null;
    }
    
    if (isset($result['error'])) {
        error_log('Chatbot Anthropic API Error: ' . json_encode($result['error']));
        return null;
    }
    
    if (isset($result['content'][0]['text'])) {
        $content = $result['content'][0]['text'];
        $content = nl2br(htmlspecialchars($content));
        return [
            'title' => 'AI Assistant',
            'content' => '<p>' . $content . '</p>'
        ];
    }
    
    error_log('Chatbot Anthropic: Unexpected response structure: ' . substr($response, 0, 200));
    return null;
}

/**
 * Get response from custom API
 */
function getCustomAPIResponse($message, $context) {
    if (empty(CUSTOM_API_URL)) {
        return null;
    }
    
    $data = [
        'message' => $message,
        'context' => $context,
        'api_key' => CUSTOM_API_KEY
    ];
    
    // Check if curl is available
    if (!function_exists('curl_init')) {
        error_log('Chatbot: cURL is not available');
        return null;
    }
    
    $ch = curl_init(CUSTOM_API_URL);
    if ($ch === false) {
        error_log('Chatbot: Failed to initialize cURL');
        return null;
    }
    
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
    
    if ($curlError) {
        error_log('Chatbot Custom API cURL Error: ' . $curlError);
        return null;
    }
    
    if ($httpCode !== 200) {
        error_log('Chatbot Custom API HTTP Error: ' . $httpCode . ' - Response: ' . substr($response, 0, 200));
        return null;
    }
    
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Chatbot Custom API JSON Error: ' . json_last_error_msg());
        return null;
    }
    
    if (isset($result['title']) && isset($result['content'])) {
        return [
            'title' => $result['title'],
            'content' => $result['content']
        ];
    }
    
    error_log('Chatbot Custom API: Unexpected response structure: ' . substr($response, 0, 200));
    return null;
}

/**
 * Extract keywords from message
 */
function extractKeywords($message) {
    $commonWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'how', 'what', 'where', 'when', 'why', 'is', 'are', 'was', 'were', 'do', 'does', 'did', 'can', 'could', 'will', 'would', 'should'];
    $words = preg_split('/\s+/', strtolower($message));
    $keywords = array_filter($words, function($word) use ($commonWords) {
        return strlen($word) > 2 && !in_array($word, $commonWords);
    });
    return array_values(array_slice($keywords, 0, 5));
}

/**
 * Get recent conversations for context
 */
function getRecentConversations($user_id, $session_id, $limit) {
    $conn = getDBConnection();
    if (!$conn) {
        return [];
    }
    
    if ($user_id) {
        $stmt = $conn->prepare("SELECT message, response FROM chatbot_conversations WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $user_id, $limit);
    } else {
        $stmt = $conn->prepare("SELECT message, response FROM chatbot_conversations WHERE session_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("si", $session_id, $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $conversations = [];
    
    while ($row = $result->fetch_assoc()) {
        $conversations[] = [
            'message' => $row['message'],
            'response' => strip_tags($row['response'])
        ];
    }
    
    $stmt->close();
    $conn->close();
    
    return array_reverse($conversations); // Reverse to get chronological order
}

/**
 * Save conversation to database
 */
function saveConversation($user_id, $user_role, $session_id, $message, $response, $source) {
    $conn = getDBConnection();
    if (!$conn) {
        return;
    }
    
    $response_text = $response['title'] . "\n" . strip_tags($response['content']);
    
    $stmt = $conn->prepare("INSERT INTO chatbot_conversations (user_id, user_role, session_id, message, response, response_source) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $user_role, $session_id, $message, $response_text, $source);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

?>

