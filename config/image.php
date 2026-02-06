<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | Intervention Image supports "GD Library" and "Imagick" to process images
    | internally. You may choose one of them according to your PHP
    | configuration. By default PHP's "GD Library" implementation is used.
    |
    | Supported: "gd", "imagick"
    |
    */

    'driver' => env('IMAGE_DRIVER', 'gd'),

    /*
    |--------------------------------------------------------------------------
    | Image Cache
    |--------------------------------------------------------------------------
    |
    | Here you may configure the image cache settings. You can enable or
    | disable caching for image manipulation operations.
    |
    */

    'cache' => [
        'enabled' => env('IMAGE_CACHE_ENABLED', true),
        'lifetime' => env('IMAGE_CACHE_LIFETIME', 43200), // 30 days in minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Optimization Settings
    |--------------------------------------------------------------------------
    |
    | Configure default optimization settings for menu item images.
    | These settings help reduce file size and improve performance.
    |
    */

    'menu_items' => [
        'max_width' => 800,
        'max_height' => 600,
        'quality' => 85, // JPEG quality (1-100)
        'thumbnail' => [
            'width' => 200,
            'height' => 200,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME Types
    |--------------------------------------------------------------------------
    |
    | Define which image formats are allowed for upload.
    |
    */

    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum File Size
    |--------------------------------------------------------------------------
    |
    | Maximum allowed file size in kilobytes.
    |
    */

    'max_file_size' => 5120, // 5MB in KB

];
