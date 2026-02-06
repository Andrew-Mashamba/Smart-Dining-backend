<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure WhatsApp Business Cloud API settings for sending messages
    | and handling webhooks.
    |
    */

    'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0'),

    'access_token' => env('WHATSAPP_API_TOKEN'),

    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),

    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),

    'webhook_secret' => env('WHATSAPP_VERIFY_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Message Templates
    |--------------------------------------------------------------------------
    |
    | Pre-approved WhatsApp message template names
    |
    */

    'templates' => [
        'welcome' => env('WHATSAPP_TEMPLATE_WELCOME', 'welcome_message'),
        'order_received' => env('WHATSAPP_TEMPLATE_ORDER_RECEIVED', 'order_received'),
        'order_ready' => env('WHATSAPP_TEMPLATE_ORDER_READY', 'order_ready'),
        'bill_summary' => env('WHATSAPP_TEMPLATE_BILL_SUMMARY', 'bill_summary'),
        'thank_you' => env('WHATSAPP_TEMPLATE_THANK_YOU', 'thank_you'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    |
    | Guest session timeout and state management
    |
    */

    'session_timeout' => env('WHATSAPP_SESSION_TIMEOUT', 3600), // 1 hour in seconds

    'max_order_items' => env('WHATSAPP_MAX_ORDER_ITEMS', 20),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Prevent abuse with rate limiting
    |
    */

    'rate_limit' => [
        'enabled' => env('WHATSAPP_RATE_LIMIT_ENABLED', true),
        'max_messages_per_minute' => env('WHATSAPP_RATE_LIMIT_PER_MINUTE', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable/disable logging of WhatsApp messages
    |
    */

    'log_messages' => env('WHATSAPP_LOG_MESSAGES', true),

];
