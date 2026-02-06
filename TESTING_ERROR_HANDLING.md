# Error Handling and Logging Testing Guide

## Overview
This document provides instructions for manually testing the comprehensive error handling and logging system implemented in Story 44.

## Prerequisites
- Laravel application running locally
- Access to the database
- Access to log files in `storage/logs/`

## Testing Routes

**Important:** Test routes are only available in non-production environments. They are automatically disabled when `APP_ENV=production`.

### Web Error Pages

#### Test 404 Error Page
```bash
# Open in browser or use curl
curl http://localhost:8000/test-errors/404
```
**Expected Result:**
- Status: 404
- Page displays "404" error code in `text-gray-900`
- Background: `bg-gray-50`
- Card: `bg-white` with rounded corners
- Message: "Page Not Found" in `text-gray-600`
- "Go to Homepage" and "Go Back" buttons

#### Test 500 Error Page
```bash
curl http://localhost:8000/test-errors/500
```
**Expected Result:**
- Status: 500
- Page displays "500" error code in `text-gray-900`
- Background: `bg-gray-50`
- Card: `bg-white` with rounded corners
- Message: "Server Error" in `text-gray-600`
- "Go to Homepage" and "Reload Page" buttons

### API Error Responses

#### Test API 404 Error
```bash
curl -H "Accept: application/json" http://localhost:8000/api/test-errors/404
```
**Expected Result:**
```json
{
  "status": "error",
  "message": "Resource not found",
  "errors": null
}
```

#### Test API 500 Error
```bash
curl -H "Accept: application/json" http://localhost:8000/api/test-errors/500
```
**Expected Result:**
```json
{
  "status": "error",
  "message": "Test 500 error for API",
  "errors": null
}
```

#### Test Validation Error (422)
```bash
curl -X POST \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  http://localhost:8000/api/test-errors/validation
```
**Expected Result:**
```json
{
  "status": "error",
  "message": "The email field is required. (and 2 more errors)",
  "errors": {
    "email": ["The email field is required."],
    "name": ["The name field is required."],
    "age": ["The age field is required."]
  }
}
```
Status: 422

#### Test Unauthorized Error (403)
```bash
curl -H "Accept: application/json" http://localhost:8000/api/test-errors/unauthorized
```
**Expected Result:**
```json
{
  "status": "error",
  "message": "Unauthorized API access",
  "errors": null
}
```
Status: 403

### Critical Exception Logging

#### Test OrderWorkflowException
```bash
curl -H "Accept: application/json" http://localhost:8000/api/test-errors/order-workflow
```
**Verification:**
1. Check `storage/logs/critical.log` for entry
2. Should contain: "OrderWorkflowException"
3. Should contain: "Invalid status transition from 'pending' to 'completed'"
4. Should include file, line, and stack trace

#### Test PaymentException
```bash
curl -H "Accept: application/json" http://localhost:8000/api/test-errors/payment
```
**Verification:**
1. Check `storage/logs/critical.log` for entry
2. Should contain: "PaymentException"
3. Should contain: "Payment processing failed"

#### Test InventoryException
```bash
curl -H "Accept: application/json" http://localhost:8000/api/test-errors/inventory
```
**Verification:**
1. Check `storage/logs/critical.log` for entry
2. Should contain: "InventoryException"
3. Should contain: "Insufficient stock"

### Database Logging

#### Verify Database Logs
```bash
# Access the database and query error_logs table
sqlite3 database/database.sqlite "SELECT * FROM error_logs ORDER BY created_at DESC LIMIT 10;"
```
**Expected Result:**
- Records should exist in `error_logs` table
- Each record should have: `message`, `level`, `context` (JSON), `created_at`

#### View Error Logs via API (Development only)
```bash
curl http://localhost:8000/test-errors/logs
```
**Expected Result:**
- JSON array of the last 50 error logs
- Each log should have proper structure

### API Request Logging

#### Test API Request Logging
```bash
# Create a user token first, then make an API request
curl -H "Accept: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/menu
```

**Verification:**
1. Check `storage/logs/laravel.log`
2. Should contain entry like:
```
[YYYY-MM-DD HH:MM:SS] local.INFO: API Request {"method":"GET","endpoint":"http://localhost:8000/api/menu","user_id":1,"user_email":"user@example.com","ip_address":"127.0.0.1","timestamp":"YYYY-MM-DD HH:MM:SS","user_agent":"curl/7.x.x"}
```
3. Should also contain corresponding API Response entry

## Log Files

### File Locations
- **Main Log:** `storage/logs/laravel.log` - All general logs
- **Critical Log:** `storage/logs/critical.log` - Critical exceptions only (30 days retention)
- **Database:** `error_logs` table - All logs stored in database

### Log Channels Configuration

#### Stack Channel (Default)
- Combines: `single` and `database` channels
- Configured in `.env`: `LOG_STACK=single,database`

#### Daily Channel
- Rotates logs daily
- Retention: 14 days (configurable via `LOG_DAILY_DAYS`)

#### Critical Channel
- Only logs critical level errors
- Retention: 30 days
- Used for: OrderWorkflowException, PaymentException, InventoryException

#### Database Channel
- Stores logs in `error_logs` table
- Handles all log levels
- Uses custom `DatabaseHandler` class

#### Slack Channel (Optional)
- Requires webhook URL: `LOG_SLACK_WEBHOOK_URL`
- Default username: "Laravel Log"
- Default emoji: ":boom:"
- Only logs critical level by default

## Environment Variables

Add these to your `.env` file:

```bash
# Logging Configuration
LOG_CHANNEL=stack
LOG_STACK=single,database
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug
LOG_DAILY_DAYS=14

# Slack Logging (Optional)
LOG_SLACK_WEBHOOK_URL=
LOG_SLACK_USERNAME="Laravel Log"
LOG_SLACK_EMOJI=":boom:"

# Sentry Error Monitoring (Optional)
SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=1.0
```

## Automated Testing

Run the comprehensive test suite:

```bash
php artisan test --filter ErrorHandlingTest
```

**Test Coverage:**
- ✓ 404 error page renders correctly
- ✓ 500 error page renders correctly
- ✓ API 404 returns JSON
- ✓ API 500 returns JSON
- ✓ Validation error returns 422 with errors
- ✓ API unauthorized returns JSON
- ✓ OrderWorkflowException logs to critical
- ✓ PaymentException logs to critical
- ✓ InventoryException logs to critical
- ✓ Errors logged to database
- ✓ API requests are logged
- ✓ Log channels configured correctly
- ✓ ErrorLog model configuration
- ✓ ErrorLog scopes

## Production Considerations

### Before Deploying to Production:

1. **Remove Test Routes:** Test routes are automatically disabled in production, but verify:
   ```php
   // In routes/web.php and routes/api.php
   if (config('app.env') !== 'production') {
       // Test routes here
   }
   ```

2. **Set Environment:**
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   LOG_LEVEL=error
   ```

3. **Configure Sentry (Optional but Recommended):**
   - Sign up at https://sentry.io
   - Get your DSN
   - Add to `.env`: `SENTRY_LARAVEL_DSN=your_dsn_here`
   - Install package: `composer require sentry/sentry-laravel`

4. **Configure Slack Alerts (Optional):**
   - Create Slack webhook
   - Add to `.env`: `LOG_SLACK_WEBHOOK_URL=your_webhook_url`

5. **Set Up Log Rotation:**
   - Use daily driver
   - Set appropriate retention days
   - Consider external log management (e.g., Papertrail, Loggly)

6. **Database Cleanup:**
   - Set up scheduled task to clean old error logs:
   ```php
   // In app/Console/Kernel.php
   $schedule->command('error-logs:cleanup')->daily();
   ```

## Troubleshooting

### Logs Not Appearing in Database
- Check `LOG_STACK` includes `database` channel
- Verify `error_logs` table exists: `php artisan migrate`
- Check database connection
- Review `app/Logging/DatabaseHandler.php` for errors

### Critical Logs Not in critical.log
- Verify exception is one of: OrderWorkflowException, PaymentException, InventoryException
- Check file permissions on `storage/logs/` directory
- Review `bootstrap/app.php` exception reporting configuration

### API Requests Not Being Logged
- Verify middleware is registered in `bootstrap/app.php`
- Check `LogApiRequests` middleware is applied to API routes
- Review log level - should be `info` or lower

## Success Criteria Checklist

- [x] Custom exception handler with render() method in bootstrap/app.php
- [x] Custom error pages (404.blade.php, 500.blade.php) with monochrome design
- [x] Error page styling: bg-gray-50, bg-white card, text-gray-900 code, text-gray-600 message
- [x] API error responses in JSON format with status, message, errors fields
- [x] Validation errors return 422 with field-specific messages
- [x] Log channels: daily, slack (optional), database, critical
- [x] Critical exceptions logged to 'critical' channel
- [x] API request logging middleware tracks method, endpoint, user, timestamp
- [x] Database error_logs table with message, level, context (json), created_at
- [x] Sentry integration configured (optional, DSN in .env.example)
- [x] Comprehensive tests verify all error scenarios
- [x] All tests passing (14 tests, 62 assertions)
