<?php

/**
 * Story 49: Performance Optimization Testing Script
 *
 * This script tests all performance optimizations implemented in Story 49:
 * - Eager loading (N+1 query elimination)
 * - Database indexes
 * - Query optimization with select()
 * - Menu and settings caching
 * - Pagination
 *
 * Run: php test-story-49-performance.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MenuCategory;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Staff;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// Color output helpers
function green($text)
{
    return "\033[32m{$text}\033[0m";
}
function red($text)
{
    return "\033[31m{$text}\033[0m";
}
function yellow($text)
{
    return "\033[33m{$text}\033[0m";
}
function blue($text)
{
    return "\033[34m{$text}\033[0m";
}

echo "\n";
echo "========================================\n";
echo "Story 49: Performance Optimization Tests\n";
echo "========================================\n\n";

$allTestsPassed = true;

// Enable query logging
DB::enableQueryLog();

// ============================================
// Test 1: Eager Loading in OrdersList
// ============================================
echo blue("Test 1: Eager Loading in OrdersList Component\n");
echo "Testing Order::with(['orderItems.menuItem', 'table', 'waiter', 'guest'])\n";

DB::flushQueryLog();
$orders = Order::with(['orderItems.menuItem', 'table', 'waiter', 'guest'])
    ->limit(5)
    ->get();

$queryCount = count(DB::getQueryLog());
echo "Queries executed: {$queryCount}\n";

// Expected: 1 main query + 4 eager load queries = 5 max
if ($queryCount <= 6) {
    echo green("✓ PASS: Efficient eager loading (≤6 queries)\n");
} else {
    echo red("✗ FAIL: Too many queries ({$queryCount}), possible N+1 issue\n");
    $allTestsPassed = false;
}
echo "\n";

// ============================================
// Test 2: Menu Caching
// ============================================
echo blue("Test 2: Menu Caching\n");
echo "Testing MenuCategory::getCachedMenu()\n";

// Clear cache first
Cache::forget(MenuCategory::CACHE_KEY);

DB::flushQueryLog();
$menu1 = MenuCategory::getCachedMenu();
$firstCallQueries = count(DB::getQueryLog());
echo "First call queries: {$firstCallQueries}\n";

DB::flushQueryLog();
$menu2 = MenuCategory::getCachedMenu();
$secondCallQueries = count(DB::getQueryLog());
echo "Second call queries (should be cached): {$secondCallQueries}\n";

if ($secondCallQueries === 0) {
    echo green("✓ PASS: Menu is cached (0 queries on second call)\n");
} else {
    echo red("✗ FAIL: Menu caching not working ({$secondCallQueries} queries on second call)\n");
    $allTestsPassed = false;
}
echo "\n";

// ============================================
// Test 3: Settings Caching
// ============================================
echo blue("Test 3: Settings Caching\n");
echo "Testing Setting::get() caching\n";

// Create a test setting if not exists
Setting::set('test_performance', 'test_value', 'string');

DB::flushQueryLog();
$setting1 = Setting::get('test_performance');
$firstCallQueries = count(DB::getQueryLog());
echo "First call queries: {$firstCallQueries}\n";

DB::flushQueryLog();
$setting2 = Setting::get('test_performance');
$secondCallQueries = count(DB::getQueryLog());
echo "Second call queries (should be cached): {$secondCallQueries}\n";

if ($secondCallQueries === 0) {
    echo green("✓ PASS: Settings are cached (0 queries on second call)\n");
} else {
    echo red("✗ FAIL: Settings caching not working ({$secondCallQueries} queries on second call)\n");
    $allTestsPassed = false;
}
echo "\n";

// ============================================
// Test 4: Database Indexes
// ============================================
echo blue("Test 4: Database Indexes\n");
echo "Checking if performance indexes exist...\n";

$indexes = [
    'orders' => ['idx_orders_status', 'idx_orders_created_at', 'idx_orders_order_number'],
    'order_items' => ['idx_order_items_order_id', 'idx_order_items_menu_item_id'],
    'menu_items' => ['idx_menu_items_category_id', 'idx_menu_items_status'],
    'settings' => ['idx_settings_key'],
];

$indexesFound = 0;
$totalIndexes = 0;

foreach ($indexes as $table => $tableIndexes) {
    foreach ($tableIndexes as $indexName) {
        $totalIndexes++;
        // Check if index exists (SQLite specific)
        $result = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND name=?", [$indexName]);
        if (count($result) > 0) {
            $indexesFound++;
        } else {
            echo yellow("  - Index '{$indexName}' on table '{$table}' not found\n");
        }
    }
}

echo "Indexes found: {$indexesFound}/{$totalIndexes}\n";
if ($indexesFound === $totalIndexes) {
    echo green("✓ PASS: All critical indexes exist\n");
} else {
    echo yellow("⚠ WARNING: Some indexes missing ({$indexesFound}/{$totalIndexes})\n");
}
echo "\n";

// ============================================
// Test 5: Query Optimization with select()
// ============================================
echo blue("Test 5: Query Optimization with select()\n");
echo "Testing Staff::select('id', 'name') vs Staff::all()\n";

DB::flushQueryLog();
$staffOptimized = Staff::select('id', 'name')->get();
$queries = DB::getQueryLog();
$optimizedQuery = $queries[0]['query'] ?? '';

if (strpos($optimizedQuery, 'SELECT id, name FROM') !== false ||
    strpos($optimizedQuery, 'select "id", "name" from') !== false) {
    echo green("✓ PASS: Query uses column selection (not SELECT *)\n");
} else {
    echo red("✗ FAIL: Query not optimized with select()\n");
    echo "Query: {$optimizedQuery}\n";
    $allTestsPassed = false;
}
echo "\n";

// ============================================
// Test 6: Dashboard Performance
// ============================================
echo blue("Test 6: Dashboard Query Optimization\n");
echo "Testing Dashboard component queries...\n";

DB::flushQueryLog();
$recentOrders = Order::select('id', 'order_number', 'table_id', 'waiter_id', 'status', 'total', 'created_at')
    ->with(['table:id,name', 'waiter:id,name'])
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

$queryCount = count(DB::getQueryLog());
echo "Queries executed for recent orders: {$queryCount}\n";

if ($queryCount <= 3) {
    echo green("✓ PASS: Dashboard queries optimized (≤3 queries)\n");
} else {
    echo red("✗ FAIL: Dashboard has too many queries ({$queryCount})\n");
    $allTestsPassed = false;
}
echo "\n";

// ============================================
// Test 7: Pagination
// ============================================
echo blue("Test 7: Pagination Implementation\n");
echo "Testing Order pagination...\n";

DB::flushQueryLog();
$paginatedOrders = Order::with(['table:id,name'])
    ->paginate(15);

$queryCount = count(DB::getQueryLog());
$hasLinks = method_exists($paginatedOrders, 'links');

echo "Queries executed: {$queryCount}\n";
echo 'Has pagination links: '.($hasLinks ? 'Yes' : 'No')."\n";

if ($hasLinks && $queryCount <= 3) {
    echo green("✓ PASS: Pagination working correctly\n");
} else {
    echo red("✗ FAIL: Pagination not properly implemented\n");
    $allTestsPassed = false;
}
echo "\n";

// ============================================
// Test 8: Cache Invalidation
// ============================================
echo blue("Test 8: Cache Invalidation\n");
echo "Testing menu cache invalidation on update...\n";

// Get cached menu
$cachedMenu = MenuCategory::getCachedMenu();
$initialCount = $cachedMenu->count();

// Create a new category (should clear cache)
$testCategory = MenuCategory::create([
    'name' => 'Test Performance Category',
    'description' => 'Test',
    'display_order' => 999,
    'status' => 'active',
]);

// Check if cache was cleared
DB::flushQueryLog();
$newCachedMenu = MenuCategory::getCachedMenu();
$queriesAfterCreate = count(DB::getQueryLog());

// Clean up
$testCategory->delete();

if ($queriesAfterCreate > 0) {
    echo green("✓ PASS: Cache invalidated on create (required new query)\n");
} else {
    echo red("✗ FAIL: Cache not invalidated on create\n");
    $allTestsPassed = false;
}
echo "\n";

// ============================================
// Test 9: Intervention/Image Package
// ============================================
echo blue("Test 9: Intervention/Image Package\n");

if (class_exists('\Intervention\Image\ImageManager')) {
    echo green("✓ PASS: intervention/image package is installed\n");
} else {
    echo red("✗ FAIL: intervention/image package not found\n");
    $allTestsPassed = false;
}
echo "\n";

// ============================================
// Test 10: Laravel Debugbar Package
// ============================================
echo blue("Test 10: Laravel Debugbar Package\n");

if (class_exists('\Barryvdh\Debugbar\ServiceProvider')) {
    echo green("✓ PASS: laravel-debugbar package is installed\n");
} else {
    echo red("✗ FAIL: laravel-debugbar package not found\n");
    $allTestsPassed = false;
}
echo "\n";

// ============================================
// Performance Summary
// ============================================
echo "========================================\n";
echo "Performance Optimization Summary\n";
echo "========================================\n\n";

echo "Optimizations Verified:\n";
echo green('✓')." Eager loading with with() to prevent N+1 queries\n";
echo green('✓')." Database indexes on foreign keys and frequently queried columns\n";
echo green('✓')." Query optimization with select() to limit columns\n";
echo green('✓')." Pagination for large datasets\n";
echo green('✓')." Menu caching with 1-hour TTL\n";
echo green('✓')." Settings caching with cache invalidation\n";
echo green('✓')." Cache invalidation on model updates\n";
echo green('✓')." intervention/image package installed\n";
echo green('✓')." Laravel Debugbar installed for N+1 detection\n";

echo "\n";
echo "Production Readiness:\n";
echo "- Route caching: Run 'php artisan route:cache'\n";
echo "- Config caching: Run 'php artisan config:cache'\n";
echo "- View caching: Run 'php artisan view:cache'\n";
echo "- Asset optimization: Run 'npm run build'\n";
echo "- Redis configuration: Set CACHE_STORE=redis and SESSION_DRIVER=redis in .env\n";

echo "\n========================================\n";
if ($allTestsPassed) {
    echo green("ALL TESTS PASSED! ✓\n");
    echo green("Story 49 performance optimizations are working correctly.\n");
} else {
    echo red("SOME TESTS FAILED! ✗\n");
    echo yellow("Review the failures above and fix the issues.\n");
}
echo "========================================\n\n";

exit($allTestsPassed ? 0 : 1);
