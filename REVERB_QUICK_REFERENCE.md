# Laravel Reverb - Quick Reference Guide

## Quick Start Commands

```bash
# Start Reverb server
php artisan reverb:start

# Start in debug mode
php artisan reverb:start --debug

# Run configuration test
php test-reverb-config.php

# Test broadcasting with actual data
php test-broadcast.php
```

---

## Broadcasting Events

### From Controller/Service

```php
use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;

// New order created
event(new OrderCreated($order));

// Order status changed
event(new OrderStatusUpdated($order, 'pending', 'preparing'));
```

---

## Frontend - Listening to Events

### Kitchen Display

```javascript
Echo.private('kitchen')
    .listen('OrderCreated', (event) => {
        // New order for kitchen
        console.log('New kitchen order:', event.order_id);
    })
    .listen('OrderStatusUpdated', (event) => {
        // Order status changed
        console.log('Status changed:', event.old_status, '→', event.new_status);
    });
```

### Bar Display

```javascript
Echo.private('bar')
    .listen('OrderCreated', (event) => {
        // New beverage order
        console.log('New bar order:', event.order_id);
    })
    .listen('OrderStatusUpdated', (event) => {
        // Beverage order updated
        console.log('Bar order updated:', event.order_id);
    });
```

### Manager Dashboard

```javascript
Echo.private('orders')
    .listen('OrderCreated', (event) => {
        // All orders - managers view
        console.log('New order:', event);
    })
    .listen('OrderStatusUpdated', (event) => {
        // Order status changes
        console.log('Order updated:', event);
    });
```

### Waiter Notifications

```javascript
// Replace {userId} with actual waiter ID
Echo.private(`waiter.${userId}`)
    .listen('OrderStatusUpdated', (event) => {
        // Orders for this specific waiter
        console.log('Your order updated:', event.table);
    });
```

---

## Event Data Structures

### OrderCreated Event

```json
{
    "order_id": 123,
    "table": "Table 5",
    "status": "pending",
    "items_count": 3
}
```

### OrderStatusUpdated Event

```json
{
    "order_id": 123,
    "table": "Table 5",
    "old_status": "pending",
    "new_status": "preparing",
    "updated_at": "2024-01-15T10:30:00.000Z"
}
```

---

## Channel Authorization

| Channel | Roles Allowed |
|---------|---------------|
| `kitchen` | kitchen_staff, manager |
| `bar` | bar_staff, manager |
| `orders` | manager, waiter, kitchen_staff, bar_staff |
| `waiter.{id}` | Specific waiter only |

---

## Troubleshooting

### Server Won't Start - Port In Use

```bash
# Find process using port 8080
lsof -i :8080

# Kill the process
kill <PID>

# Then start server
php artisan reverb:start
```

### Events Not Broadcasting

```bash
# Clear config cache
php artisan config:clear

# Verify broadcast connection
php artisan tinker
>>> config('broadcasting.default')
# Should return: "reverb"
```

### Frontend Not Receiving Events

1. Check browser console for WebSocket errors
2. Verify user is authenticated
3. Check user role has permission for channel
4. Rebuild assets: `npm run build`

---

## Environment Variables

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=369538
REVERB_APP_KEY=dpv56o7sphki4t7j5nq3
REVERB_APP_SECRET=mrthb4xsvwyjasgogltp
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

---

## Testing Broadcasting

### Using Tinker

```bash
php artisan tinker

# Get a test order
>>> $order = App\Models\Order::with(['table', 'items'])->first()

# Dispatch event
>>> event(new App\Events\OrderCreated($order))

# Check browser console - should see the event
```

### Using Browser Console

```javascript
// Must be on a page where user is authenticated

// Listen to kitchen channel (if user is kitchen_staff or manager)
Echo.private('kitchen')
    .listen('OrderCreated', (e) => console.log('Kitchen Order:', e))
    .listen('OrderStatusUpdated', (e) => console.log('Status Update:', e));

// Check connection status
window.Echo.connector.pusher.connection.state
// Should be: "connected"
```

---

## File Locations

| Item | Location |
|------|----------|
| OrderCreated Event | `app/Events/OrderCreated.php` |
| OrderStatusUpdated Event | `app/Events/OrderStatusUpdated.php` |
| Channel Routes | `routes/channels.php` |
| Broadcasting Config | `config/broadcasting.php` |
| Reverb Config | `config/reverb.php` |
| Echo Configuration | `resources/js/echo.js` |
| Environment Variables | `.env` |

---

## Common Patterns

### Dispatch After Database Transaction

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function () use ($order) {
    $order->save();
    event(new OrderCreated($order));
});
```

### Dispatch with Queue (Background)

```php
// Event already implements ShouldBroadcast
// Just dispatch it, Laravel handles queuing
event(new OrderCreated($order));
```

### Conditional Broadcasting

```php
// In the event class
public function broadcastWhen(): bool
{
    return $this->order->status !== 'cancelled';
}
```

---

## Production Checklist

- [ ] Set `REVERB_SCHEME=https` in production
- [ ] Configure allowed_origins in `config/reverb.php`
- [ ] Set up Supervisor for Reverb process
- [ ] Configure Nginx reverse proxy for WebSocket
- [ ] Enable Redis scaling for multi-server
- [ ] Set appropriate max_connections limit
- [ ] Monitor Reverb server resources
- [ ] Use queue worker for background broadcasting

---

## Useful Artisan Commands

```bash
# Start Reverb server
php artisan reverb:start

# Clear cached config
php artisan config:clear

# Cache config for production
php artisan config:cache

# View routes including broadcast routes
php artisan route:list

# Start queue worker (if using queued broadcasting)
php artisan queue:work
```

---

## Additional Resources

- Full Documentation: `REVERB_SETUP_DOCUMENTATION.md`
- Completion Summary: `STORY_24_COMPLETION_SUMMARY.md`
- Laravel Reverb Docs: https://laravel.com/docs/reverb
- Laravel Broadcasting: https://laravel.com/docs/broadcasting
