<?php
/**
 * Chatbot Configuration
 * Configure chatbot behavior: database mode or AI API mode
 */

// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Chatbot mode: 'database' or 'ai_api'
// IMPORTANT: Set to 'ai_api' to use AI API, or 'database' to use stored knowledge base
define('CHATBOT_MODE', 'ai_api'); // Change to 'database' to use stored knowledge base instead

// AI API Configuration (if using AI API mode)
// Options: 'openai', 'google', 'anthropic', 'custom'
define('AI_API_PROVIDER', 'google'); // Changed to Google Gemini

// OpenAI Configuration
// Get your API key from: https://platform.openai.com/api-keys
// Format: sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
// ⚠️ SECURITY: Use environment variable in production!
define('OPENAI_API_KEY', getEnvVar('OPENAI_API_KEY', '')); // Load from .env file
define('OPENAI_MODEL', 'gpt-3.5-turbo'); // Model to use: gpt-3.5-turbo, gpt-4, etc.
define('OPENAI_MAX_TOKENS', 500); // Maximum response length

// Google Gemini Configuration
// ⚠️ SECURITY: Use environment variable in production!
// Priority: 1. Environment variable (.env), 2. System environment, 3. Fallback (for development only)
$gemini_key = getEnvVar('GEMINI_API_KEY', '');
if (empty($gemini_key)) {
    $gemini_key = getenv('GEMINI_API_KEY') ?: '';
}
// ⚠️ REMOVE THIS FALLBACK IN PRODUCTION!
if (empty($gemini_key)) {
    $gemini_key = 'AIzaSyD2DHnfmijjr2dn85B2S2VgBzLVUo8oPWY'; // TEMPORARY: Move to .env file!
}
define('GEMINI_API_KEY', $gemini_key);
define('GEMINI_MODEL', 'gemini-pro'); // Model to use: 'gemini-pro' or 'gemini-1.5-flash'
define('GEMINI_API_VERSION', 'v1beta'); // API version: Use 'v1beta' (more compatible)

// Anthropic Claude Configuration
// ⚠️ SECURITY: Use environment variable in production!
define('ANTHROPIC_API_KEY', getEnvVar('ANTHROPIC_API_KEY', '')); // Load from .env file
define('ANTHROPIC_MODEL', 'claude-3-haiku-20240307'); // Model to use

// Custom API Configuration
define('CUSTOM_API_URL', getEnvVar('CUSTOM_API_URL', '')); // Your custom API endpoint
define('CUSTOM_API_KEY', getEnvVar('CUSTOM_API_KEY', '')); // Your custom API key

// System Context (used for AI API)
define('CHATBOT_SYSTEM_CONTEXT', 'You are a helpful assistant for the PNG Maritime College Student Management System. You help users understand how to use the system, answer questions about workflows, and provide guidance on various features. Be concise, clear, and helpful.');

// Fallback to database if AI API fails
define('FALLBACK_TO_DATABASE', true);

// Enable conversation history
define('SAVE_CONVERSATIONS', true);

// Maximum conversation history to send to AI (for context)
define('MAX_CONVERSATION_HISTORY', 10);

// Debug mode - Set to true to see error messages in responses (for development only)
// ⚠️ SECURITY: MUST be false in production!
$debug_mode = getEnvVar('CHATBOT_DEBUG', 'false');
define('CHATBOT_DEBUG', ($debug_mode === 'true' || $debug_mode === true)); // Default to false for production

?>

