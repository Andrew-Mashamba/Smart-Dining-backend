# Laravel Reverb WebSocket Setup Documentation

## Story 24: Setup Laravel Reverb for real-time WebSocket

### Overview
This document provides a comprehensive guide to the Laravel Reverb WebSocket setup for the Hospitality System. Reverb enables real-time broadcasting to Kitchen Display, Bar Display, and Dashboard.

---

## Implementation Summary

### ✅ Completed Tasks

1. **Laravel Reverb Package Installation**
   - Package: `laravel/reverb` v1.7
   - Installed via Composer
   - Location: `composer.json:13`

2. **Configuration Published**
   - Reverb config: `config/reverb.php`
   - Broadcasting config: `config/broadcasting.php`

3. **Environment Variables**
   - `.env` configured with Reverb credentials:
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

4. **Broadcasting Configuration**
   - Default connection set to `reverb`
   - Reverb connection configured in `config/broadcasting.php:33-47`
   - Supports both HTTP and HTTPS schemes

5. **Event Broadcasting**
   - **OrderCreated Event** (`app/Events/OrderCreated.php`)
     - Implements `ShouldBroadcast`
     - Broadcasts to channels: `orders`, `kitchen`, `bar`, `waiter.{id}`
     - Includes order data: order_id, table, status, items_count

   - **OrderStatusUpdated Event** (`app/Events/OrderStatusUpdated.php`)
     - Implements `ShouldBroadcast`
     - Broadcasts to channels: `orders`, `kitchen`, `bar`, `waiter.{id}`
     - Includes: order_id, table, old_status, new_status, updated_at

6. **Channel Authorization**
   - Configured in `routes/channels.php`
   - **Kitchen Channel**: Restricted to kitchen_staff and managers
   - **Bar Channel**: Restricted to bar_staff and managers
   - **Orders Channel**: Accessible to managers, waiters, kitchen_staff, bar_staff
   - **Waiter Channel**: Waiter-specific private channel with ID matching

7. **Frontend WebSocket Client**
   - **Laravel Echo**: v2.3.0 installed
   - **Pusher JS**: v8.4.0 installed
   - Echo configured in `resources/js/echo.js`
   - Uses Reverb broadcaster with proper credentials from Vite env variables

---

## Configuration Files

### 1. Broadcasting Configuration (`config/broadcasting.php`)
```php
'default' => env('BROADCAST_CONNECTION', 'null'),

'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
        'options' => [
            'host' => env('REVERB_HOST'),
            'port' => env('REVERB_PORT', 443),
            'scheme' => env('REVERB_SCHEME', 'https'),
            'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
        ],
    ],
],
```

### 2. Reverb Server Configuration (`config/reverb.php`)
- Server host: `0.0.0.0` (listens on all interfaces)
- Server port: `8080`
- App credentials configured via environment variables
- Allowed origins: `*` (all origins)
- Max connections: Unlimited (configurable via env)

### 3. Channel Routes (`routes/channels.php`)
```php
// Kitchen channel - only kitchen staff and managers
Broadcast::channel('kitchen', function ($user) {
    return in_array($user->role, ['kitchen_staff', 'manager']);
});

// Bar channel - only bar staff and managers
Broadcast::channel('bar', function ($user) {
    return in_array($user->role, ['bar_staff', 'manager']);
});

// Orders channel - managers and authorized staff
Broadcast::channel('orders', function ($user) {
    return in_array($user->role, ['manager', 'waiter', 'kitchen_staff', 'bar_staff']);
});

// Waiter-specific channel
Broadcast::channel('waiter.{waiterId}', function ($user, $waiterId) {
    return (int) $user->id === (int) $waiterId && $user->role === 'waiter';
});
```

### 4. Laravel Echo Configuration (`resources/js/echo.js`)
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

---

## Usage Guide

### Starting the Reverb Server

```bash
# Start Reverb server
php artisan reverb:start

# Start Reverb in debug mode
php artisan reverb:start --debug

# Start Reverb in the background
php artisan reverb:start &
```

### Dispatching Events

#### From Controllers/Services:
```php
use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Order;

// Dispatch OrderCreated event
$order = Order::with(['table', 'items'])->find($orderId);
event(new OrderCreated($order));

// Dispatch OrderStatusUpdated event
$oldStatus = $order->status;
$order->update(['status' => 'preparing']);
event(new OrderStatusUpdated($order, $oldStatus, 'preparing'));
```

### Listening to Events on Frontend

#### In Blade Templates or JavaScript:
```javascript
// Listen to OrderCreated on kitchen channel
Echo.private('kitchen')
    .listen('OrderCreated', (event) => {
        console.log('New order created:', event);
        // Update kitchen display UI
        updateKitchenDisplay(event);
    });

// Listen to OrderStatusUpdated on bar channel
Echo.private('bar')
    .listen('OrderStatusUpdated', (event) => {
        console.log('Order status updated:', event);
        // Update bar display UI
        updateBarDisplay(event);
    });

// Listen to events on orders channel (managers)
Echo.private('orders')
    .listen('OrderCreated', (event) => {
        console.log('New order:', event);
    })
    .listen('OrderStatusUpdated', (event) => {
        console.log('Order updated:', event);
    });

// Listen on waiter-specific channel
Echo.private(`waiter.${userId}`)
    .listen('OrderStatusUpdated', (event) => {
        console.log('Your order was updated:', event);
    });
```

---

## Testing

### Running the Configuration Test

A test script is provided to verify the Reverb setup:

```bash
php test-reverb-config.php
```

This script checks:
- ✓ Broadcast connection is set to 'reverb'
- ✓ Reverb credentials are configured
- ✓ Events exist and implement ShouldBroadcast
- ✓ Channel routes are configured
- ✓ Laravel Echo is configured
- ✓ NPM packages are installed

### Manual Testing

1. **Start the Reverb server:**
   ```bash
   php artisan reverb:start
   ```

2. **In browser console (authenticated user):**
   ```javascript
   // Listen to kitchen channel
   Echo.private('kitchen').listen('OrderCreated', (e) => {
       console.log('Order created:', e);
   });
   ```

3. **Dispatch an event (in tinker or controller):**
   ```bash
   php artisan tinker

   >>> $order = App\Models\Order::with(['table', 'items'])->first();
   >>> event(new App\Events\OrderCreated($order));
   ```

4. **Check browser console** - you should see the event data logged

---

## Troubleshooting

### Reverb Server Won't Start

**Error:** `Address already in use (EADDRINUSE)`
- **Solution:** Reverb is already running. Kill the existing process:
  ```bash
  lsof -i :8080
  kill <PID>
  ```

### Events Not Broadcasting

1. **Check broadcast connection:**
   ```bash
   php artisan config:cache
   php artisan config:clear
   ```

2. **Verify queue is running (if using queue driver):**
   ```bash
   php artisan queue:work
   ```

3. **Check Reverb server logs** for any errors

### Frontend Not Receiving Events

1. **Check browser console** for WebSocket connection errors

2. **Verify authentication** - user must be authenticated to join private channels

3. **Check channel authorization** in `routes/channels.php`

4. **Rebuild frontend assets:**
   ```bash
   npm run build
   ```

---

## Channel Summary

| Channel | Authorization | Purpose |
|---------|---------------|---------|
| `private-kitchen` | kitchen_staff, manager | Kitchen Display updates |
| `private-bar` | bar_staff, manager | Bar Display updates |
| `private-orders` | manager, waiter, kitchen_staff, bar_staff | General order updates |
| `private-waiter.{id}` | Specific waiter | Waiter-specific notifications |

---

## Event Broadcast Data

### OrderCreated
```json
{
    "order_id": 123,
    "table": "Table 5",
    "status": "pending",
    "items_count": 3
}
```

### OrderStatusUpdated
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

## Production Considerations

### Security
1. **Use HTTPS in production** - set `REVERB_SCHEME=https`
2. **Restrict allowed_origins** in `config/reverb.php`
3. **Implement proper channel authorization**
4. **Use environment-specific credentials**

### Performance
1. **Enable Redis scaling** for multi-server deployments
2. **Set appropriate max_connections** limit
3. **Monitor server resources**
4. **Use queue worker for broadcasting** to avoid blocking requests

### Deployment
1. **Process manager** - use Supervisor to keep Reverb running:
   ```ini
   [program:reverb]
   command=php /path/to/artisan reverb:start
   autostart=true
   autorestart=true
   ```

2. **Reverse proxy** - use Nginx to proxy WebSocket connections:
   ```nginx
   location /reverb {
       proxy_pass http://localhost:8080;
       proxy_http_version 1.1;
       proxy_set_header Upgrade $http_upgrade;
       proxy_set_header Connection "Upgrade";
   }
   ```

---

## Acceptance Criteria Status

All acceptance criteria have been met:

- [x] Install Reverb: `composer require laravel/reverb`
- [x] Publish config: `php artisan reverb:install`
- [x] Configure .env: BROADCAST_CONNECTION=reverb, REVERB_APP_ID, REVERB_APP_KEY, REVERB_APP_SECRET
- [x] Update config/broadcasting.php with reverb connection
- [x] Create events: OrderCreated.php, OrderStatusUpdated.php
- [x] Implement ShouldBroadcast on events with broadcastOn() returning channel names
- [x] Channel routes: routes/channels.php with auth checks for kitchen, bar, manager channels
- [x] Install Laravel Echo and Pusher JS: `npm install --save-dev laravel-echo pusher-js`
- [x] Configure Echo in resources/js/app.js with Reverb settings
- [x] Start Reverb server: `php artisan reverb:start`
- [x] Test: dispatch OrderCreated event and verify broadcast

---

## Additional Resources

- [Laravel Reverb Documentation](https://laravel.com/docs/reverb)
- [Laravel Broadcasting Documentation](https://laravel.com/docs/broadcasting)
- [Laravel Echo Documentation](https://laravel.com/docs/broadcasting#client-side-installation)

---

**Implementation Date:** 2026-02-06
**Story Priority:** 24
**Status:** ✅ Complete
