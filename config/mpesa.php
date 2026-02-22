<?php

return [

    'enabled' => env('MPESA_ENABLED', false),

    'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),

    'consumer_key' => env('MPESA_CONSUMER_KEY'),

    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),

    'shortcode' => env('MPESA_SHORTCODE'),

    'passkey' => env('MPESA_PASSKEY'),

    'callback_url' => env('MPESA_CALLBACK_URL', env('APP_URL').'/api/webhooks/mpesa'),

    'timeout_url' => env('MPESA_TIMEOUT_URL', env('APP_URL').'/api/webhooks/mpesa/timeout'),

    'urls' => [
        'sandbox' => [
            'oauth' => 'https://sandbox.safaricom.co.ke/oauth/v1/generate',
            'stk_push' => 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
            'stk_query' => 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query',
        ],
        'production' => [
            'oauth' => 'https://api.safaricom.co.ke/oauth/v1/generate',
            'stk_push' => 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
            'stk_query' => 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query',
        ],
    ],

];
