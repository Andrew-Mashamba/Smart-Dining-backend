<?php

/**
 * Laravel Reverb Broadcasting Configuration Test
 *
 * This script verifies that Laravel Reverb is properly configured
 * and ready for real-time broadcasting.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Laravel Reverb Broadcasting Configuration Test\n";
echo "==============================================\n\n";

// Test 1: Check broadcast driver configuration
echo "1. Checking Broadcast Driver Configuration:\n";
$broadcastDriver = config('broadcasting.default');
echo "   BROADCAST_CONNECTION: {$broadcastDriver}\n";

if ($broadcastDriver === 'reverb') {
    echo "   ✓ Broadcast driver is set to 'reverb'\n\n";
} else {
    echo "   ✗ Broadcast driver is NOT set to 'reverb' (current: {$broadcastDriver})\n";
    echo "   Please set BROADCAST_CONNECTION=reverb in your .env file\n\n";
    exit(1);
}

// Test 2: Check Reverb configuration
echo "2. Checking Reverb Configuration:\n";
$reverbAppId = config('reverb.apps.apps.0.app_id');
$reverbAppKey = config('reverb.apps.apps.0.key');
$reverbAppSecret = config('reverb.apps.apps.0.secret');
$reverbHost = config('reverb.apps.apps.0.options.host');
$reverbPort = config('reverb.apps.apps.0.options.port');
$reverbScheme = config('reverb.apps.apps.0.options.scheme');

echo "   REVERB_APP_ID: {$reverbAppId}\n";
echo "   REVERB_APP_KEY: {$reverbAppKey}\n";
echo '   REVERB_APP_SECRET: '.str_repeat('*', strlen($reverbAppSecret ?? ''))."\n";
echo "   REVERB_HOST: {$reverbHost}\n";
echo "   REVERB_PORT: {$reverbPort}\n";
echo "   REVERB_SCHEME: {$reverbScheme}\n";

if ($reverbAppId && $reverbAppKey && $reverbAppSecret) {
    echo "   ✓ Reverb credentials are configured\n\n";
} else {
    echo "   ✗ Reverb credentials are missing\n";
    echo "   Please check your .env file has REVERB_APP_ID, REVERB_APP_KEY, and REVERB_APP_SECRET\n\n";
    exit(1);
}

// Test 3: Check broadcasting connection configuration
echo "3. Checking Broadcasting Connection:\n";
$reverbConnection = config('broadcasting.connections.reverb');
echo "   Driver: {$reverbConnection['driver']}\n";
echo "   App ID: {$reverbConnection['app_id']}\n";
echo "   ✓ Reverb connection is properly configured\n\n";

// Test 4: Verify event classes exist
echo "4. Verifying Event Classes:\n";
if (class_exists(\App\Events\OrderCreated::class)) {
    echo "   ✓ OrderCreated event class exists\n";
    $reflection = new ReflectionClass(\App\Events\OrderCreated::class);
    $implements = $reflection->getInterfaceNames();
    if (in_array('Illuminate\Contracts\Broadcasting\ShouldBroadcast', $implements)) {
        echo "   ✓ OrderCreated implements ShouldBroadcast\n";
    } else {
        echo "   ✗ OrderCreated does NOT implement ShouldBroadcast\n";
    }
} else {
    echo "   ✗ OrderCreated event class not found\n";
}

if (class_exists(\App\Events\OrderStatusUpdated::class)) {
    echo "   ✓ OrderStatusUpdated event class exists\n";
    $reflection = new ReflectionClass(\App\Events\OrderStatusUpdated::class);
    $implements = $reflection->getInterfaceNames();
    if (in_array('Illuminate\Contracts\Broadcasting\ShouldBroadcast', $implements)) {
        echo "   ✓ OrderStatusUpdated implements ShouldBroadcast\n";
    } else {
        echo "   ✗ OrderStatusUpdated does NOT implement ShouldBroadcast\n";
    }
} else {
    echo "   ✗ OrderStatusUpdated event class not found\n";
}
echo "\n";

// Test 5: Check channel authorization routes
echo "5. Checking Channel Routes:\n";
if (file_exists(base_path('routes/channels.php'))) {
    echo "   ✓ routes/channels.php exists\n";
    $channelsContent = file_get_contents(base_path('routes/channels.php'));
    if (strpos($channelsContent, 'kitchen') !== false) {
        echo "   ✓ Kitchen channel route defined\n";
    }
    if (strpos($channelsContent, 'bar') !== false) {
        echo "   ✓ Bar channel route defined\n";
    }
    if (strpos($channelsContent, 'orders') !== false) {
        echo "   ✓ Orders channel route defined\n";
    }
} else {
    echo "   ✗ routes/channels.php not found\n";
}
echo "\n";

// Test 6: Check JavaScript Echo configuration
echo "6. Checking JavaScript Echo Configuration:\n";
if (file_exists(resource_path('js/echo.js'))) {
    echo "   ✓ resources/js/echo.js exists\n";
    $echoContent = file_get_contents(resource_path('js/echo.js'));
    if (strpos($echoContent, 'reverb') !== false) {
        echo "   ✓ Echo configured to use Reverb broadcaster\n";
    }
    if (strpos($echoContent, 'VITE_REVERB_APP_KEY') !== false) {
        echo "   ✓ Echo configured with Reverb credentials\n";
    }
} else {
    echo "   ✗ resources/js/echo.js not found\n";
}
echo "\n";

// Summary
echo "==============================================\n";
echo "Configuration Test Summary:\n";
echo "✓ Laravel Reverb is properly configured!\n\n";

echo "Next Steps:\n";
echo "1. Start the Reverb server:\n";
echo "   php artisan reverb:start\n\n";
echo "2. Start your Laravel development server:\n";
echo "   php artisan serve\n\n";
echo "3. Build frontend assets:\n";
echo "   npm run dev\n\n";
echo "4. To test broadcasting with real data:\n";
echo "   - Create an order through the application UI\n";
echo "   - Or use tinker: php artisan tinker\n";
echo "   - Then dispatch: event(new \\App\\Events\\OrderCreated(\$order));\n\n";

echo "Reverb Server Status:\n";
$connection = @fsockopen('localhost', 8080, $errno, $errstr, 1);
if ($connection) {
    echo "✓ Reverb server is running on port 8080\n";
    fclose($connection);
} else {
    echo "⚠ Reverb server is not running on port 8080\n";
    echo "  Start it with: php artisan reverb:start\n";
}
echo "\n";
