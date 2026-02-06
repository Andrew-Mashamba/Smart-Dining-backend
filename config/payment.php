<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Specify the default payment gateway to use
    | Options: 'pesapal', 'mpesa', 'stripe'
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'pesapal'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways Configuration
    |--------------------------------------------------------------------------
    */

    'gateways' => [

        'pesapal' => [
            'enabled' => env('PESAPAL_ENABLED', true),
            'consumer_key' => env('PESAPAL_CONSUMER_KEY'),
            'consumer_secret' => env('PESAPAL_CONSUMER_SECRET'),
            'environment' => env('PESAPAL_ENVIRONMENT', 'sandbox'), // sandbox or live
            'api_url' => env('PESAPAL_ENVIRONMENT', 'sandbox') === 'sandbox'
                ? 'https://cybqa.pesapal.com/pesapalv3'
                : 'https://pay.pesapal.com/v3',
            'callback_url' => env('APP_URL').'/payments/pesapal/callback',
            'ipn_url' => env('APP_URL').'/payments/pesapal/ipn',
        ],

        'mpesa' => [
            'enabled' => env('MPESA_ENABLED', false),
            'consumer_key' => env('MPESA_CONSUMER_KEY'),
            'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
            'shortcode' => env('MPESA_SHORTCODE'),
            'passkey' => env('MPESA_PASSKEY'),
            'environment' => env('MPESA_ENVIRONMENT', 'sandbox'), // sandbox or live
            'callback_url' => env('APP_URL').'/payments/mpesa/callback',
            'timeout_url' => env('APP_URL').'/payments/mpesa/timeout',
        ],

        'stripe' => [
            'enabled' => env('STRIPE_ENABLED', false),
            'public_key' => env('STRIPE_PUBLIC_KEY'),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */

    'currency' => env('PAYMENT_CURRENCY', 'TZS'), // Tanzanian Shilling

    'currency_symbol' => env('PAYMENT_CURRENCY_SYMBOL', 'TZS'),

    /*
    |--------------------------------------------------------------------------
    | Tips Configuration
    |--------------------------------------------------------------------------
    */

    'tips' => [
        'enabled' => env('TIPS_ENABLED', true),
        'suggested_percentages' => [10, 15, 20], // Suggested tip percentages
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Status
    |--------------------------------------------------------------------------
    */

    'statuses' => [
        'pending' => 'pending',
        'processing' => 'processing',
        'completed' => 'completed',
        'failed' => 'failed',
        'cancelled' => 'cancelled',
        'refunded' => 'refunded',
    ],

];
