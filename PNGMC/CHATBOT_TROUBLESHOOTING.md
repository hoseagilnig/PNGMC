# Chatbot AI API Troubleshooting Guide

## Issues Fixed

### 1. API Key Detection
- **Problem**: The code was only checking for OpenAI API key, even when using other providers
- **Fix**: Now checks the correct API key based on the selected provider (OpenAI, Google, Anthropic, or Custom)

### 2. Error Handling
- **Problem**: No error handling for curl failures, HTTP errors, or JSON parsing errors
- **Fix**: Added comprehensive error handling with logging for all API calls

### 3. Debugging
- **Problem**: No way to see what's going wrong
- **Fix**: Added debug mode and a test page to diagnose issues

## How to Diagnose Issues

### Step 1: Use the Test Page

Access the test page:
```
http://localhost/sms2/pages/test_chatbot_api.php
```

This page will show you:
- Current chatbot mode
- API provider configuration
- API key status
- PHP extensions (cURL, JSON)
- Database connection status

### Step 2: Test the API

1. Enter a test message (e.g., "How do I generate an invoice?")
2. Click "Test API" to see the response
3. Click "Test with Debug" to see detailed debug information

### Step 3: Check Error Logs

If the API is still not working, check your PHP error log:
- **XAMPP**: `C:\xampp\php\logs\php_error_log`
- **Apache**: `C:\xampp\apache\logs\error.log`

Look for errors with prefix: `Chatbot`

## Common Issues and Solutions

### Issue 1: "API key not configured"

**Solution:**
1. Edit `pages/includes/chatbot_config.php`
2. Set `CHATBOT_MODE` to `'ai_api'`
3. Set `AI_API_PROVIDER` to your provider (e.g., `'openai'`)
4. Add your API key:
   ```php
   define('OPENAI_API_KEY', 'sk-your-actual-api-key-here');
   ```

### Issue 2: "cURL is not available"

**Solution:**
1. Open `php.ini` (usually in `C:\xampp\php\php.ini`)
2. Find the line: `;extension=curl`
3. Remove the semicolon: `extension=curl`
4. Restart Apache

### Issue 3: "HTTP Error: 401" or "HTTP Error: 403"

**Causes:**
- Invalid API key
- API key expired
- Insufficient API credits

**Solution:**
- Verify your API key is correct
- Check your API account for credits/limits
- For OpenAI: Check at https://platform.openai.com/api-keys

### Issue 4: "HTTP Error: 429"

**Cause:** Rate limit exceeded

**Solution:**
- Wait a few minutes and try again
- Upgrade your API plan if needed
- Reduce the number of requests

### Issue 5: "Timeout" or "Connection Error"

**Causes:**
- Internet connection issues
- Firewall blocking requests
- API service down

**Solution:**
- Check your internet connection
- Verify firewall settings
- Check API service status (e.g., https://status.openai.com)

### Issue 6: "Unexpected response structure"

**Cause:** API response format changed or unexpected

**Solution:**
- Check the API documentation for current response format
- Enable debug mode to see the actual response
- Check error logs for full response

## Enable Debug Mode

To see detailed error messages:

1. Edit `pages/includes/chatbot_config.php`
2. Set:
   ```php
   define('CHATBOT_DEBUG', true);
   ```
3. Test the chatbot - you'll see debug information in responses

**⚠️ Warning:** Disable debug mode in production for security!

## Testing Checklist

- [ ] Chatbot mode is set to `'ai_api'`
- [ ] Correct API provider is selected
- [ ] API key is configured and correct
- [ ] cURL extension is enabled in PHP
- [ ] Internet connection is working
- [ ] API account has credits/access
- [ ] No firewall blocking API requests
- [ ] Error logs checked for details

## Quick Test Commands

### Test cURL
```php
<?php
if (function_exists('curl_init')) {
    echo "cURL is available";
} else {
    echo "cURL is NOT available";
}
?>
```

### Test API Key
```php
<?php
require_once 'pages/includes/chatbot_config.php';
echo "Mode: " . CHATBOT_MODE . "\n";
echo "Provider: " . AI_API_PROVIDER . "\n";
echo "API Key configured: " . (!empty(OPENAI_API_KEY) ? "Yes" : "No") . "\n";
?>
```

## Still Not Working?

1. **Check the test page** (`pages/test_chatbot_api.php`) for configuration status
2. **Enable debug mode** to see detailed error messages
3. **Check PHP error logs** for specific errors
4. **Verify API key** is correct and has credits
5. **Test with a simple message** first
6. **Check API service status** (OpenAI, Google, etc.)

## Fallback to Database

If AI API is not working, the system will automatically fall back to database mode if `FALLBACK_TO_DATABASE` is set to `true` (default).

To ensure fallback works:
1. Run `database/create_chatbot_knowledge.php` to create knowledge base
2. Add knowledge entries via `pages/manage_chatbot_knowledge.php`

## Support

For more information, see:
- `CHATBOT_SETUP_GUIDE.md` - Complete setup guide
- `CHATBOT_IMPLEMENTATION_SUMMARY.md` - Implementation details

