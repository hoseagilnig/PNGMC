# Chatbot Setup Guide

This guide explains how to set up and configure the chatbot system with database storage and AI API integration.

## Features

1. **Database-Backed Knowledge Base**: Store chatbot responses in the database
2. **AI API Integration**: Connect to OpenAI, Google Gemini, Anthropic Claude, or custom APIs
3. **Automatic Fallback**: Falls back to database if AI API fails
4. **Conversation History**: Saves conversation history for context
5. **Role-Based Responses**: Different responses for different user roles

## Setup Steps

### 1. Create Database Tables

Run the database creation script:

```bash
php database/create_chatbot_knowledge.php
```

This will create:
- `chatbot_knowledge` table: Stores knowledge entries
- `chatbot_conversations` table: Stores conversation history

### 2. Configure Chatbot Mode

Edit `pages/includes/chatbot_config.php`:

#### Option A: Database Mode (Default)
```php
define('CHATBOT_MODE', 'database');
```

#### Option B: AI API Mode
```php
define('CHATBOT_MODE', 'ai_api');
define('AI_API_PROVIDER', 'openai'); // or 'google', 'anthropic', 'custom'
```

### 3. Configure AI API (If Using AI Mode)

#### OpenAI Configuration
```php
define('OPENAI_API_KEY', 'your-api-key-here');
define('OPENAI_MODEL', 'gpt-3.5-turbo');
define('OPENAI_MAX_TOKENS', 500);
```

#### Google Gemini Configuration
```php
define('AI_API_PROVIDER', 'google');
define('GEMINI_API_KEY', 'your-api-key-here');
define('GEMINI_MODEL', 'gemini-pro');
```

#### Anthropic Claude Configuration
```php
define('AI_API_PROVIDER', 'anthropic');
define('ANTHROPIC_API_KEY', 'your-api-key-here');
define('ANTHROPIC_MODEL', 'claude-3-haiku-20240307');
```

#### Custom API Configuration
```php
define('AI_API_PROVIDER', 'custom');
define('CUSTOM_API_URL', 'https://your-api-endpoint.com/chat');
define('CUSTOM_API_KEY', 'your-api-key-here');
```

### 4. Manage Knowledge Base

Access the knowledge management page:
- URL: `pages/manage_chatbot_knowledge.php`
- Requires admin role

From here you can:
- Add new knowledge entries
- Edit existing entries
- Delete entries
- Set priority levels
- Activate/deactivate entries

## Adding Knowledge Entries

### Via Admin Interface

1. Go to `pages/manage_chatbot_knowledge.php`
2. Fill in the form:
   - **Role**: Select user role (admin, finance, studentservices, hod)
   - **Keyword**: Word or phrase that triggers this response
   - **Title**: Response title
   - **Content**: Response content (HTML allowed)
   - **Priority**: Higher priority entries are matched first (0-100)

### Via Database

```sql
INSERT INTO chatbot_knowledge (role, keyword, title, content, priority) 
VALUES ('finance', 'invoice', 'Generate Invoice', '<p>How to generate...</p>', 10);
```

## How It Works

### Database Mode

1. User sends a message
2. System searches `chatbot_knowledge` table for matching keywords
3. Returns the best match based on:
   - Role match
   - Keyword match
   - Priority level

### AI API Mode

1. User sends a message
2. System calls the configured AI API with:
   - System context (role, system description)
   - Conversation history (if enabled)
   - User message
3. AI generates a response
4. If AI fails, falls back to database mode

### Fallback Behavior

- If AI API fails → Falls back to database
- If database has no match → Returns default response
- All conversations are saved (if enabled)

## Configuration Options

### Conversation History

```php
define('SAVE_CONVERSATIONS', true); // Save conversations to database
define('MAX_CONVERSATION_HISTORY', 10); // Number of previous messages to send to AI
```

### System Context

Edit `CHATBOT_SYSTEM_CONTEXT` in `chatbot_config.php` to customize the AI's behavior:

```php
define('CHATBOT_SYSTEM_CONTEXT', 'You are a helpful assistant for the PNG Maritime College Student Management System...');
```

## API Endpoint

The chatbot uses the API endpoint: `pages/api/chatbot_query.php`

### Request Format
```json
{
    "message": "How do I generate an invoice?"
}
```

### Response Format
```json
{
    "success": true,
    "title": "Generate Invoice",
    "content": "<p>How to generate...</p>",
    "source": "database" // or "ai_api" or "fallback"
}
```

## Best Practices

1. **Keywords**: Use specific, unique keywords for better matching
2. **Priority**: Set higher priority for more important/common responses
3. **Content**: Use HTML formatting for better readability
4. **Testing**: Test both database and AI modes
5. **Monitoring**: Check conversation history to improve responses

## Troubleshooting

### Chatbot Not Responding

1. Check browser console for errors
2. Verify API endpoint is accessible: `pages/api/chatbot_query.php`
3. Check database connection
4. Verify chatbot_config.php settings

### AI API Not Working

1. Verify API key is correct
2. Check API endpoint URL
3. Verify curl is enabled in PHP
4. Check API response in browser network tab
5. Enable fallback to database mode

### Database Errors

1. Run `database/create_chatbot_knowledge.php` again
2. Check database connection in `db_config.php`
3. Verify table exists: `SHOW TABLES LIKE 'chatbot_knowledge';`

## Security Notes

1. **API Keys**: Never commit API keys to version control
2. **Input Validation**: All user input is sanitized
3. **Access Control**: Knowledge management requires admin role
4. **Session Security**: Uses PHP sessions for user identification

## Example Knowledge Entries

### Finance Role - Invoice
```php
Role: finance
Keyword: invoice
Title: Generate Invoice
Content: <p><strong>How to generate an invoice:</strong></p><ol><li>When application is approved...</li></ol>
Priority: 10
```

### Student Services - Enrollment
```php
Role: studentservices
Keyword: enroll
Title: Enroll Student
Content: <p><strong>Enrollment Process:</strong></p><ol><li>Application must be accepted...</li></ol>
Priority: 10
```

## Next Steps

1. Run the database setup script
2. Configure chatbot mode in `chatbot_config.php`
3. Add knowledge entries via admin interface
4. Test chatbot responses
5. (Optional) Set up AI API for advanced responses

For questions or issues, check the conversation history in the database to see what users are asking about.

