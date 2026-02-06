#!/usr/bin/env php
<?php

/**
 * Story 49: Application Performance Optimization - Comprehensive Verification Script
 *
 * This script verifies all acceptance criteria for Story 49.
 *
 * Tests:
 * 1. Eager loading in Livewire components
 * 2. Database indexes existence
 * 3. Query optimization (select, pagination)
 * 4. Cache configuration
 * 5. Menu caching functionality
 * 6. Settings caching functionality
 * 7. Production optimization commands
 * 8. Asset optimization
 * 9. Laravel Debugbar installation
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MenuCategory;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// Color output helpers
function success($message)
{
    echo "\033[32m✓\033[0m ".$message."\n";
}

function error($message)
{
    echo "\033[31m✗\033[0m ".$message."\n";
}

function info($message)
{
    echo "\033[34mℹ\033[0m ".$message."\n";
}

function heading($message)
{
    echo "\n\033[1;36m".$message."\033[0m\n";
    echo str_repeat('=', strlen($message))."\n\n";
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Story 49: Performance Optimization - Verification Script     ║\n";
echo "╔════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

// Test 1: Check Eager Loading in Livewire Components
heading('Test 1: Eager Loading in Livewire Components');

$componentsToCheck = [
    'OrdersList' => ['orderItems.menuItem', 'table', 'waiter', 'guest'],
    'KitchenDisplay' => ['order', 'order.table', 'menuItem'],
    'BarDisplay' => ['order', 'order.table', 'menuItem'],
    'Dashboard' => ['table', 'waiter'],
    'CreateOrder' => ['menuCategory'],
];

foreach ($componentsToCheck as $component => $relationships) {
    $totalTests++;
    $filePath = __DIR__."/app/Livewire/{$component}.php";

    if (! file_exists($filePath)) {
        error("Component {$component}.php not found");
        $failedTests++;

        continue;
    }

    $content = file_get_contents($filePath);
    $hasEagerLoading = false;

    foreach ($relationships as $relationship) {
        if (strpos($content, 'with(') !== false || strpos($content, '->with([') !== false) {
            $hasEagerLoading = true;
            break;
        }
    }

    if ($hasEagerLoading) {
        success("{$component}: Has eager loading implemented");
        $passedTests++;
    } else {
        error("{$component}: Missing eager loading");
        $failedTests++;
    }
}

// Test 2: Check Database Indexes
heading('Test 2: Database Indexes');

$indexesToCheck = [
    'orders' => ['idx_orders_table_id', 'idx_orders_status', 'idx_orders_order_number', 'idx_orders_created_at'],
    'order_items' => ['idx_order_items_order_id', 'idx_order_items_menu_item_id', 'idx_order_items_prep_status'],
    'menu_items' => ['idx_menu_items_category_id', 'idx_menu_items_status'],
    'menu_categories' => ['idx_menu_categories_status'],
    'settings' => ['idx_settings_key'],
];

foreach ($indexesToCheck as $table => $indexes) {
    foreach ($indexes as $index) {
        $totalTests++;

        try {
            // Check if index exists by querying sqlite_master
            $result = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND name=?", [$index]);

            if (count($result) > 0) {
                success("Index '{$index}' exists on table '{$table}'");
                $passedTests++;
            } else {
                error("Index '{$index}' missing on table '{$table}'");
                $failedTests++;
            }
        } catch (\Exception $e) {
            error("Error checking index '{$index}': ".$e->getMessage());
            $failedTests++;
        }
    }
}

// Test 3: Query Optimization - Check for select() and pagination
heading('Test 3: Query Optimization (select() and pagination)');

$totalTests++;
$orderListContent = file_get_contents(__DIR__.'/app/Livewire/OrdersList.php');
if (strpos($orderListContent, 'WithPagination') !== false && strpos($orderListContent, '->paginate(') !== false) {
    success('OrdersList: Uses pagination');
    $passedTests++;
} else {
    error('OrdersList: Missing pagination');
    $failedTests++;
}

$totalTests++;
$dashboardContent = file_get_contents(__DIR__.'/app/Livewire/Dashboard.php');
if (strpos($dashboardContent, '->select(') !== false || strpos($dashboardContent, '::select(') !== false) {
    success('Dashboard: Uses select() to limit columns');
    $passedTests++;
} else {
    error('Dashboard: Missing select() optimization');
    $failedTests++;
}

// Test 4: Cache Configuration
heading('Test 4: Redis Cache Configuration in .env.example');

$totalTests++;
$envExample = file_get_contents(__DIR__.'/.env.example');
if (strpos($envExample, 'CACHE_STORE=') !== false &&
    strpos($envExample, 'SESSION_DRIVER=') !== false &&
    strpos($envExample, 'REDIS_HOST=') !== false) {
    success('.env.example: Contains Redis cache configuration');
    $passedTests++;
} else {
    error('.env.example: Missing Redis configuration');
    $failedTests++;
}

// Test 5: Menu Caching
heading('Test 5: Menu Caching Implementation');

$totalTests++;
try {
    // Clear cache first
    Cache::forget(MenuCategory::CACHE_KEY);

    // First call (should cache)
    $start = microtime(true);
    $menu1 = MenuCategory::getCachedMenu();
    $time1 = microtime(true) - $start;

    // Second call (should use cache)
    $start = microtime(true);
    $menu2 = MenuCategory::getCachedMenu();
    $time2 = microtime(true) - $start;

    if ($time2 < $time1 && method_exists(MenuCategory::class, 'getCachedMenu')) {
        success('Menu caching: Works correctly (2nd call faster: '.number_format($time2 * 1000, 2).'ms vs '.number_format($time1 * 1000, 2).'ms)');
        $passedTests++;
    } else {
        error('Menu caching: Not optimized or method missing');
        $failedTests++;
    }

    // Test cache invalidation
    $totalTests++;
    MenuCategory::clearMenuCache();
    $cached = Cache::get(MenuCategory::CACHE_KEY);
    if ($cached === null) {
        success('Menu cache invalidation: Works correctly');
        $passedTests++;
    } else {
        error('Menu cache invalidation: Failed');
        $failedTests++;
    }

} catch (\Exception $e) {
    error('Menu caching test failed: '.$e->getMessage());
    $failedTests += 2;
    $totalTests++;
}

// Test 6: Settings Caching
heading('Test 6: Settings Caching Implementation');

$totalTests++;
try {
    if (method_exists(Setting::class, 'get') && method_exists(Setting::class, 'set')) {
        // Test set and get
        Setting::set('test_key', 'test_value', 'string');
        $value = Setting::get('test_key', 'default');

        if ($value === 'test_value') {
            success('Settings caching: get() and set() methods work correctly');
            $passedTests++;
        } else {
            error('Settings caching: get() returns incorrect value');
            $failedTests++;
        }

        // Check if caching is implemented
        $totalTests++;
        $settingContent = file_get_contents(__DIR__.'/app/Models/Setting.php');
        if (strpos($settingContent, 'Cache::remember') !== false && strpos($settingContent, 'Cache::forget') !== false) {
            success('Settings caching: Uses cache correctly');
            $passedTests++;
        } else {
            error('Settings caching: Not using cache');
            $failedTests++;
        }

    } else {
        error('Settings: Missing get() or set() methods');
        $failedTests++;
    }
} catch (\Exception $e) {
    error('Settings caching test failed: '.$e->getMessage());
    $failedTests++;
}

// Test 7: Production Optimization Documentation
heading('Test 7: Production Optimization Commands');

$totalTests++;
if (file_exists(__DIR__.'/PERFORMANCE_OPTIMIZATION.md')) {
    $perfDoc = file_get_contents(__DIR__.'/PERFORMANCE_OPTIMIZATION.md');

    $requiredCommands = [
        'php artisan route:cache',
        'php artisan config:cache',
        'php artisan view:cache',
        'npm run build',
    ];

    $allCommandsDocumented = true;
    foreach ($requiredCommands as $command) {
        if (strpos($perfDoc, $command) === false) {
            $allCommandsDocumented = false;
            break;
        }
    }

    if ($allCommandsDocumented) {
        success('Documentation: All production optimization commands documented');
        $passedTests++;
    } else {
        error('Documentation: Missing some production commands');
        $failedTests++;
    }
} else {
    error('Documentation: PERFORMANCE_OPTIMIZATION.md not found');
    $failedTests++;
}

// Test 8: Asset Optimization (package.json)
heading('Test 8: Asset Optimization');

$totalTests++;
$packageJson = json_decode(file_get_contents(__DIR__.'/package.json'), true);
if (isset($packageJson['scripts']['build'])) {
    success("package.json: Has 'npm run build' script configured");
    $passedTests++;
} else {
    error('package.json: Missing build script');
    $failedTests++;
}

// Test 9: Intervention/Image Package
heading('Test 9: Image Optimization Package');

$totalTests++;
$composerJson = json_decode(file_get_contents(__DIR__.'/composer.json'), true);
if (isset($composerJson['require']['intervention/image'])) {
    success('intervention/image: Package is installed');
    $passedTests++;
} else {
    error('intervention/image: Package not installed');
    $failedTests++;
}

// Test 10: Laravel Debugbar
heading('Test 10: Laravel Debugbar (Development Tool)');

$totalTests++;
if (isset($composerJson['require-dev']['barryvdh/laravel-debugbar'])) {
    success('Laravel Debugbar: Package is installed (require-dev)');
    $passedTests++;
} else {
    error('Laravel Debugbar: Package not installed');
    $failedTests++;
}

// Test 11: Performance Migration
heading('Test 11: Performance Indexes Migration');

$totalTests++;
if (file_exists(__DIR__.'/database/migrations/2026_02_06_131146_add_performance_indexes_to_tables.php')) {
    success('Performance indexes migration: File exists');
    $passedTests++;
} else {
    error('Performance indexes migration: File not found');
    $failedTests++;
}

// Summary
heading('Test Summary');

echo "Total Tests:  {$totalTests}\n";
echo "\033[32mPassed:       {$passedTests}\033[0m\n";

if ($failedTests > 0) {
    echo "\033[31mFailed:       {$failedTests}\033[0m\n";
} else {
    echo "\033[32mFailed:       {$failedTests}\033[0m\n";
}

$percentage = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;
echo "Success Rate: {$percentage}%\n";

echo "\n";
if ($failedTests === 0) {
    echo "\033[1;32m╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ✓ All performance optimizations verified successfully!       ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\033[0m\n";
    exit(0);
} else {
    echo "\033[1;33m╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ⚠ Some tests failed. Please review the errors above.         ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\033[0m\n";
    exit(1);
}
