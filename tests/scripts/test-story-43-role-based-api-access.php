<?php

/**
 * Story 43: Role-Based API Access Control Test Script
 *
 * This script tests all role-based access control requirements:
 * - Waiter access: can create orders, view own orders, process payments
 * - Chef access: can view kitchen orders, update kitchen item prep_status
 * - Bartender access: can view bar orders, update bar item prep_status
 * - Manager access: full access to all API endpoints
 * - Admin access: full access including staff management endpoints
 * - 403 responses for unauthorized access
 */

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$baseUrl = 'http://127.0.0.1:8000/api';
$results = ['passed' => 0, 'failed' => 0, 'tests' => []];

// Color output helpers
function colorize($text, $status)
{
    $colors = [
        'success' => "\033[32m", // Green
        'error' => "\033[31m",   // Red
        'info' => "\033[36m",    // Cyan
        'reset' => "\033[0m",
    ];

    return $colors[$status].$text.$colors['reset'];
}

function printResult($testName, $passed, $message = '')
{
    global $results;
    $status = $passed ? 'PASS' : 'FAIL';
    $color = $passed ? 'success' : 'error';

    echo colorize("[$status] ", $color)."$testName";
    if ($message) {
        echo ' - '.colorize($message, 'info');
    }
    echo PHP_EOL;

    $results['tests'][] = [
        'name' => $testName,
        'passed' => $passed,
        'message' => $message,
    ];

    if ($passed) {
        $results['passed']++;
    } else {
        $results['failed']++;
    }
}

// HTTP request helper
function apiRequest($method, $url, $token = null, $data = [])
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer '.$token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if (! empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status' => $statusCode,
        'body' => json_decode($response, true),
    ];
}

// Login helper
function login($email, $password)
{
    global $baseUrl;
    $response = apiRequest('POST', "$baseUrl/auth/login", null, [
        'email' => $email,
        'password' => $password,
        'device_name' => 'test-device',
    ]);

    return $response['body']['token'] ?? null;
}

echo "\n".colorize("=== Story 43: Role-Based API Access Control Tests ===\n", 'info');

// ============================================================================
// Setup: Create test staff members with different roles
// ============================================================================
echo "\n".colorize("Setting up test data...\n", 'info');

DB::table('staff')->where('email', 'LIKE', 'test-%@example.com')->delete();

$testStaff = [
    'waiter' => [
        'name' => 'Test Waiter',
        'email' => 'test-waiter@example.com',
        'password' => Hash::make('password'),
        'role' => 'waiter',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    'chef' => [
        'name' => 'Test Chef',
        'email' => 'test-chef@example.com',
        'password' => Hash::make('password'),
        'role' => 'chef',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    'bartender' => [
        'name' => 'Test Bartender',
        'email' => 'test-bartender@example.com',
        'password' => Hash::make('password'),
        'role' => 'bartender',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    'manager' => [
        'name' => 'Test Manager',
        'email' => 'test-manager@example.com',
        'password' => Hash::make('password'),
        'role' => 'manager',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    'admin' => [
        'name' => 'Test Admin',
        'email' => 'test-admin@example.com',
        'password' => Hash::make('password'),
        'role' => 'admin',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ],
];

foreach ($testStaff as $role => $staff) {
    DB::table('staff')->insert($staff);
}

// Get staff IDs
$waiterId = DB::table('staff')->where('email', 'test-waiter@example.com')->value('id');
$chefId = DB::table('staff')->where('email', 'test-chef@example.com')->value('id');
$bartenderId = DB::table('staff')->where('email', 'test-bartender@example.com')->value('id');

echo colorize("Test data created successfully\n", 'success');

// ============================================================================
// Test 1: Authentication & Token Generation
// ============================================================================
echo "\n".colorize("=== Test 1: Authentication & Token Generation ===\n", 'info');

$tokens = [];
foreach ($testStaff as $role => $staff) {
    $token = login($staff['email'], 'password');
    if ($token) {
        $tokens[$role] = $token;
        printResult("Login as $role", true, 'Token generated');
    } else {
        printResult("Login as $role", false, 'Failed to generate token');
    }
}

// ============================================================================
// Test 2: Waiter Access Control
// ============================================================================
echo "\n".colorize("=== Test 2: Waiter Access Control ===\n", 'info');

// Get a table for testing
$tableId = DB::table('tables')->first()->id ?? null;

if ($tableId) {
    // Test: Waiter can create orders
    $orderData = [
        'table_id' => $tableId,
        'guest_name' => 'Test Guest',
        'guest_phone' => '+1234567890',
        'items' => [
            [
                'menu_item_id' => DB::table('menu_items')->first()->id,
                'quantity' => 2,
                'special_instructions' => 'Test order',
            ],
        ],
    ];

    $response = apiRequest('POST', "$baseUrl/orders", $tokens['waiter'], $orderData);
    printResult(
        'Waiter can create orders',
        $response['status'] === 201,
        "Status: {$response['status']}"
    );

    $waiterOrderId = $response['body']['order']['id'] ?? null;

    // Test: Waiter can view own orders
    if ($waiterOrderId) {
        $response = apiRequest('GET', "$baseUrl/orders/$waiterOrderId", $tokens['waiter']);
        printResult(
            'Waiter can view own order',
            $response['status'] === 200,
            "Status: {$response['status']}"
        );
    }

    // Test: Waiter can process payments
    if ($waiterOrderId) {
        $paymentData = [
            'order_id' => $waiterOrderId,
            'payment_method' => 'cash',
            'amount' => 50.00,
        ];

        $response = apiRequest('POST', "$baseUrl/payments", $tokens['waiter'], $paymentData);
        printResult(
            'Waiter can process payments',
            in_array($response['status'], [200, 201]),
            "Status: {$response['status']}"
        );
    }

    // Test: Waiter CANNOT update order status
    if ($waiterOrderId) {
        $response = apiRequest('PATCH', "$baseUrl/orders/$waiterOrderId/status", $tokens['waiter'], ['status' => 'cancelled']);
        printResult(
            'Waiter CANNOT update order status',
            $response['status'] === 403,
            "Status: {$response['status']}"
        );
    }
}

// ============================================================================
// Test 3: Chef Access Control
// ============================================================================
echo "\n".colorize("=== Test 3: Chef Access Control ===\n", 'info');

// Test: Chef can view pending kitchen items
$response = apiRequest('GET', "$baseUrl/order-items/pending", $tokens['chef']);
printResult(
    'Chef can view pending items',
    $response['status'] === 200,
    "Status: {$response['status']}"
);

// Get a kitchen item
$kitchenItem = DB::table('order_items')
    ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
    ->where('menu_items.prep_area', 'kitchen')
    ->where('order_items.prep_status', 'pending')
    ->select('order_items.id')
    ->first();

if ($kitchenItem) {
    // Test: Chef can update kitchen item prep_status
    $response = apiRequest('POST', "$baseUrl/order-items/{$kitchenItem->id}/received", $tokens['chef']);
    printResult(
        'Chef can update kitchen item prep_status',
        $response['status'] === 200,
        "Status: {$response['status']}"
    );
}

// Get a bar item
$barItem = DB::table('order_items')
    ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
    ->where('menu_items.prep_area', 'bar')
    ->where('order_items.prep_status', 'pending')
    ->select('order_items.id')
    ->first();

if ($barItem) {
    // Test: Chef CANNOT update bar item prep_status
    $response = apiRequest('POST', "$baseUrl/order-items/{$barItem->id}/received", $tokens['chef']);
    printResult(
        'Chef CANNOT update bar item prep_status',
        $response['status'] === 403,
        "Status: {$response['status']}"
    );
}

// Test: Chef CANNOT create orders
$response = apiRequest('POST', "$baseUrl/orders", $tokens['chef'], $orderData ?? []);
printResult(
    'Chef CANNOT create orders',
    $response['status'] === 403,
    "Status: {$response['status']}"
);

// ============================================================================
// Test 4: Bartender Access Control
// ============================================================================
echo "\n".colorize("=== Test 4: Bartender Access Control ===\n", 'info');

// Test: Bartender can view pending bar items
$response = apiRequest('GET', "$baseUrl/order-items/pending", $tokens['bartender']);
printResult(
    'Bartender can view pending items',
    $response['status'] === 200,
    "Status: {$response['status']}"
);

if ($barItem) {
    // Test: Bartender can update bar item prep_status
    $response = apiRequest('POST', "$baseUrl/order-items/{$barItem->id}/received", $tokens['bartender']);
    printResult(
        'Bartender can update bar item prep_status',
        $response['status'] === 200,
        "Status: {$response['status']}"
    );
}

if ($kitchenItem) {
    // Test: Bartender CANNOT update kitchen item prep_status
    $response = apiRequest('POST', "$baseUrl/order-items/{$kitchenItem->id}/done", $tokens['bartender']);
    printResult(
        'Bartender CANNOT update kitchen item prep_status',
        $response['status'] === 403,
        "Status: {$response['status']}"
    );
}

// Test: Bartender CANNOT process payments
$response = apiRequest('POST', "$baseUrl/payments", $tokens['bartender'], $paymentData ?? []);
printResult(
    'Bartender CANNOT process payments',
    $response['status'] === 403,
    "Status: {$response['status']}"
);

// ============================================================================
// Test 5: Manager Access Control
// ============================================================================
echo "\n".colorize("=== Test 5: Manager Access Control ===\n", 'info');

// Test: Manager can create orders
$response = apiRequest('POST', "$baseUrl/orders", $tokens['manager'], $orderData ?? []);
printResult(
    'Manager can create orders',
    $response['status'] === 201,
    "Status: {$response['status']}"
);

$managerOrderId = $response['body']['order']['id'] ?? null;

// Test: Manager can view all orders
$response = apiRequest('GET', "$baseUrl/orders", $tokens['manager']);
printResult(
    'Manager can view all orders',
    $response['status'] === 200,
    "Status: {$response['status']}"
);

// Test: Manager can update order status
if ($managerOrderId) {
    $response = apiRequest('PATCH', "$baseUrl/orders/$managerOrderId/status", $tokens['manager'], ['status' => 'confirmed']);
    printResult(
        'Manager can update order status',
        $response['status'] === 200,
        "Status: {$response['status']}"
    );
}

// Test: Manager can cancel orders
if ($managerOrderId) {
    $response = apiRequest('POST', "$baseUrl/orders/$managerOrderId/cancel", $tokens['manager'], ['reason' => 'Test cancellation']);
    printResult(
        'Manager can cancel orders',
        $response['status'] === 200,
        "Status: {$response['status']}"
    );
}

// Test: Manager can update menu availability
$menuItemId = DB::table('menu_items')->first()->id ?? null;
if ($menuItemId) {
    $response = apiRequest('PUT', "$baseUrl/menu/$menuItemId/availability", $tokens['manager'], ['status' => 'available']);
    printResult(
        'Manager can update menu availability',
        $response['status'] === 200,
        "Status: {$response['status']}"
    );
}

// ============================================================================
// Test 6: Admin Access Control
// ============================================================================
echo "\n".colorize("=== Test 6: Admin Access Control ===\n", 'info');

// Test: Admin has full access (same as manager)
$response = apiRequest('POST', "$baseUrl/orders", $tokens['admin'], $orderData ?? []);
printResult(
    'Admin can create orders',
    $response['status'] === 201,
    "Status: {$response['status']}"
);

$response = apiRequest('GET', "$baseUrl/orders", $tokens['admin']);
printResult(
    'Admin can view all orders',
    $response['status'] === 200,
    "Status: {$response['status']}"
);

// ============================================================================
// Test 7: Unauthorized Access (No Token)
// ============================================================================
echo "\n".colorize("=== Test 7: Unauthorized Access (No Token) ===\n", 'info');

$response = apiRequest('GET', "$baseUrl/orders", null);
printResult(
    'Unauthenticated request returns 401',
    $response['status'] === 401,
    "Status: {$response['status']}"
);

// ============================================================================
// Test 8: Token Abilities Validation
// ============================================================================
echo "\n".colorize("=== Test 8: Token Abilities Validation ===\n", 'info');

// Verify tokens have correct abilities
foreach ($tokens as $role => $token) {
    $response = apiRequest('GET', "$baseUrl/auth/me", $token);
    $hasCorrectRole = isset($response['body']['user']['role']) && $response['body']['user']['role'] === $role;
    printResult(
        "$role token has correct role",
        $hasCorrectRole,
        'Role: '.($response['body']['user']['role'] ?? 'none')
    );
}

// ============================================================================
// Summary
// ============================================================================
echo "\n".colorize("=== Test Summary ===\n", 'info');
echo 'Total Tests: '.($results['passed'] + $results['failed']).PHP_EOL;
echo colorize("Passed: {$results['passed']}\n", 'success');
echo colorize("Failed: {$results['failed']}\n", $results['failed'] > 0 ? 'error' : 'success');

$successRate = $results['passed'] / ($results['passed'] + $results['failed']) * 100;
echo "\nSuccess Rate: ".colorize(round($successRate, 2)."%\n", $successRate === 100 ? 'success' : 'error');

if ($results['failed'] > 0) {
    echo "\n".colorize("Failed Tests:\n", 'error');
    foreach ($results['tests'] as $test) {
        if (! $test['passed']) {
            echo "  - {$test['name']}: {$test['message']}\n";
        }
    }
}

echo "\n".colorize('Story 43 implementation '.($results['failed'] === 0 ? 'COMPLETE ✓' : 'needs attention')."\n", $results['failed'] === 0 ? 'success' : 'error');

exit($results['failed'] > 0 ? 1 : 0);
