<?php

/**
 * Test Script for Laravel Reverb Setup
 *
 * This script verifies all acceptance criteria for Story 24
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "==============================================\n";
echo "Testing Laravel Reverb Setup (Story 24)\n";
echo "==============================================\n\n";

$allPassed = true;

// Test 1: Check if Reverb is installed
echo "1. Checking if Laravel Reverb is installed...\n";
$composerJson = json_decode(file_get_contents(__DIR__.'/composer.json'), true);
if (isset($composerJson['require']['laravel/reverb'])) {
    echo "   ✓ Laravel Reverb is installed (version: {$composerJson['require']['laravel/reverb']})\n";
} else {
    echo "   ✗ Laravel Reverb is NOT installed\n";
    $allPassed = false;
}
echo "\n";

// Test 2: Check if Reverb config exists
echo "2. Checking if Reverb config file exists...\n";
if (file_exists(__DIR__.'/config/reverb.php')) {
    echo "   ✓ config/reverb.php exists\n";
} else {
    echo "   ✗ config/reverb.php does NOT exist\n";
    $allPassed = false;
}
echo "\n";

// Test 3: Check .env configuration
echo "3. Checking .env configuration...\n";
$envVars = [
    'BROADCAST_CONNECTION' => 'reverb',
    'REVERB_APP_ID',
    'REVERB_APP_KEY',
    'REVERB_APP_SECRET',
    'REVERB_HOST',
    'REVERB_PORT',
    'REVERB_SCHEME',
];

foreach ($envVars as $key => $expectedValue) {
    if (is_numeric($key)) {
        // Just check if it exists
        $value = env($expectedValue);
        if ($value !== null) {
            echo "   ✓ {$expectedValue} is set\n";
        } else {
            echo "   ✗ {$expectedValue} is NOT set\n";
            $allPassed = false;
        }
    } else {
        // Check if it matches expected value
        $value = env($key);
        if ($value === $expectedValue) {
            echo "   ✓ {$key} = {$expectedValue}\n";
        } else {
            echo "   ✗ {$key} = {$value} (expected: {$expectedValue})\n";
            $allPassed = false;
        }
    }
}
echo "\n";

// Test 4: Check broadcasting.php config
echo "4. Checking config/broadcasting.php...\n";
$broadcastingConfig = require __DIR__.'/config/broadcasting.php';
if (isset($broadcastingConfig['connections']['reverb'])) {
    echo "   ✓ Reverb connection is configured in broadcasting.php\n";

    // Verify reverb connection details
    $reverbConfig = $broadcastingConfig['connections']['reverb'];
    $requiredKeys = ['driver', 'key', 'secret', 'app_id', 'options'];

    foreach ($requiredKeys as $key) {
        if (isset($reverbConfig[$key])) {
            echo "   ✓ Reverb connection has '{$key}' configured\n";
        } else {
            echo "   ✗ Reverb connection missing '{$key}'\n";
            $allPassed = false;
        }
    }
} else {
    echo "   ✗ Reverb connection is NOT configured\n";
    $allPassed = false;
}
echo "\n";

// Test 5: Check if OrderCreated event exists
echo "5. Checking if OrderCreated event exists...\n";
if (file_exists(__DIR__.'/app/Events/OrderCreated.php')) {
    echo "   ✓ app/Events/OrderCreated.php exists\n";

    // Check if it implements ShouldBroadcast
    $content = file_get_contents(__DIR__.'/app/Events/OrderCreated.php');
    if (strpos($content, 'implements ShouldBroadcast') !== false) {
        echo "   ✓ OrderCreated implements ShouldBroadcast\n";
    } else {
        echo "   ✗ OrderCreated does NOT implement ShouldBroadcast\n";
        $allPassed = false;
    }

    if (strpos($content, 'function broadcastOn()') !== false) {
        echo "   ✓ OrderCreated has broadcastOn() method\n";
    } else {
        echo "   ✗ OrderCreated does NOT have broadcastOn() method\n";
        $allPassed = false;
    }
} else {
    echo "   ✗ app/Events/OrderCreated.php does NOT exist\n";
    $allPassed = false;
}
echo "\n";

// Test 6: Check if OrderStatusUpdated event exists
echo "6. Checking if OrderStatusUpdated event exists...\n";
if (file_exists(__DIR__.'/app/Events/OrderStatusUpdated.php')) {
    echo "   ✓ app/Events/OrderStatusUpdated.php exists\n";

    // Check if it implements ShouldBroadcast
    $content = file_get_contents(__DIR__.'/app/Events/OrderStatusUpdated.php');
    if (strpos($content, 'implements ShouldBroadcast') !== false) {
        echo "   ✓ OrderStatusUpdated implements ShouldBroadcast\n";
    } else {
        echo "   ✗ OrderStatusUpdated does NOT implement ShouldBroadcast\n";
        $allPassed = false;
    }

    if (strpos($content, 'function broadcastOn()') !== false) {
        echo "   ✓ OrderStatusUpdated has broadcastOn() method\n";
    } else {
        echo "   ✗ OrderStatusUpdated does NOT have broadcastOn() method\n";
        $allPassed = false;
    }
} else {
    echo "   ✗ app/Events/OrderStatusUpdated.php does NOT exist\n";
    $allPassed = false;
}
echo "\n";

// Test 7: Check channel routes
echo "7. Checking routes/channels.php...\n";
if (file_exists(__DIR__.'/routes/channels.php')) {
    echo "   ✓ routes/channels.php exists\n";

    $content = file_get_contents(__DIR__.'/routes/channels.php');
    $channels = ['kitchen', 'bar', 'orders'];

    foreach ($channels as $channel) {
        if (strpos($content, "'{$channel}'") !== false) {
            echo "   ✓ Channel '{$channel}' is defined\n";
        } else {
            echo "   ✗ Channel '{$channel}' is NOT defined\n";
            $allPassed = false;
        }
    }
} else {
    echo "   ✗ routes/channels.php does NOT exist\n";
    $allPassed = false;
}
echo "\n";

// Test 8: Check if Laravel Echo and Pusher JS are installed
echo "8. Checking if Laravel Echo and Pusher JS are installed...\n";
$packageJson = json_decode(file_get_contents(__DIR__.'/package.json'), true);
if (isset($packageJson['devDependencies']['laravel-echo'])) {
    echo "   ✓ laravel-echo is installed (version: {$packageJson['devDependencies']['laravel-echo']})\n";
} else {
    echo "   ✗ laravel-echo is NOT installed\n";
    $allPassed = false;
}

if (isset($packageJson['devDependencies']['pusher-js'])) {
    echo "   ✓ pusher-js is installed (version: {$packageJson['devDependencies']['pusher-js']})\n";
} else {
    echo "   ✗ pusher-js is NOT installed\n";
    $allPassed = false;
}
echo "\n";

// Test 9: Check Echo configuration
echo "9. Checking Echo configuration in resources/js/...\n";
if (file_exists(__DIR__.'/resources/js/echo.js')) {
    echo "   ✓ resources/js/echo.js exists\n";

    $content = file_get_contents(__DIR__.'/resources/js/echo.js');
    if (strpos($content, "broadcaster: 'reverb'") !== false) {
        echo "   ✓ Echo is configured to use 'reverb' broadcaster\n";
    } else {
        echo "   ✗ Echo is NOT configured to use 'reverb' broadcaster\n";
        $allPassed = false;
    }

    if (strpos($content, 'VITE_REVERB_APP_KEY') !== false) {
        echo "   ✓ Echo uses VITE_REVERB_APP_KEY\n";
    } else {
        echo "   ✗ Echo does NOT use VITE_REVERB_APP_KEY\n";
        $allPassed = false;
    }
} else {
    echo "   ✗ resources/js/echo.js does NOT exist\n";
    $allPassed = false;
}
echo "\n";

// Test 10: Test event broadcasting (simulated)
echo "10. Testing event broadcasting capability...\n";
try {
    // Check if we can instantiate the events
    $testOrder = new \App\Models\Order;
    $testOrder->id = 999;
    $testOrder->waiter_id = 1;
    $testOrder->status = 'pending';

    // Mock the relationships
    $testOrder->setRelation('table', (object) ['name' => 'Test Table']);
    $testOrder->setRelation('items', collect([]));

    $event = new \App\Events\OrderCreated($testOrder);
    echo "   ✓ OrderCreated event can be instantiated\n";

    $channels = $event->broadcastOn();
    echo '   ✓ OrderCreated broadcasts to '.count($channels)." channels\n";

    $event2 = new \App\Events\OrderStatusUpdated($testOrder, 'pending', 'preparing');
    echo "   ✓ OrderStatusUpdated event can be instantiated\n";

    $channels2 = $event2->broadcastOn();
    echo '   ✓ OrderStatusUpdated broadcasts to '.count($channels2)." channels\n";

    echo "   ✓ Events are properly configured for broadcasting\n";
} catch (\Exception $e) {
    echo '   ✗ Error testing events: '.$e->getMessage()."\n";
    $allPassed = false;
}
echo "\n";

// Summary
echo "==============================================\n";
if ($allPassed) {
    echo "✓ ALL TESTS PASSED!\n";
    echo "==============================================\n";
    echo "\nStory 24 Implementation Status: COMPLETE\n\n";
    echo "Next Steps:\n";
    echo "1. Start Reverb server: php artisan reverb:start\n";
    echo "2. In another terminal, start queue worker: php artisan queue:work\n";
    echo "3. Start the dev server: php artisan serve\n";
    echo "4. Test in browser by creating an order\n";
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED\n";
    echo "==============================================\n";
    echo "\nPlease review the failed tests above.\n";
    exit(1);
}
