#!/usr/bin/env php
<?php

/**
 * Story 24: Laravel Reverb WebSocket Setup - Comprehensive Test Script
 *
 * This script tests all acceptance criteria for Story 24:
 * 1. Reverb package installation
 * 2. Configuration files
 * 3. Environment variables
 * 4. Broadcasting configuration
 * 5. Event classes (OrderCreated, OrderStatusUpdated)
 * 6. Channel authorization
 * 7. JavaScript dependencies
 * 8. Echo configuration
 * 9. Event broadcasting
 */

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\Event;

define('LARAVEL_START', microtime(true));

// Require the Composer autoloader
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ANSI color codes for output
$colors = [
    'green' => "\033[0;32m",
    'red' => "\033[0;31m",
    'yellow' => "\033[1;33m",
    'blue' => "\033[0;34m",
    'cyan' => "\033[0;36m",
    'reset' => "\033[0m",
];

function colorize($text, $color)
{
    global $colors;

    return $colors[$color].$text.$colors['reset'];
}

function testResult($name, $passed, $details = '')
{
    $status = $passed ? colorize('✓ PASS', 'green') : colorize('✗ FAIL', 'red');
    echo sprintf("%-60s %s\n", $name, $status);
    if ($details) {
        echo '  '.colorize($details, 'cyan')."\n";
    }

    return $passed;
}

echo colorize("\n╔════════════════════════════════════════════════════════════════╗\n", 'blue');
echo colorize("║     Story 24: Laravel Reverb WebSocket Setup - Test Suite     ║\n", 'blue');
echo colorize("╚════════════════════════════════════════════════════════════════╝\n\n", 'blue');

$results = [];

// Test 1: Verify Laravel Reverb package is installed
echo colorize("1. Package Installation\n", 'yellow');
echo colorize(str_repeat('─', 70)."\n", 'yellow');

$composerJson = json_decode(file_get_contents(__DIR__.'/composer.json'), true);
$results[] = testResult(
    'Laravel Reverb package installed',
    isset($composerJson['require']['laravel/reverb']),
    'Version: '.($composerJson['require']['laravel/reverb'] ?? 'Not found')
);

// Test 2: Verify configuration files exist
echo "\n".colorize("2. Configuration Files\n", 'yellow');
echo colorize(str_repeat('─', 70)."\n", 'yellow');

$results[] = testResult(
    'config/reverb.php exists',
    file_exists(__DIR__.'/config/reverb.php')
);

$results[] = testResult(
    'config/broadcasting.php exists',
    file_exists(__DIR__.'/config/broadcasting.php')
);

// Test 3: Verify environment variables
echo "\n".colorize("3. Environment Variables\n", 'yellow');
echo colorize(str_repeat('─', 70)."\n", 'yellow');

$results[] = testResult(
    'BROADCAST_CONNECTION=reverb',
    env('BROADCAST_CONNECTION') === 'reverb',
    'Value: '.env('BROADCAST_CONNECTION')
);

$results[] = testResult(
    'REVERB_APP_ID is set',
    ! empty(env('REVERB_APP_ID')),
    'Value: '.env('REVERB_APP_ID')
);

$results[] = testResult(
    'REVERB_APP_KEY is set',
    ! empty(env('REVERB_APP_KEY')),
    'Value: '.env('REVERB_APP_KEY')
);

$results[] = testResult(
    'REVERB_APP_SECRET is set',
    ! empty(env('REVERB_APP_SECRET')),
    'Value: '.substr(env('REVERB_APP_SECRET'), 0, 5).'...'
);

// Test 4: Verify broadcasting configuration
echo "\n".colorize("4. Broadcasting Configuration\n", 'yellow');
echo colorize(str_repeat('─', 70)."\n", 'yellow');

$broadcastConfig = config('broadcasting.connections.reverb');
$results[] = testResult(
    'Reverb connection configured',
    ! empty($broadcastConfig),
    'Driver: '.($broadcastConfig['driver'] ?? 'N/A')
);

$results[] = testResult(
    'Reverb driver is set',
    ($broadcastConfig['driver'] ?? null) === 'reverb'
);

$results[] = testResult(
    'Reverb key configured',
    ! empty($broadcastConfig['key'])
);

$results[] = testResult(
    'Reverb secret configured',
    ! empty($broadcastConfig['secret'])
);

$results[] = testResult(
    'Reverb app_id configured',
    ! empty($broadcastConfig['app_id'])
);

// Test 5: Verify Event classes exist
echo "\n".colorize("5. Event Classes\n", 'yellow');
echo colorize(str_repeat('─', 70)."\n", 'yellow');

$results[] = testResult(
    'OrderCreated event exists',
    class_exists(OrderCreated::class)
);

$results[] = testResult(
    'OrderStatusUpdated event exists',
    class_exists(OrderStatusUpdated::class)
);

// Test 6: Verify events implement ShouldBroadcast
$orderCreatedImplements = class_implements(OrderCreated::class);
$results[] = testResult(
    'OrderCreated implements ShouldBroadcast',
    in_array('Illuminate\Contracts\Broadcasting\ShouldBroadcast', $orderCreatedImplements)
);

$orderStatusImplements = class_implements(OrderStatusUpdated::class);
$results[] = testResult(
    'OrderStatusUpdated implements ShouldBroadcast',
    in_array('Illuminate\Contracts\Broadcasting\ShouldBroadcast', $orderStatusImplements)
);

// Test 7: Verify broadcastOn method exists
$results[] = testResult(
    'OrderCreated has broadcastOn method',
    method_exists(OrderCreated::class, 'broadcastOn')
);

$results[] = testResult(
    'OrderStatusUpdated has broadcastOn method',
    method_exists(OrderStatusUpdated::class, 'broadcastOn')
);

// Test 8: Verify channel routes
echo "\n".colorize("6. Channel Routes\n", 'yellow');
echo colorize(str_repeat('─', 70)."\n", 'yellow');

$channelsFile = __DIR__.'/routes/channels.php';
$results[] = testResult(
    'routes/channels.php exists',
    file_exists($channelsFile)
);

$channelsContent = file_get_contents($channelsFile);
$results[] = testResult(
    'Kitchen channel defined',
    str_contains($channelsContent, "Broadcast::channel('kitchen'")
);

$results[] = testResult(
    'Bar channel defined',
    str_contains($channelsContent, "Broadcast::channel('bar'")
);

$results[] = testResult(
    'Orders channel defined',
    str_contains($channelsContent, "Broadcast::channel('orders'")
);

$results[] = testResult(
    'Manager channel (dashboard) defined',
    str_contains($channelsContent, "Broadcast::channel('dashboard'")
);

// Test 9: Verify JavaScript dependencies
echo "\n".colorize("7. JavaScript Dependencies\n", 'yellow');
echo colorize(str_repeat('─', 70)."\n", 'yellow');

$packageJson = json_decode(file_get_contents(__DIR__.'/package.json'), true);
$results[] = testResult(
    'laravel-echo installed',
    isset($packageJson['devDependencies']['laravel-echo']),
    'Version: '.($packageJson['devDependencies']['laravel-echo'] ?? 'Not found')
);

$results[] = testResult(
    'pusher-js installed',
    isset($packageJson['devDependencies']['pusher-js']),
    'Version: '.($packageJson['devDependencies']['pusher-js'] ?? 'Not found')
);

// Test 10: Verify Echo configuration
echo "\n".colorize("8. Echo Configuration\n", 'yellow');
echo colorize(str_repeat('─', 70)."\n", 'yellow');

$echoJsFile = __DIR__.'/resources/js/echo.js';
$results[] = testResult(
    'resources/js/echo.js exists',
    file_exists($echoJsFile)
);

$echoContent = file_exists($echoJsFile) ? file_get_contents($echoJsFile) : '';
$results[] = testResult(
    'Echo configured with Reverb broadcaster',
    str_contains($echoContent, "broadcaster: 'reverb'")
);

$results[] = testResult(
    'Echo uses VITE_REVERB_APP_KEY',
    str_contains($echoContent, 'VITE_REVERB_APP_KEY')
);

$results[] = testResult(
    'Echo uses VITE_REVERB_HOST',
    str_contains($echoContent, 'VITE_REVERB_HOST')
);

$results[] = testResult(
    'Echo uses VITE_REVERB_PORT',
    str_contains($echoContent, 'VITE_REVERB_PORT')
);

// Test 11: Test event broadcasting (simulation)
echo "\n".colorize("9. Event Broadcasting Test\n", 'yellow');
echo colorize(str_repeat('─', 70)."\n", 'yellow');

try {
    // Create test data
    $table = Table::first();
    $waiter = User::where('role', 'waiter')->first();

    if ($table && $waiter) {
        $order = Order::create([
            'order_number' => 'TEST-'.time(),
            'table_id' => $table->id,
            'waiter_id' => $waiter->id,
            'order_source' => 'pos',
            'status' => 'pending',
            'subtotal' => 100.00,
            'tax' => 10.00,
            'total' => 110.00,
        ]);

        // Test OrderCreated event
        $orderCreatedEvent = new OrderCreated($order);
        $channels = $orderCreatedEvent->broadcastOn();

        $results[] = testResult(
            'OrderCreated event can be instantiated',
            $orderCreatedEvent !== null
        );

        $results[] = testResult(
            'OrderCreated broadcasts to channels',
            count($channels) > 0,
            'Channels: '.count($channels)
        );

        // Test OrderStatusUpdated event
        $orderStatusEvent = new OrderStatusUpdated($order, 'pending', 'confirmed');
        $statusChannels = $orderStatusEvent->broadcastOn();

        $results[] = testResult(
            'OrderStatusUpdated event can be instantiated',
            $orderStatusEvent !== null
        );

        $results[] = testResult(
            'OrderStatusUpdated broadcasts to channels',
            count($statusChannels) > 0,
            'Channels: '.count($statusChannels)
        );

        // Dispatch events to test broadcasting
        echo '  '.colorize('→ Dispatching OrderCreated event...', 'cyan')."\n";
        event($orderCreatedEvent);

        $results[] = testResult(
            'OrderCreated event dispatched successfully',
            true
        );

        echo '  '.colorize('→ Dispatching OrderStatusUpdated event...', 'cyan')."\n";
        event($orderStatusEvent);

        $results[] = testResult(
            'OrderStatusUpdated event dispatched successfully',
            true
        );

        // Clean up test order
        $order->delete();

    } else {
        $results[] = testResult(
            'Test data available (table and waiter)',
            false,
            'Please ensure database has test data'
        );
    }
} catch (\Exception $e) {
    $results[] = testResult(
        'Event broadcasting test',
        false,
        'Error: '.$e->getMessage()
    );
}

// Summary
echo "\n".colorize("═══════════════════════════════════════════════════════════════\n", 'blue');
echo colorize("Test Summary\n", 'yellow');
echo colorize("═══════════════════════════════════════════════════════════════\n", 'blue');

$passed = count(array_filter($results));
$total = count($results);
$percentage = round(($passed / $total) * 100, 2);

echo sprintf("Total Tests: %d\n", $total);
echo sprintf("Passed: %s\n", colorize($passed, 'green'));
echo sprintf("Failed: %s\n", colorize($total - $passed, 'red'));
echo sprintf("Success Rate: %s%%\n", colorize($percentage, $percentage == 100 ? 'green' : 'yellow'));

echo "\n".colorize("═══════════════════════════════════════════════════════════════\n", 'blue');

if ($percentage == 100) {
    echo colorize("✓ All acceptance criteria met! Story 24 is complete.\n", 'green');
} else {
    echo colorize("✗ Some tests failed. Please review the output above.\n", 'red');
}

echo colorize("═══════════════════════════════════════════════════════════════\n\n", 'blue');

// Additional instructions
echo colorize("Next Steps:\n", 'yellow');
echo '1. Start Reverb server: '.colorize("php artisan reverb:start\n", 'cyan');
echo '2. Start Laravel app: '.colorize("php artisan serve\n", 'cyan');
echo '3. Start frontend build: '.colorize("npm run dev\n", 'cyan');
echo "4. Test real-time updates in browser console\n";
echo "\n";

exit($percentage == 100 ? 0 : 1);
