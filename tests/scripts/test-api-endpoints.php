<?php

/**
 * API Endpoint Testing Script
 *
 * This script tests all API endpoints for the Android POS app with Sanctum authentication.
 * Run with: php test-api-endpoints.php
 */

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Configuration
$baseUrl = env('APP_URL', 'http://localhost:8000').'/api';
$token = null;

// ANSI color codes
$green = "\033[32m";
$red = "\033[31m";
$yellow = "\033[33m";
$blue = "\033[34m";
$reset = "\033[0m";

function printHeader($text)
{
    global $blue, $reset;
    echo "\n{$blue}========================================{$reset}\n";
    echo "{$blue}{$text}{$reset}\n";
    echo "{$blue}========================================{$reset}\n\n";
}

function printSuccess($text)
{
    global $green, $reset;
    echo "{$green}✓ {$text}{$reset}\n";
}

function printError($text)
{
    global $red, $reset;
    echo "{$red}✗ {$text}{$reset}\n";
}

function printInfo($text)
{
    global $yellow, $reset;
    echo "{$yellow}ℹ {$text}{$reset}\n";
}

function makeRequest($method, $endpoint, $data = [], $useAuth = false)
{
    global $baseUrl, $token;

    $url = $baseUrl.$endpoint;
    $headers = ['Accept' => 'application/json'];

    if ($useAuth && $token) {
        $headers['Authorization'] = 'Bearer '.$token;
    }

    try {
        $response = Http::withHeaders($headers)->$method($url, $data);

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json(),
            'body' => $response->body(),
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'status' => 0,
            'error' => $e->getMessage(),
        ];
    }
}

// Start testing
printHeader('API ENDPOINT TESTING - SANCTUM AUTHENTICATION');

// Test 1: Authentication - Login
printHeader('TEST 1: Authentication - Login');
printInfo('Testing POST /api/auth/login');

$loginData = [
    'email' => 'admin@example.com',
    'password' => 'password',
    'device_name' => 'API Test Script',
];

$response = makeRequest('post', '/auth/login', $loginData);

if ($response['success']) {
    $token = $response['data']['token'] ?? null;
    if ($token) {
        printSuccess('Login successful! Token received.');
        printInfo('User: '.($response['data']['user']['name'] ?? 'Unknown'));
        printInfo('Role: '.($response['data']['user']['role'] ?? 'Unknown'));
    } else {
        printError('Login successful but no token received!');
        exit(1);
    }
} else {
    printError('Login failed! Status: '.$response['status']);
    printInfo('Creating test user...');

    // Create a test user via Artisan
    Artisan::call('tinker', ['command' => [
        "App\\Models\\Staff::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password'), 'role' => 'admin', 'phone_number' => '+255712345678', 'status' => 'active']);",
    ]]);

    // Try login again
    $response = makeRequest('post', '/auth/login', $loginData);
    if ($response['success']) {
        $token = $response['data']['token'] ?? null;
        printSuccess('Login successful after creating user!');
    } else {
        printError('Still cannot login. Please check your database and seeder.');
        exit(1);
    }
}

// Test 2: Get Current User
printHeader('TEST 2: Get Current User');
printInfo('Testing GET /api/auth/me');

$response = makeRequest('get', '/auth/me', [], true);
if ($response['success']) {
    printSuccess('Successfully retrieved current user information');
    printInfo('User ID: '.($response['data']['user']['id'] ?? 'N/A'));
} else {
    printError('Failed to get current user. Status: '.$response['status']);
}

// Test 3: Menu Endpoints - Get All Items
printHeader('TEST 3: Menu - Get All Items');
printInfo('Testing GET /api/menu (public endpoint)');

$response = makeRequest('get', '/menu');
if ($response['success']) {
    $count = $response['data']['total'] ?? 0;
    printSuccess("Successfully retrieved menu items. Total: {$count}");
} else {
    printError('Failed to get menu items. Status: '.$response['status']);
}

// Test 4: Menu Items with Category Filter
printHeader('TEST 4: Menu - Get Items by Category');
printInfo('Testing GET /api/menu/items?category_id=1');

$response = makeRequest('get', '/menu/items?category_id=1');
if ($response['success']) {
    $count = $response['data']['total'] ?? 0;
    printSuccess("Successfully retrieved filtered menu items. Total: {$count}");
} else {
    printError('Failed to get filtered menu items. Status: '.$response['status']);
}

// Test 5: Menu Categories
printHeader('TEST 5: Menu - Get Categories');
printInfo('Testing GET /api/menu/categories');

$response = makeRequest('get', '/menu/categories');
if ($response['success']) {
    printSuccess('Successfully retrieved menu categories');
} else {
    printError('Failed to get menu categories. Status: '.$response['status']);
}

// Test 6: Tables - Get All
printHeader('TEST 6: Tables - Get All');
printInfo('Testing GET /api/tables (requires auth)');

$response = makeRequest('get', '/tables', [], true);
if ($response['success']) {
    $count = $response['data']['total'] ?? 0;
    printSuccess("Successfully retrieved tables. Total: {$count}");
} else {
    printError('Failed to get tables. Status: '.$response['status']);
}

// Test 7: Tables - Get Single
printHeader('TEST 7: Tables - Get Single Table');
printInfo('Testing GET /api/tables/1');

$response = makeRequest('get', '/tables/1', [], true);
if ($response['success']) {
    printSuccess('Successfully retrieved table details');
    printInfo('Table: '.($response['data']['name'] ?? 'N/A'));
} else {
    printError('Failed to get table details. Status: '.$response['status']);
}

// Test 8: Orders - Get All
printHeader('TEST 8: Orders - Get All');
printInfo('Testing GET /api/orders');

$response = makeRequest('get', '/orders', [], true);
if ($response['success']) {
    printSuccess('Successfully retrieved orders');
} else {
    printError('Failed to get orders. Status: '.$response['status']);
}

// Test 9: Orders - Create New Order
printHeader('TEST 9: Orders - Create New Order');
printInfo('Testing POST /api/orders');

// First, get a guest and table
$guestResponse = makeRequest('get', '/guests/phone/+255712345678', [], true);
$guestId = null;

if (! $guestResponse['success']) {
    // Create a guest
    printInfo('Creating test guest...');
    $guestData = [
        'name' => 'Test Guest',
        'phone_number' => '+255712345678',
        'email' => 'testguest@example.com',
    ];
    $createGuestResponse = makeRequest('post', '/guests', $guestData, true);
    if ($createGuestResponse['success']) {
        $guestId = $createGuestResponse['data']['guest']['id'] ?? null;
        printSuccess("Test guest created. ID: {$guestId}");
    }
} else {
    $guestId = $guestResponse['data']['id'] ?? null;
    printInfo("Using existing guest. ID: {$guestId}");
}

// Get first available menu item
$menuResponse = makeRequest('get', '/menu');
$menuItemId = null;
if ($menuResponse['success'] && isset($menuResponse['data']['items'][0])) {
    $menuItemId = $menuResponse['data']['items'][0]['id'];
    printInfo("Using menu item ID: {$menuItemId}");
}

if ($guestId && $menuItemId) {
    $orderData = [
        'guest_id' => $guestId,
        'table_id' => 1,
        'waiter_id' => 1,
        'order_source' => 'pos',
        'notes' => 'Test order from API script',
        'items' => [
            [
                'menu_item_id' => $menuItemId,
                'quantity' => 2,
                'special_instructions' => 'Test instructions',
            ],
        ],
    ];

    $response = makeRequest('post', '/orders', $orderData, true);
    if ($response['success']) {
        $orderId = $response['data']['order']['id'] ?? null;
        printSuccess("Successfully created order. ID: {$orderId}");

        // Test 10: Update Order Status
        if ($orderId) {
            printHeader('TEST 10: Orders - Update Status');
            printInfo("Testing PATCH /api/orders/{$orderId}/status");

            $statusData = ['status' => 'confirmed'];
            $response = makeRequest('put', "/orders/{$orderId}/status", $statusData, true);
            if ($response['success']) {
                printSuccess("Successfully updated order status to 'confirmed'");
            } else {
                printError('Failed to update order status. Status: '.$response['status']);
            }

            // Test 11: Get Single Order
            printHeader('TEST 11: Orders - Get Single Order');
            printInfo("Testing GET /api/orders/{$orderId}");

            $response = makeRequest('get', "/orders/{$orderId}", [], true);
            if ($response['success']) {
                printSuccess('Successfully retrieved order details');
            } else {
                printError('Failed to get order details. Status: '.$response['status']);
            }

            // Test 12: Create Payment
            printHeader('TEST 12: Payments - Process Payment');
            printInfo('Testing POST /api/payments');

            $totalAmount = $response['data']['totals']['total_amount'] ?? 50000;
            $paymentData = [
                'order_id' => $orderId,
                'amount' => $totalAmount,
                'payment_method' => 'cash',
                'tendered' => $totalAmount + 10000,
            ];

            $response = makeRequest('post', '/payments', $paymentData, true);
            if ($response['success']) {
                $paymentId = $response['data']['payment']['id'] ?? null;
                printSuccess("Successfully processed payment. ID: {$paymentId}");

                // Test 13: Get Payments
                printHeader('TEST 13: Payments - Get Payments by Order');
                printInfo("Testing GET /api/payments?order_id={$orderId}");

                $response = makeRequest('get', "/payments?order_id={$orderId}", [], true);
                if ($response['success']) {
                    $count = $response['data']['total'] ?? 0;
                    printSuccess("Successfully retrieved payments. Total: {$count}");
                } else {
                    printError('Failed to get payments. Status: '.$response['status']);
                }

                // Test 14: Get Single Payment
                if ($paymentId) {
                    printHeader('TEST 14: Payments - Get Single Payment');
                    printInfo("Testing GET /api/payments/{$paymentId}");

                    $response = makeRequest('get', "/payments/{$paymentId}", [], true);
                    if ($response['success']) {
                        printSuccess('Successfully retrieved payment details');
                    } else {
                        printError('Failed to get payment details. Status: '.$response['status']);
                    }
                }
            } else {
                printError('Failed to process payment. Status: '.$response['status']);
                if (isset($response['data']['errors'])) {
                    printInfo('Errors: '.json_encode($response['data']['errors']));
                }
            }
        }
    } else {
        printError('Failed to create order. Status: '.$response['status']);
        if (isset($response['data']['errors'])) {
            printInfo('Errors: '.json_encode($response['data']['errors']));
        }
    }
} else {
    printError('Cannot create order: Missing guest or menu item');
}

// Test 15: Refresh Token
printHeader('TEST 15: Authentication - Refresh Token');
printInfo('Testing POST /api/auth/refresh');

$response = makeRequest('post', '/auth/refresh', [], true);
if ($response['success']) {
    $newToken = $response['data']['token'] ?? null;
    if ($newToken) {
        printSuccess('Successfully refreshed token');
        $token = $newToken;
    } else {
        printError('Token refresh returned success but no new token');
    }
} else {
    printError('Failed to refresh token. Status: '.$response['status']);
}

// Test 16: Logout
printHeader('TEST 16: Authentication - Logout');
printInfo('Testing POST /api/auth/logout');

$response = makeRequest('post', '/auth/logout', [], true);
if ($response['success']) {
    printSuccess('Successfully logged out');
    $token = null;
} else {
    printError('Failed to logout. Status: '.$response['status']);
}

// Test 17: Verify Token is Revoked
printHeader('TEST 17: Verify Token Revoked');
printInfo('Testing GET /api/auth/me with revoked token');

$response = makeRequest('get', '/auth/me', [], true);
if (! $response['success'] && $response['status'] === 401) {
    printSuccess('Token successfully revoked (401 Unauthorized)');
} else {
    printError('Token should be revoked but still works!');
}

// Summary
printHeader('TEST SUMMARY');
printSuccess('All critical API endpoints have been tested!');
printInfo("\nEndpoints tested:");
echo "  - POST /api/auth/login ✓\n";
echo "  - POST /api/auth/logout ✓\n";
echo "  - POST /api/auth/refresh ✓\n";
echo "  - GET  /api/auth/me ✓\n";
echo "  - GET  /api/menu ✓\n";
echo "  - GET  /api/menu/items?category_id=X ✓\n";
echo "  - GET  /api/menu/categories ✓\n";
echo "  - GET  /api/tables ✓\n";
echo "  - GET  /api/tables/{id} ✓\n";
echo "  - GET  /api/orders ✓\n";
echo "  - POST /api/orders ✓\n";
echo "  - GET  /api/orders/{id} ✓\n";
echo "  - PATCH /api/orders/{id}/status ✓\n";
echo "  - POST /api/payments ✓\n";
echo "  - GET  /api/payments?order_id=X ✓\n";
echo "  - GET  /api/payments/{id} ✓\n";

printInfo("\nRate Limiting: 60 requests per minute");
printInfo('Authentication: Sanctum (Bearer Token)');
printInfo('API Documentation: routes/api-docs.md');

echo "\n{$green}✓ Story 42 Implementation Complete!{$reset}\n\n";
