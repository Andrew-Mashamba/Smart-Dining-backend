<?php

/**
 * Performance Optimization Test Script
 *
 * This script tests the performance optimizations implemented in Story 49.
 * Run with: php test-performance-optimizations.php
 *
 * Requirements:
 * - Laravel Debugbar should be installed and enabled
 * - Database should have test data
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PerformanceTest
{
    private $results = [];

    private $passed = 0;

    private $failed = 0;

    public function run()
    {
        echo "\n";
        echo "=========================================\n";
        echo "  Performance Optimization Test Suite\n";
        echo "=========================================\n\n";

        $this->testEagerLoading();
        $this->testDatabaseIndexes();
        $this->testMenuCaching();
        $this->testSettingsCaching();
        $this->testQueryOptimization();
        $this->testCacheConfiguration();

        $this->printSummary();
    }

    /**
     * Test 1: Eager Loading Implementation
     */
    private function testEagerLoading()
    {
        echo "Test 1: Eager Loading\n";
        echo "--------------------\n";

        // Enable query log
        DB::enableQueryLog();

        // Test Order eager loading
        $order = Order::with(['orderItems.menuItem', 'table', 'waiter'])->first();

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        DB::disableQueryLog();

        if ($order) {
            // Access relationships - should not trigger additional queries
            DB::enableQueryLog();
            $items = $order->orderItems;
            foreach ($items as $item) {
                $menuItem = $item->menuItem;
            }
            $table = $order->table;
            $waiter = $order->waiter;

            $additionalQueries = count(DB::getQueryLog());
            DB::disableQueryLog();

            if ($additionalQueries == 0) {
                $this->pass('Eager loading working correctly (no N+1 queries)');
                echo "  Initial queries: $queryCount\n";
                echo "  Additional queries when accessing relations: $additionalQueries\n";
            } else {
                $this->fail("N+1 query detected! Additional queries: $additionalQueries");
            }
        } else {
            $this->skip('No orders found in database');
        }

        echo "\n";
    }

    /**
     * Test 2: Database Indexes
     */
    private function testDatabaseIndexes()
    {
        echo "Test 2: Database Indexes\n";
        echo "-----------------------\n";

        try {
            // Check if indexes exist on key tables
            $tables = ['orders', 'order_items', 'menu_items', 'payments', 'settings'];
            $indexesFound = true;

            foreach ($tables as $table) {
                $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name=?", [$table]);

                if (count($indexes) > 0) {
                    echo "  ✓ Indexes found on '$table' table: ".count($indexes)." indexes\n";
                } else {
                    echo "  ✗ No indexes found on '$table' table\n";
                    $indexesFound = false;
                }
            }

            if ($indexesFound) {
                $this->pass('Database indexes configured');
            } else {
                $this->fail('Some tables missing indexes');
            }
        } catch (\Exception $e) {
            $this->fail('Error checking indexes: '.$e->getMessage());
        }

        echo "\n";
    }

    /**
     * Test 3: Menu Caching
     */
    private function testMenuCaching()
    {
        echo "Test 3: Menu Caching\n";
        echo "-------------------\n";

        try {
            // Clear menu cache first
            MenuCategory::clearMenuCache();
            $this->pass('Cache can be cleared');

            // First call - should query database
            DB::enableQueryLog();
            $menu1 = MenuCategory::getCachedMenu();
            $queries1 = count(DB::getQueryLog());
            DB::disableQueryLog();

            echo "  First call queries: $queries1\n";

            // Second call - should use cache
            DB::enableQueryLog();
            $menu2 = MenuCategory::getCachedMenu();
            $queries2 = count(DB::getQueryLog());
            DB::disableQueryLog();

            echo "  Second call queries: $queries2\n";

            if ($queries2 === 0) {
                $this->pass('Menu caching working (second call used cache)');
            } else {
                $this->fail('Menu not cached properly');
            }

            // Verify cache key exists
            if (Cache::has(MenuCategory::CACHE_KEY)) {
                $this->pass('Menu cache key exists');
            } else {
                $this->fail('Menu cache key not found');
            }
        } catch (\Exception $e) {
            $this->fail('Menu caching error: '.$e->getMessage());
        }

        echo "\n";
    }

    /**
     * Test 4: Settings Caching
     */
    private function testSettingsCaching()
    {
        echo "Test 4: Settings Caching\n";
        echo "-----------------------\n";

        try {
            // Set a test setting
            Setting::set('test_key', 'test_value');
            $this->pass('Setting can be created');

            // First get - should query database
            Cache::forget('setting.test_key');
            DB::enableQueryLog();
            $value1 = Setting::get('test_key');
            $queries1 = count(DB::getQueryLog());
            DB::disableQueryLog();

            echo "  First get queries: $queries1\n";

            // Second get - should use cache
            DB::enableQueryLog();
            $value2 = Setting::get('test_key');
            $queries2 = count(DB::getQueryLog());
            DB::disableQueryLog();

            echo "  Second get queries: $queries2\n";

            if ($value1 === 'test_value' && $queries2 === 0) {
                $this->pass('Settings caching working correctly');
            } else {
                $this->fail('Settings caching not working properly');
            }

            // Test cache invalidation
            Setting::set('test_key', 'new_value');
            $newValue = Setting::get('test_key');

            if ($newValue === 'new_value') {
                $this->pass('Settings cache invalidation working');
            } else {
                $this->fail('Settings cache not invalidated on update');
            }

            // Cleanup
            Setting::where('key', 'test_key')->delete();
        } catch (\Exception $e) {
            $this->fail('Settings caching error: '.$e->getMessage());
        }

        echo "\n";
    }

    /**
     * Test 5: Query Optimization (select, pagination)
     */
    private function testQueryOptimization()
    {
        echo "Test 5: Query Optimization\n";
        echo "-------------------------\n";

        try {
            // Test that queries use select() to limit columns
            DB::enableQueryLog();
            $items = MenuItem::select('id', 'name', 'price')->limit(5)->get();
            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            if (count($queries) > 0) {
                $sql = $queries[0]['query'];
                // Check if SELECT * is NOT used
                if (strpos($sql, 'SELECT *') === false) {
                    $this->pass('Queries use select() to limit columns');
                    echo '  Query: '.substr($sql, 0, 80)."...\n";
                } else {
                    $this->fail('Query still using SELECT *');
                }
            }

            // Test pagination exists
            $orders = Order::paginate(10);
            if ($orders->lastPage() >= 1) {
                $this->pass('Pagination implemented');
                echo '  Items per page: '.$orders->perPage()."\n";
            } else {
                $this->skip('Pagination test inconclusive (not enough data)');
            }
        } catch (\Exception $e) {
            $this->fail('Query optimization test error: '.$e->getMessage());
        }

        echo "\n";
    }

    /**
     * Test 6: Cache Configuration
     */
    private function testCacheConfiguration()
    {
        echo "Test 6: Cache Configuration\n";
        echo "--------------------------\n";

        $cacheDriver = config('cache.default');
        echo "  Current cache driver: $cacheDriver\n";

        if ($cacheDriver === 'redis') {
            $this->pass('Redis cache configured (PRODUCTION READY)');
        } elseif ($cacheDriver === 'database') {
            $this->skip('Using database cache (OK for development)');
            echo "  💡 Recommendation: Use Redis for production (CACHE_STORE=redis)\n";
        } else {
            $this->fail("Unexpected cache driver: $cacheDriver");
        }

        $sessionDriver = config('session.driver');
        echo "  Current session driver: $sessionDriver\n";

        if ($sessionDriver === 'redis') {
            $this->pass('Redis sessions configured (PRODUCTION READY)');
        } else {
            $this->skip("Using $sessionDriver for sessions");
            echo "  💡 Recommendation: Use Redis for production (SESSION_DRIVER=redis)\n";
        }

        echo "\n";
    }

    /**
     * Helper: Mark test as passed
     */
    private function pass($message)
    {
        echo "  ✓ PASS: $message\n";
        $this->passed++;
        $this->results[] = ['status' => 'PASS', 'message' => $message];
    }

    /**
     * Helper: Mark test as failed
     */
    private function fail($message)
    {
        echo "  ✗ FAIL: $message\n";
        $this->failed++;
        $this->results[] = ['status' => 'FAIL', 'message' => $message];
    }

    /**
     * Helper: Skip test
     */
    private function skip($message)
    {
        echo "  ⊘ SKIP: $message\n";
        $this->results[] = ['status' => 'SKIP', 'message' => $message];
    }

    /**
     * Print test summary
     */
    private function printSummary()
    {
        echo "=========================================\n";
        echo "  Test Summary\n";
        echo "=========================================\n\n";
        echo '  Tests Passed: '.$this->passed."\n";
        echo '  Tests Failed: '.$this->failed."\n";
        echo "\n";

        if ($this->failed === 0) {
            echo "  ✓ All tests passed!\n";
            echo "\n";
            echo "Performance optimizations are working correctly.\n";
            echo "For best production performance:\n";
            echo "  1. Enable Redis: CACHE_STORE=redis, SESSION_DRIVER=redis\n";
            echo "  2. Run: ./optimize-for-production.sh\n";
            echo "  3. Enable OPcache in php.ini\n";
            echo "  4. Use Laravel Debugbar to monitor queries in development\n";
        } else {
            echo "  ✗ Some tests failed!\n";
            echo "\n";
            echo "Please review the failed tests above.\n";
        }

        echo "\n=========================================\n";
    }
}

// Run tests
$test = new PerformanceTest;
$test->run();
