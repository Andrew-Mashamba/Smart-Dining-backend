<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe API Keys
    |--------------------------------------------------------------------------
    |
    | These keys are used to authenticate with the Stripe API.
    | Get your keys from https://dashboard.stripe.com/apikeys
    |
    */
    'public_key' => env('STRIPE_PUBLIC_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | Default currency for Stripe payments.
    |
    */
    'currency' => env('STRIPE_CURRENCY', 'usd'),
];
