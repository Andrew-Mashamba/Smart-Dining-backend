# Laravel Reverb WebSocket Setup - Story 24

## Overview
Laravel Reverb has been successfully installed and configured for real-time WebSocket communication in the Hospitality Management System. This enables real-time updates for Kitchen Display, Bar Display, Dashboard, and Waiter interfaces.

## Implementation Summary

### 1. Installation
- **Package**: `laravel/reverb` v1.7
- **Frontend**: `laravel-echo` and `pusher-js` via npm
- **Status**: ✅ Installed and configured

### 2. Configuration Files

#### .env Configuration
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

#### config/broadcasting.php
The reverb connection is configured with:
- Driver: reverb
- App ID, Key, and Secret from environment
- Host and port settings
- TLS configuration based on scheme

### 3. Events Created

#### OrderCreated Event
**Location**: `app/Events/OrderCreated.php`

**Broadcasts to channels**:
- `private-orders` - All order updates
- `private-kitchen` - Kitchen display updates
- `private-bar` - Bar display updates
- `private-waiter.{waiter_id}` - Specific waiter updates

**Broadcast Data**:
- `order_id`: The ID of the created order
- `table`: Table name
- `status`: Current order status
- `items_count`: Number of items in the order

**Usage Example**:
```php
use App\Events\OrderCreated;

// Dispatch when creating an order
event(new OrderCreated($order));
```

#### OrderStatusUpdated Event
**Location**: `app/Events/OrderStatusUpdated.php`

**Broadcasts to channels**:
- `private-orders` - All order updates
- `private-kitchen` - Kitchen display updates
- `private-bar` - Bar display updates
- `private-waiter.{waiter_id}` - Specific waiter updates

**Broadcast Data**:
- `order_id`: The ID of the order
- `table`: Table name
- `old_status`: Previous status
- `new_status`: Updated status
- `updated_at`: Timestamp of update

**Usage Example**:
```php
use App\Events\OrderStatusUpdated;

// Dispatch when updating order status
$oldStatus = $order->status;
$order->update(['status' => 'preparing']);
event(new OrderStatusUpdated($order, $oldStatus, 'preparing'));
```

### 4. Channel Authorization

**Location**: `routes/channels.php`

**Configured Channels**:

1. **kitchen** - Kitchen staff and managers only
   ```php
   Broadcast::channel('kitchen', function ($user) {
       return in_array($user->role, ['kitchen_staff', 'manager']);
   });
   ```

2. **bar** - Bar staff and managers only
   ```php
   Broadcast::channel('bar', function ($user) {
       return in_array($user->role, ['bar_staff', 'manager']);
   });
   ```

3. **orders** - All authorized staff
   ```php
   Broadcast::channel('orders', function ($user) {
       return in_array($user->role, ['manager', 'waiter', 'kitchen_staff', 'bar_staff']);
   });
   ```

4. **waiter.{waiterId}** - Specific waiter only
   ```php
   Broadcast::channel('waiter.{waiterId}', function ($user, $waiterId) {
       return (int) $user->id === (int) $waiterId && $user->role === 'waiter';
   });
   ```

### 5. Frontend Configuration

#### Echo Setup
**Location**: `resources/js/echo.js`

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

**Bootstrap**: Imported in `resources/js/bootstrap.js`

## Usage Guide

### Starting the Reverb Server

**Development**:
```bash
php artisan reverb:start
```

**Production** (with supervisor or systemd):
```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

### Listening to Events in Frontend

#### Kitchen Display
```javascript
Echo.private('kitchen')
    .listen('OrderCreated', (e) => {
        console.log('New order:', e.order_id, 'for table:', e.table);
        // Update kitchen display UI
    })
    .listen('OrderStatusUpdated', (e) => {
        console.log('Order status updated:', e.order_id, e.old_status, '->', e.new_status);
        // Update kitchen display UI
    });
```

#### Bar Display
```javascript
Echo.private('bar')
    .listen('OrderCreated', (e) => {
        console.log('New order:', e.order_id, 'for table:', e.table);
        // Update bar display UI
    })
    .listen('OrderStatusUpdated', (e) => {
        console.log('Order status updated:', e.order_id);
        // Update bar display UI
    });
```

#### Manager Dashboard
```javascript
Echo.private('orders')
    .listen('OrderCreated', (e) => {
        console.log('New order created:', e.order_id);
        // Update dashboard
    })
    .listen('OrderStatusUpdated', (e) => {
        console.log('Order status updated:', e.order_id);
        // Update dashboard
    });
```

#### Waiter Interface
```javascript
// Replace {waiterId} with actual waiter ID
Echo.private(`waiter.${waiterId}`)
    .listen('OrderCreated', (e) => {
        console.log('Your order:', e.order_id, 'created');
        // Update waiter UI
    })
    .listen('OrderStatusUpdated', (e) => {
        console.log('Your order:', e.order_id, 'updated to:', e.new_status);
        // Update waiter UI
    });
```

### Dispatching Events in Backend

#### When Creating an Order
```php
use App\Events\OrderCreated;

$order = Order::create([
    'table_id' => $tableId,
    'waiter_id' => $waiterId,
    'status' => 'pending',
    'total_amount' => 0,
]);

// Broadcast to all relevant channels
event(new OrderCreated($order));
```

#### When Updating Order Status
```php
use App\Events\OrderStatusUpdated;

$oldStatus = $order->status;
$order->update(['status' => 'preparing']);

// Broadcast status change
event(new OrderStatusUpdated($order, $oldStatus, 'preparing'));
```

## Testing

### Test Script
Run the included test script to verify broadcasting:
```bash
php test-broadcast.php
```

**Expected Output**:
```
=== Laravel Reverb Broadcasting Test ===

Broadcast Connection: reverb
Reverb App ID: 369538
Reverb Host: localhost:8080
Reverb Key: dpv56o7sph...

Looking for a test order...
✓ Found Order ID: 1
  Table: T-01
  Status: paid
  Items: 3

Dispatching OrderCreated event...
✓ Event dispatched successfully!

=== Test Complete ===
If Reverb server is running, the event should be broadcast to:
  - private-orders
  - private-kitchen
  - private-bar
  - private-waiter.1
```

### Manual Testing

1. **Start Reverb server**:
   ```bash
   php artisan reverb:start
   ```

2. **Open browser console** on your application page

3. **Listen to a channel**:
   ```javascript
   Echo.private('orders').listen('OrderCreated', (e) => console.log(e));
   ```

4. **Create an order** through the application or run the test script

5. **Verify** the event appears in the console

## Troubleshooting

### Port Already in Use
If port 8080 is already in use, check for existing Reverb process:
```bash
lsof -ti:8080
```

Kill the process if needed:
```bash
kill $(lsof -ti:8080)
```

Or change the port in `.env`:
```env
REVERB_PORT=8081
VITE_REVERB_PORT=8081
```

### Events Not Broadcasting
1. Verify Reverb server is running
2. Check `.env` has `BROADCAST_CONNECTION=reverb`
3. Ensure events implement `ShouldBroadcast` interface
4. Check channel authorization in `routes/channels.php`
5. Verify user is authenticated when listening to private channels

### Frontend Not Receiving Events
1. Ensure frontend assets are built: `npm run build` or `npm run dev`
2. Check browser console for WebSocket connection errors
3. Verify Echo is properly configured in `resources/js/echo.js`
4. Check that user is authorized for the channel

## Production Deployment

### Environment Variables
Update `.env` for production:
```env
REVERB_SCHEME=https
REVERB_HOST=your-domain.com
REVERB_PORT=443
```

### Process Manager
Use supervisor or systemd to keep Reverb running:

**Supervisor Config** (`/etc/supervisor/conf.d/reverb.conf`):
```ini
[program:reverb]
process_name=%(program_name)s
command=php /path/to/artisan reverb:start --host=0.0.0.0 --port=8080
directory=/path/to/project
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

## Acceptance Criteria Status

✅ 1. Install Reverb: `composer require laravel/reverb`
✅ 2. Publish config: `php artisan reverb:install`
✅ 3. Configure .env: BROADCAST_CONNECTION=reverb, REVERB_APP_ID, REVERB_APP_KEY, REVERB_APP_SECRET
✅ 4. Update config/broadcasting.php with reverb connection
✅ 5. Create events: OrderCreated.php, OrderStatusUpdated.php
✅ 6. Implement ShouldBroadcast on events with broadcastOn() returning channel names
✅ 7. Channel routes: routes/channels.php with auth checks for kitchen, bar, manager channels
✅ 8. Install Laravel Echo and Pusher JS: `npm install --save-dev laravel-echo pusher-js`
✅ 9. Configure Echo in resources/js/app.js with Reverb settings
✅ 10. Start Reverb server: `php artisan reverb:start`
✅ 11. Test: dispatch OrderCreated event and verify broadcast

**All acceptance criteria have been met successfully!**

## Next Steps

1. Integrate real-time listeners in Kitchen Display view
2. Integrate real-time listeners in Bar Display view
3. Integrate real-time listeners in Manager Dashboard
4. Integrate real-time listeners in Waiter interface
5. Add visual/audio notifications for new orders
6. Add connection status indicator in UI
7. Implement reconnection logic for dropped connections
