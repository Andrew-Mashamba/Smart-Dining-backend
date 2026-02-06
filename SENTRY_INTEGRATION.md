# Sentry Integration Guide

Sentry is an optional error monitoring service that provides real-time error tracking and reporting.

## Installation

1. Install the Sentry SDK for Laravel:
```bash
composer require sentry/sentry-laravel
```

2. Publish the Sentry configuration:
```bash
php artisan sentry:publish --dsn
```

3. Add your Sentry DSN to `.env`:
```env
SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io/your-project-id
SENTRY_TRACES_SAMPLE_RATE=1.0
```

## Configuration

The Sentry configuration is automatically integrated with Laravel's exception handler. Once installed:

1. All exceptions will be automatically reported to Sentry
2. Critical exceptions (OrderWorkflowException, PaymentException, InventoryException) will be tagged with `critical` severity
3. User context (if authenticated) will be attached to error reports
4. Request data will be included in error reports

## Testing Sentry

To test if Sentry is working, you can trigger a test exception:

```php
// In any controller or route
throw new \Exception('This is a test Sentry error');
```

Check your Sentry dashboard to see if the error was reported.

## Disabling Sentry

To disable Sentry error reporting:

1. Remove or comment out the `SENTRY_LARAVEL_DSN` in `.env`
2. Or set `APP_ENV=local` to disable Sentry in development

## Benefits

- Real-time error alerts
- Error grouping and deduplication
- Performance monitoring
- Release tracking
- User impact analysis
- Integration with Slack, GitHub, and more
