# Performance Optimization Guide

This document outlines the performance optimizations implemented in the Laravel Hospitality System and provides commands for production deployment.

## Implemented Optimizations

### 1. Database Query Optimization

#### Eager Loading
All Livewire components use eager loading to prevent N+1 query problems:

- **OrdersList**: `Order::with(['orderItems.menuItem', 'table', 'waiter', 'guest'])`
- **Dashboard**: Optimized queries with `select()` to limit columns
- **KitchenDisplay**: `OrderItem::with(['order:id,order_number,table_id,created_at', 'order.table:id,name', 'menuItem:id,name,prep_time_minutes'])`
- **CreateOrder**: `MenuItem::with('menuCategory')`

#### Database Indexes
Added indexes to frequently queried columns:

Comprehensive indexes added via migration `2026_02_06_131146_add_performance_indexes_to_tables.php`:

**orders table:**
- `table_id`, `guest_id`, `waiter_id` (foreign keys)
- `status`, `order_number` (filtering/searching)
- `created_at`, `order_source` (date queries)
- Composite: `[status, created_at]` (common query pattern)

**order_items table:**
- `order_id`, `menu_item_id` (foreign keys)
- `prep_status` (kitchen/bar filtering)
- Composite: `[prep_status, order_id]` (kitchen/bar queries)

**menu_items table:**
- `category_id`, `status`, `prep_area` (filtering)
- Composite: `[stock_quantity, low_stock_threshold]` (low stock alerts)

**menu_categories table:**
- `status`, `display_order` (ordering)

**tables, staff, payments, tips, guests, guest_sessions:**
- Foreign keys and status columns indexed
- Date-based query indexes

**audit_logs, order_status_logs, error_logs:**
- Foreign keys and timestamp indexes

**settings table:**
- `key` column (unique lookup)

#### Query Optimization
- Use `select()` to limit columns returned
- Pagination implemented in OrdersList component
- Limited columns in filter dropdowns

### 2. Caching System

#### Menu Caching
The menu system is cached for 1 hour to reduce database queries:

```php
// Get cached menu (auto-refreshes after 1 hour)
$menu = MenuCategory::getCachedMenu();

// Manually clear menu cache
MenuCategory::clearMenuCache();
```

Cache is automatically cleared when:
- Menu categories are created, updated, or deleted
- Menu items are created, updated, or deleted

**Location:** `app/Models/MenuCategory.php:51-72`

#### Settings Caching
System settings are cached for 1 hour:

```php
// Get setting (cached)
$taxRate = Setting::get('tax_rate', 0.18);

// Set setting (auto-clears cache)
Setting::set('tax_rate', 0.20, 'decimal');
```

Cache is automatically invalidated when settings are updated.

**Location:** `app/Models/Setting.php:19-61`

### 3. Redis Configuration

For production environments, configure Redis for caching and sessions in `.env`:

```env
# Enable Redis for cache and sessions
CACHE_STORE=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis

# Redis connection settings
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CACHE_DB=1
REDIS_PREFIX=hospitality_
```

**Note:** Ensure Redis is installed and running on your server.

## Production Optimization Commands

Run these commands when deploying to production:

### 1. Route Caching
Cache all application routes for faster route registration:

```bash
php artisan route:cache
```

To clear route cache (needed after route changes):
```bash
php artisan route:clear
```

### 2. Config Caching
Cache all configuration files for faster loading:

```bash
php artisan config:cache
```

To clear config cache (needed after config changes):
```bash
php artisan config:clear
```

### 3. View Caching
Pre-compile all Blade views:

```bash
php artisan view:cache
```

To clear view cache:
```bash
php artisan view:clear
```

### 4. Event Caching
Cache event-listener mappings:

```bash
php artisan event:cache
```

To clear event cache:
```bash
php artisan event:clear
```

### 5. Asset Optimization
Build and minify CSS/JS assets for production:

```bash
npm run build
```

This command:
- Minifies JavaScript files
- Minifies CSS files
- Optimizes assets for production
- Generates versioned filenames for cache busting

### 6. Complete Production Optimization

Run all optimization commands at once:

```bash
# Clear old caches first
php artisan optimize:clear

# Run all optimizations
php artisan optimize

# Cache routes, config, views, and events
php artisan route:cache
php artisan config:cache
php artisan view:cache
php artisan event:cache

# Build production assets
npm run build
```

## Image Optimization

### Intervention/Image Package

The `intervention/image` package is included for menu item image optimization.

**Installation:**
```bash
composer require intervention/image
```

**Basic Usage:**
```php
use Intervention\Image\Facades\Image;

// Resize and optimize menu item image
$image = Image::make($request->file('image'))
    ->resize(800, 600, function ($constraint) {
        $constraint->aspectRatio();
        $constraint->upsize();
    })
    ->encode('jpg', 80); // 80% quality

// Save optimized image
$path = 'menu-items/' . $filename;
Storage::disk('public')->put($path, $image);
```

**Location for implementation:** `app/Livewire/MenuManagement.php:199-231` (saveItem method)

## Development Tools

### Laravel Debugbar

Laravel Debugbar helps identify N+1 queries and performance issues during development.

**Installation:**
```bash
composer require barryvdh/laravel-debugbar --dev
```

**Configuration:**
The package auto-discovers and is only active when `APP_DEBUG=true`.

**Usage:**
- Visit any page in your browser
- Check the debugbar at the bottom of the page
- Click "Queries" tab to see all database queries
- Look for duplicate queries (N+1 problems)
- Check "Timeline" for slow operations

**Key Metrics to Monitor:**
- Total query count (should be low)
- Duplicate queries (indicates missing eager loading)
- Slow queries (>100ms)
- Memory usage
- Request duration

## Performance Testing

### Verify Optimizations

1. **Check N+1 Queries:**
   - Enable Laravel Debugbar
   - Navigate to Orders list page
   - Verify only 1-2 queries load all orders with relationships

2. **Test Cache:**
   ```bash
   # Clear all caches
   php artisan cache:clear

   # First load (slow - builds cache)
   # Second load (fast - uses cache)
   ```

3. **Test Database Indexes:**
   ```bash
   # Check indexes in SQLite
   sqlite3 database/database.sqlite ".schema orders"

   # Or in MySQL
   mysql> SHOW INDEX FROM orders;
   ```

4. **Monitor Production Performance:**
   - Use Laravel Telescope for production monitoring
   - Set up application performance monitoring (APM)
   - Monitor Redis memory usage

## Maintenance

### Cache Clearing Schedule

Clear caches when:
- Deploying new code
- Updating menu items (automatic)
- Changing settings (automatic)
- Modifying routes, configs, or views

```bash
# Clear all application caches
php artisan optimize:clear

# Rebuild caches
php artisan optimize
```

## Performance Benchmarks

Expected improvements after optimization:

- **Dashboard Load Time:** 50-70% faster
- **Orders List:** 60-80% faster (with eager loading)
- **Menu Display:** 90% faster (with caching)
- **Settings Access:** 95% faster (with caching)
- **Database Queries:** Reduced by 60-80%

## Additional Recommendations

1. **Enable OPcache** in production (PHP configuration)
2. **Use a CDN** for static assets
3. **Enable HTTP/2** on your web server
4. **Configure Redis persistence** for cache durability
5. **Monitor with Laravel Telescope** in production
6. **Use Queue workers** for background jobs
7. **Enable compression** (gzip/brotli) in web server

## Troubleshooting

### Cache Issues

**Problem:** Changes not appearing after deployment
**Solution:**
```bash
php artisan optimize:clear
php artisan optimize
```

**Problem:** Settings not updating
**Solution:** Check that `Setting::set()` is being called (it auto-clears cache)

**Problem:** Menu changes not visible
**Solution:** Menu cache clears automatically via model events. If stuck:
```php
MenuCategory::clearMenuCache();
```

### Redis Connection Issues

**Problem:** Redis connection refused
**Solution:**
```bash
# Check Redis is running
redis-cli ping

# Start Redis
sudo systemctl start redis
```

**Problem:** Redis memory full
**Solution:**
```bash
# Clear Redis cache
redis-cli FLUSHDB

# Or configure Redis maxmemory policy in redis.conf
maxmemory-policy allkeys-lru
```

## Documentation References

- **Eager Loading:** `app/Livewire/*.php` components
- **Database Indexes:** `database/migrations/*.php`
- **Menu Caching:** `app/Models/MenuCategory.php:51-72`
- **Settings Caching:** `app/Models/Setting.php:19-61`
- **Query Optimization:** `app/Livewire/Dashboard.php:24-70`

## Support

For performance issues or questions:
1. Check Laravel Debugbar for query problems
2. Review this documentation
3. Verify all caches are properly configured
4. Monitor server resources (CPU, memory, disk I/O)
