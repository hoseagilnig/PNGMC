# Chatbot Implementation Summary

## Overview

The chatbot system has been enhanced to support two modes:
1. **Database-Backed Knowledge Base**: Store and manage responses in the database
2. **AI API Integration**: Connect to external AI services (OpenAI, Google Gemini, Anthropic Claude, or custom APIs)

## Files Created

### 1. Database Setup
- **`database/create_chatbot_knowledge.php`**: Creates database tables and inserts default knowledge entries

### 2. Configuration
- **`pages/includes/chatbot_config.php`**: Configuration file for chatbot mode and AI API settings

### 3. API Endpoint
- **`pages/api/chatbot_query.php`**: Backend API that handles chatbot queries, supports both database and AI API modes

### 4. Admin Interface
- **`pages/manage_chatbot_knowledge.php`**: Admin page to manage knowledge base entries (add, edit, delete)

### 5. Documentation
- **`CHATBOT_SETUP_GUIDE.md`**: Complete setup and configuration guide

## Files Modified

### 1. Chatbot Frontend
- **`pages/includes/chatbot_simple.php`**: Updated to use AJAX API calls instead of hardcoded responses

## How to Use

### Step 1: Setup Database

Access via browser:
```
http://localhost/sms2/database/create_chatbot_knowledge.php
```

Or via command line (if PHP is in PATH):
```bash
php database/create_chatbot_knowledge.php
```

This creates:
- `chatbot_knowledge` table: Stores knowledge entries
- `chatbot_conversations` table: Stores conversation history

### Step 2: Choose Mode

Edit `pages/includes/chatbot_config.php`:

**For Database Mode (Default):**
```php
define('CHATBOT_MODE', 'database');
```

**For AI API Mode:**
```php
define('CHATBOT_MODE', 'ai_api');
define('AI_API_PROVIDER', 'openai'); // or 'google', 'anthropic', 'custom'
define('OPENAI_API_KEY', 'your-api-key-here');
```

### Step 3: Manage Knowledge Base

1. Login as admin
2. Go to: `pages/manage_chatbot_knowledge.php`
3. Add/edit/delete knowledge entries

## Features

### Database Mode
- ✅ Keyword-based matching
- ✅ Role-specific responses
- ✅ Priority-based matching
- ✅ HTML content support
- ✅ Active/inactive entries

### AI API Mode
- ✅ OpenAI GPT integration
- ✅ Google Gemini integration
- ✅ Anthropic Claude integration
- ✅ Custom API support
- ✅ Conversation history context
- ✅ Automatic fallback to database

### Both Modes
- ✅ Conversation history saving
- ✅ Role-based responses
- ✅ Error handling
- ✅ Fallback mechanisms

## API Endpoint

**URL:** `pages/api/chatbot_query.php`

**Method:** POST

**Request:**
```json
{
    "message": "How do I generate an invoice?"
}
```

**Response:**
```json
{
    "success": true,
    "title": "Generate Invoice",
    "content": "<p>How to generate...</p>",
    "source": "database"
}
```

## Configuration Options

### Chatbot Mode
- `CHATBOT_MODE`: `'database'` or `'ai_api'`

### AI API Providers
- `AI_API_PROVIDER`: `'openai'`, `'google'`, `'anthropic'`, or `'custom'`

### Conversation History
- `SAVE_CONVERSATIONS`: `true` or `false`
- `MAX_CONVERSATION_HISTORY`: Number of previous messages to send to AI

### Fallback
- `FALLBACK_TO_DATABASE`: `true` or `false` (falls back to database if AI fails)

## Knowledge Entry Structure

Each knowledge entry has:
- **Role**: User role (admin, finance, studentservices, hod)
- **Keyword**: Trigger word/phrase
- **Title**: Response title
- **Content**: Response content (HTML allowed)
- **Priority**: Matching priority (0-100, higher = more important)
- **Active**: Enable/disable entry

## Example Knowledge Entry

```php
Role: finance
Keyword: invoice
Title: Generate Invoice
Content: <p><strong>How to generate an invoice:</strong></p><ol><li>When application is approved...</li></ol>
Priority: 10
Active: Yes
```

## Testing

1. **Test Database Mode:**
   - Set `CHATBOT_MODE = 'database'`
   - Add knowledge entries via admin interface
   - Test chatbot responses

2. **Test AI API Mode:**
   - Set `CHATBOT_MODE = 'ai_api'`
   - Configure API key
   - Test chatbot responses
   - Verify fallback works if API fails

## Troubleshooting

### Chatbot Not Responding
- Check browser console for errors
- Verify API endpoint is accessible
- Check database connection
- Verify `chatbot_config.php` settings

### AI API Not Working
- Verify API key is correct
- Check API endpoint URL
- Verify curl is enabled in PHP
- Enable fallback to database mode

### Database Errors
- Run `database/create_chatbot_knowledge.php` again
- Check database connection
- Verify tables exist

## Security Notes

1. **API Keys**: Never commit API keys to version control
2. **Input Validation**: All user input is sanitized
3. **Access Control**: Knowledge management requires admin role
4. **Session Security**: Uses PHP sessions for user identification

## Next Steps

1. ✅ Run database setup script
2. ✅ Configure chatbot mode
3. ✅ Add knowledge entries
4. ✅ Test chatbot responses
5. ⏳ (Optional) Set up AI API for advanced responses

## Support

For detailed setup instructions, see `CHATBOT_SETUP_GUIDE.md`

For questions or issues:
- Check conversation history in database
- Review API responses in browser network tab
- Check PHP error logs

