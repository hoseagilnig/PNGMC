# API Key Setup Guide

## Quick Setup Steps

### Step 1: Choose Your AI Provider

You can use one of these AI providers:
- **OpenAI** (GPT-3.5, GPT-4) - Most popular
- **Google Gemini** - Free tier available
- **Anthropic Claude** - High quality responses
- **Custom API** - Your own API endpoint

### Step 2: Get Your API Key

#### For OpenAI:
1. Go to: https://platform.openai.com/api-keys
2. Sign up or log in
3. Click "Create new secret key"
4. Copy the key (starts with `sk-`)
5. **Important:** Save it immediately - you won't see it again!

#### For Google Gemini:
1. Go to: https://makersuite.google.com/app/apikey
2. Sign in with Google account
3. Click "Create API Key"
4. Copy the key

#### For Anthropic Claude:
1. Go to: https://console.anthropic.com/
2. Sign up or log in
3. Navigate to API Keys section
4. Create a new API key
5. Copy the key

### Step 3: Add API Key to Configuration

1. Open: `pages/includes/chatbot_config.php`

2. Find the section for your provider and add your API key:

**For OpenAI:**
```php
define('CHATBOT_MODE', 'ai_api');
define('AI_API_PROVIDER', 'openai');
define('OPENAI_API_KEY', 'sk-your-actual-api-key-here');
```

**For Google Gemini:**
```php
define('CHATBOT_MODE', 'ai_api');
define('AI_API_PROVIDER', 'google');
define('GEMINI_API_KEY', 'your-gemini-api-key-here');
```

**For Anthropic Claude:**
```php
define('CHATBOT_MODE', 'ai_api');
define('AI_API_PROVIDER', 'anthropic');
define('ANTHROPIC_API_KEY', 'your-anthropic-api-key-here');
```

3. Save the file

### Step 4: Verify Setup

1. Go to: `http://localhost/sms2/pages/test_chatbot_api.php`
2. Check "API Key Status" - should show "Configured"
3. Test the chatbot with a message

## Example Configuration

Here's a complete example for OpenAI:

```php
// Enable AI API mode
define('CHATBOT_MODE', 'ai_api');

// Set provider
define('AI_API_PROVIDER', 'openai');

// Add your API key (replace with your actual key)
define('OPENAI_API_KEY', 'sk-proj-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('OPENAI_MODEL', 'gpt-3.5-turbo');
define('OPENAI_MAX_TOKENS', 500);
```

## Security Notes

⚠️ **IMPORTANT:**
- Never commit API keys to version control (Git)
- Don't share your API keys publicly
- Keep your API keys secure
- Rotate keys if compromised

## Cost Information

### OpenAI
- GPT-3.5-turbo: ~$0.0015 per 1K tokens (very affordable)
- GPT-4: More expensive but higher quality
- Free tier: $5 credit for new accounts

### Google Gemini
- Free tier: 60 requests per minute
- Paid: Very affordable pricing

### Anthropic Claude
- Pay-as-you-go pricing
- High quality responses

## Troubleshooting

### "API Key: Not configured"
- Make sure you added the key in `chatbot_config.php`
- Check that the key is inside quotes: `'your-key-here'`
- Verify the key doesn't have extra spaces
- Make sure `CHATBOT_MODE` is set to `'ai_api'`

### "Invalid API Key"
- Verify the key is correct (copy-paste to avoid typos)
- Check if the key has expired
- For OpenAI: Make sure key starts with `sk-`

### "Insufficient Credits"
- Check your API account balance
- Add credits to your account
- For OpenAI: Check at https://platform.openai.com/account/billing

## Testing

After adding your API key:

1. **Test Configuration:**
   - Visit: `pages/test_chatbot_api.php`
   - Check "API Key Status" section
   - Should show "Configured"

2. **Test Chatbot:**
   - Use the chatbot in any dashboard
   - Ask a question
   - Should get AI-powered response

3. **Check Response Source:**
   - In test page, check "Source" in response
   - Should show "ai_api" if working correctly

## Fallback Mode

If AI API fails, the system will automatically use database mode (if enabled).

To ensure fallback works:
1. Run: `database/create_chatbot_knowledge.php`
2. Add knowledge entries via: `pages/manage_chatbot_knowledge.php`

## Need Help?

- Check `CHATBOT_TROUBLESHOOTING.md` for common issues
- Use the test page to diagnose problems
- Check PHP error logs for detailed errors

