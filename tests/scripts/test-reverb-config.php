<?php

/**
 * Test script to verify Laravel Reverb configuration
 *
 * This script verifies that all Reverb configuration is correct.
 *
 * Usage: php test-reverb-config.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Laravel Reverb Configuration Test ===\n\n";

$success = true;

// Test 1: Check broadcast connection
echo "1. Checking broadcast connection...\n";
$broadcastConnection = config('broadcasting.default');
if ($broadcastConnection === 'reverb') {
    echo "   ✓ BROADCAST_CONNECTION is set to 'reverb'\n";
} else {
    echo "   ❌ BROADCAST_CONNECTION is '{$broadcastConnection}', expected 'reverb'\n";
    $success = false;
}

// Test 2: Check Reverb configuration
echo "\n2. Checking Reverb configuration...\n";
$reverbKey = config('broadcasting.connections.reverb.key');
$reverbSecret = config('broadcasting.connections.reverb.secret');
$reverbAppId = config('broadcasting.connections.reverb.app_id');

if (! empty($reverbKey)) {
    echo "   ✓ REVERB_APP_KEY is configured\n";
} else {
    echo "   ❌ REVERB_APP_KEY is missing\n";
    $success = false;
}

if (! empty($reverbSecret)) {
    echo "   ✓ REVERB_APP_SECRET is configured\n";
} else {
    echo "   ❌ REVERB_APP_SECRET is missing\n";
    $success = false;
}

if (! empty($reverbAppId)) {
    echo "   ✓ REVERB_APP_ID is configured ({$reverbAppId})\n";
} else {
    echo "   ❌ REVERB_APP_ID is missing\n";
    $success = false;
}

// Test 3: Check Reverb server settings
echo "\n3. Checking Reverb server settings...\n";
$reverbHost = config('broadcasting.connections.reverb.options.host');
$reverbPort = config('broadcasting.connections.reverb.options.port');
$reverbScheme = config('broadcasting.connections.reverb.options.scheme');

echo "   Host: {$reverbHost}\n";
echo "   Port: {$reverbPort}\n";
echo "   Scheme: {$reverbScheme}\n";

// Test 4: Check if events exist
echo "\n4. Checking if events exist...\n";
if (class_exists('App\Events\OrderCreated')) {
    echo "   ✓ OrderCreated event exists\n";
} else {
    echo "   ❌ OrderCreated event not found\n";
    $success = false;
}

if (class_exists('App\Events\OrderStatusUpdated')) {
    echo "   ✓ OrderStatusUpdated event exists\n";
} else {
    echo "   ❌ OrderStatusUpdated event not found\n";
    $success = false;
}

// Test 5: Check if events implement ShouldBroadcast
echo "\n5. Checking if events implement ShouldBroadcast...\n";
$orderCreated = new ReflectionClass('App\Events\OrderCreated');
if ($orderCreated->implementsInterface('Illuminate\Contracts\Broadcasting\ShouldBroadcast')) {
    echo "   ✓ OrderCreated implements ShouldBroadcast\n";
} else {
    echo "   ❌ OrderCreated does not implement ShouldBroadcast\n";
    $success = false;
}

$orderStatusUpdated = new ReflectionClass('App\Events\OrderStatusUpdated');
if ($orderStatusUpdated->implementsInterface('Illuminate\Contracts\Broadcasting\ShouldBroadcast')) {
    echo "   ✓ OrderStatusUpdated implements ShouldBroadcast\n";
} else {
    echo "   ❌ OrderStatusUpdated does not implement ShouldBroadcast\n";
    $success = false;
}

// Test 6: Check if channel routes are configured
echo "\n6. Checking channel routes...\n";
$channelsFile = __DIR__.'/routes/channels.php';
$channelsContent = file_get_contents($channelsFile);

$requiredChannels = ['kitchen', 'bar', 'orders', 'waiter'];
foreach ($requiredChannels as $channel) {
    if (strpos($channelsContent, "'{$channel}") !== false || strpos($channelsContent, "\"{$channel}") !== false) {
        echo "   ✓ '{$channel}' channel is configured\n";
    } else {
        echo "   ❌ '{$channel}' channel not found\n";
        $success = false;
    }
}

// Test 7: Check if Echo is configured
echo "\n7. Checking Laravel Echo configuration...\n";
$echoFile = __DIR__.'/resources/js/echo.js';
if (file_exists($echoFile)) {
    echo "   ✓ echo.js file exists\n";
    $echoContent = file_get_contents($echoFile);
    if (strpos($echoContent, 'reverb') !== false) {
        echo "   ✓ Echo is configured for Reverb\n";
    } else {
        echo "   ⚠ Echo configuration may not be set to Reverb\n";
    }
} else {
    echo "   ❌ echo.js file not found\n";
    $success = false;
}

// Test 8: Check if npm packages are installed
echo "\n8. Checking npm packages...\n";
$packageJson = json_decode(file_get_contents(__DIR__.'/package.json'), true);

if (isset($packageJson['devDependencies']['laravel-echo'])) {
    echo "   ✓ laravel-echo is installed\n";
} else {
    echo "   ❌ laravel-echo is not installed\n";
    $success = false;
}

if (isset($packageJson['devDependencies']['pusher-js'])) {
    echo "   ✓ pusher-js is installed\n";
} else {
    echo "   ❌ pusher-js is not installed\n";
    $success = false;
}

// Summary
echo "\n".str_repeat('=', 50)."\n";
if ($success) {
    echo "✓ All tests passed! Laravel Reverb is properly configured.\n";
    echo "\nTo start the Reverb server, run:\n";
    echo "  php artisan reverb:start\n";
    echo "\nTo test broadcasting, dispatch an OrderCreated event:\n";
    echo "  event(new \\App\\Events\\OrderCreated(\$order));\n";
} else {
    echo "❌ Some tests failed. Please check the configuration above.\n";
    exit(1);
}
