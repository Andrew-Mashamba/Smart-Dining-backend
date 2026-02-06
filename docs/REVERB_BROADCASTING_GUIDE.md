# Laravel Reverb Broadcasting Guide

## Overview
This guide explains how Laravel Reverb is configured for real-time WebSocket broadcasting in the Hospitality System. Reverb enables real-time updates for Kitchen Display, Bar Display, and Dashboard.

## Architecture

### Broadcasting Flow
1. **Event Dispatch**: Application dispatches events (OrderCreated, OrderStatusUpdated)
2. **Reverb Server**: Broadcasts events to subscribed channels
3. **Laravel Echo**: Frontend receives events via WebSocket
4. **UI Updates**: Application updates displays in real-time

## Configuration

### Environment Variables (.env)
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

### Configuration Files

#### config/broadcasting.php
Reverb connection is configured with credentials from .env:
```php
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
]
```

#### config/reverb.php
Server configuration for hosting Reverb:
- Host: 0.0.0.0 (all interfaces)
- Port: 8080
- Max connections and message size limits
- Scaling support via Redis (disabled by default)

## Broadcasting Channels

### Channel Authorization (routes/channels.php)

#### kitchen
- **Purpose**: Broadcasts orders to kitchen staff
- **Authorization**: kitchen_staff and manager roles
```php
Broadcast::channel('kitchen', function ($user) {
    return in_array($user->role, ['kitchen_staff', 'manager']);
});
```

#### bar
- **Purpose**: Broadcasts orders to bar staff
- **Authorization**: bar_staff and manager roles
```php
Broadcast::channel('bar', function ($user) {
    return in_array($user->role, ['bar_staff', 'manager']);
});
```

#### dashboard
- **Purpose**: Broadcasts all orders to management dashboard
- **Authorization**: manager role only
```php
Broadcast::channel('dashboard', function ($user) {
    return $user->role === 'manager';
});
```

#### orders
- **Purpose**: Broadcasts to all authorized staff
- **Authorization**: manager, waiter, kitchen_staff, bar_staff roles
```php
Broadcast::channel('orders', function ($user) {
    return in_array($user->role, ['manager', 'waiter', 'kitchen_staff', 'bar_staff']);
});
```

#### waiter.{waiterId}
- **Purpose**: Personal channel for individual waiters
- **Authorization**: Waiter must be the same user
```php
Broadcast::channel('waiter.{waiterId}', function ($user, $waiterId) {
    return (int) $user->id === (int) $waiterId && $user->role === 'waiter';
});
```

## Events

### OrderCreated Event
Location: `app/Events/OrderCreated.php`

**Purpose**: Broadcast when a new order is created

**Channels**:
- `orders` - All staff
- `kitchen` - Kitchen staff (if order has kitchen items)
- `bar` - Bar staff (if order has bar items)
- `waiter.{waiter_id}` - Assigned waiter
- `dashboard` - Managers

**Broadcast Data**:
```php
[
    'order_id' => $this->order->id,
    'table' => $this->order->table->name,
    'status' => $this->order->status,
    'items_count' => $this->order->orderItems->count(),
]
```

**Usage**:
```php
use App\Events\OrderCreated;

event(new OrderCreated($order));
```

### OrderStatusUpdated Event
Location: `app/Events/OrderStatusUpdated.php`

**Purpose**: Broadcast when order status changes

**Channels**:
- `orders` - All staff
- `kitchen` - Kitchen staff
- `bar` - Bar staff
- `waiter.{waiter_id}` - Assigned waiter

**Broadcast Data**:
```php
[
    'order_id' => $this->order->id,
    'table' => $this->order->table->name,
    'old_status' => $this->oldStatus,
    'new_status' => $this->newStatus,
    'updated_at' => $this->order->updated_at?->toISOString(),
]
```

**Usage**:
```php
use App\Events\OrderStatusUpdated;

$oldStatus = $order->status;
$order->update(['status' => 'preparing']);
event(new OrderStatusUpdated($order, $oldStatus, 'preparing'));
```

## Frontend Integration

### Laravel Echo Configuration
Location: `resources/js/echo.js`

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

### Listening to Events

#### Kitchen Display Example
```javascript
// Subscribe to kitchen channel
Echo.private('kitchen')
    .listen('.OrderCreated', (e) => {
        console.log('New order:', e.order_id);
        // Update kitchen display
        addOrderToKitchenDisplay(e);
    })
    .listen('.OrderStatusUpdated', (e) => {
        console.log('Order status updated:', e.order_id);
        // Update order status in display
        updateOrderStatus(e.order_id, e.new_status);
    });
```

#### Dashboard Example
```javascript
// Subscribe to dashboard channel
Echo.private('dashboard')
    .listen('.OrderCreated', (e) => {
        console.log('New order for dashboard:', e.order_id);
        // Update dashboard metrics
        updateDashboard(e);
    });
```

#### Waiter Personal Channel Example
```javascript
// Subscribe to waiter's personal channel
const waiterId = document.querySelector('[data-waiter-id]').value;

Echo.private(`waiter.${waiterId}`)
    .listen('.OrderCreated', (e) => {
        console.log('New order assigned to you:', e.order_id);
        // Show notification
        showNotification('New order assigned!');
    });
```

## Running Reverb Server

### Development
Start the Reverb server in development mode:
```bash
php artisan reverb:start
```

With debug output:
```bash
php artisan reverb:start --debug
```

Custom host and port:
```bash
php artisan reverb:start --host=127.0.0.1 --port=8080
```

### Production
In production, run Reverb as a background process using a process manager like Supervisor:

#### Supervisor Configuration
Create `/etc/supervisor/conf.d/reverb.conf`:
```ini
[program:reverb]
command=php /path/to/laravel-app/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/reverb.log
```

Reload supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb
```

## Testing

### Test Command
A test command is provided to dispatch test events:

```bash
# Test OrderCreated event
php artisan broadcast:test

# Test OrderStatusUpdated event
php artisan broadcast:test --event=order-status-updated
```

Location: `app/Console/Commands/TestBroadcast.php`

### Manual Testing Steps

1. **Start Reverb Server**:
   ```bash
   php artisan reverb:start --debug
   ```

2. **Open Browser Console** on your application page

3. **Subscribe to Channel**:
   ```javascript
   Echo.private('kitchen')
       .listen('.OrderCreated', (e) => {
           console.log('Received event:', e);
       });
   ```

4. **Dispatch Test Event**:
   ```bash
   php artisan broadcast:test
   ```

5. **Verify** event appears in browser console

### Debugging

#### Check Reverb Server Logs
When running with `--debug`, you'll see:
- Connection events
- Channel subscriptions
- Message broadcasts
- Disconnections

#### Common Issues

**Issue**: Events not received on frontend
- **Solution**: Verify Reverb server is running
- **Solution**: Check browser console for WebSocket connection errors
- **Solution**: Ensure VITE environment variables are set correctly

**Issue**: Authorization failed for private channels
- **Solution**: Verify user is authenticated
- **Solution**: Check channel authorization logic in routes/channels.php
- **Solution**: Ensure user has correct role

**Issue**: WebSocket connection refused
- **Solution**: Verify REVERB_HOST and REVERB_PORT in .env
- **Solution**: Check firewall rules allow port 8080
- **Solution**: Ensure Reverb server is accessible from client

## Best Practices

### 1. Queue Event Broadcasting
For better performance, queue event broadcasting:

```php
class OrderCreated implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    // ...
}
```

### 2. Limit Broadcast Data
Only send necessary data in broadcastWith():

```php
public function broadcastWith(): array
{
    return [
        'order_id' => $this->order->id,
        'status' => $this->order->status,
        // Don't send full order object
    ];
}
```

### 3. Use Private Channels
Always use private channels for sensitive data:

```php
public function broadcastOn(): array
{
    return [
        new PrivateChannel('kitchen'), // Requires auth
    ];
}
```

### 4. Handle Connection Failures
Implement reconnection logic in frontend:

```javascript
Echo.connector.pusher.connection.bind('state_change', (states) => {
    if (states.current === 'disconnected') {
        console.warn('WebSocket disconnected, attempting reconnect...');
    }
});
```

### 5. Monitor Reverb Performance
Use Laravel Pulse or custom monitoring for:
- Active connections
- Messages per second
- Channel subscriptions
- Memory usage

## Security Considerations

1. **Always Authenticate Private Channels**: Never expose sensitive data on public channels
2. **Validate Channel Authorization**: Properly check user roles and permissions
3. **Use TLS in Production**: Set REVERB_SCHEME=https in production
4. **Limit Connection Count**: Set REVERB_APP_MAX_CONNECTIONS in production
5. **Rate Limit**: Implement rate limiting for event dispatching
6. **Secure Credentials**: Keep REVERB_APP_SECRET secure and never commit to version control

## Related Files

- `app/Events/OrderCreated.php` - OrderCreated event
- `app/Events/OrderStatusUpdated.php` - OrderStatusUpdated event
- `routes/channels.php` - Channel authorization
- `config/broadcasting.php` - Broadcasting configuration
- `config/reverb.php` - Reverb server configuration
- `resources/js/echo.js` - Laravel Echo setup
- `app/Console/Commands/TestBroadcast.php` - Test command

## Additional Resources

- [Laravel Reverb Documentation](https://laravel.com/docs/11.x/reverb)
- [Laravel Broadcasting Documentation](https://laravel.com/docs/11.x/broadcasting)
- [Laravel Echo Documentation](https://laravel.com/docs/11.x/broadcasting#client-side-installation)
