# Laravel Reverb Setup - Story 24 Complete

## Installation Summary

All acceptance criteria for Story 24 have been successfully implemented:

### ✅ Completed Tasks

1. **Installed Laravel Reverb Package**
   - Package: `laravel/reverb` (v1.7)
   - Command used: `composer require laravel/reverb`

2. **Published Reverb Configuration**
   - Command: `php artisan reverb:install`
   - Created: `config/reverb.php`

3. **Configured Environment Variables**
   - File: `.env`
   - Settings:
     ```env
     BROADCAST_CONNECTION=reverb
     REVERB_APP_ID=369538
     REVERB_APP_KEY=dpv56o7sphki4t7j5nq3
     REVERB_APP_SECRET=mrthb4xsvwyjasgogltp
     REVERB_HOST=localhost
     REVERB_PORT=8080
     REVERB_SCHEME=http

     VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
     VITE_REVERB_HOST="${REVERB_HOST}"
     VITE_REVERB_PORT="${REVERB_PORT}"
     VITE_REVERB_SCHEME="${REVERB_SCHEME}"
     ```

4. **Updated Broadcasting Configuration**
   - File: `config/broadcasting.php`
   - Reverb connection configured with all required settings

5. **Created Broadcast Events**
   - `app/Events/OrderCreated.php` - Implements `ShouldBroadcast`
   - `app/Events/OrderStatusUpdated.php` - Implements `ShouldBroadcast`
   - Both events broadcast to multiple channels:
     - `orders` (private channel)
     - `kitchen` (private channel)
     - `bar` (private channel)
     - `waiter.{waiterId}` (private channel)

6. **Configured Channel Authorization**
   - File: `routes/channels.php`
   - Authorization rules:
     - **kitchen**: Only kitchen staff and managers
     - **bar**: Only bar staff and managers
     - **orders**: Managers and authorized staff (waiter, kitchen_staff, bar_staff)
     - **waiter.{waiterId}**: Only the specific waiter

7. **Installed Frontend Dependencies**
   - `laravel-echo` - Laravel Echo for WebSocket client
   - `pusher-js` - Pusher client (required by Reverb)
   - Command: `npm install --save-dev laravel-echo pusher-js`

8. **Configured Laravel Echo**
   - File: `resources/js/echo.js`
   - Configuration:
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

9. **Reverb Server Ready**
   - Start command: `php artisan reverb:start`
   - Server runs on: `http://localhost:8080`

10. **Test Command Created**
    - File: `app/Console/Commands/TestBroadcasting.php`
    - Command: `php artisan test:broadcasting`

## How to Start Reverb Server

```bash
# Start the Reverb WebSocket server
php artisan reverb:start

# In a separate terminal, start queue workers
php artisan queue:work
```

## How to Test Broadcasting

### Option 1: Using the Test Command

```bash
# Make sure you have orders in your database first
php artisan test:broadcasting
```

### Option 2: Manual Testing with Tinker

```bash
php artisan tinker
```

Then in Tinker:

```php
// Create a test order (adjust based on your data)
$order = App\Models\Order::first();

// Dispatch OrderCreated event
event(new App\Events\OrderCreated($order));

// Dispatch OrderStatusUpdated event
$oldStatus = $order->status;
$order->update(['status' => 'preparing']);
event(new App\Events\OrderStatusUpdated($order, $oldStatus, 'preparing'));
```

### Option 3: Testing from Frontend

Add this to your JavaScript (e.g., in a Livewire component or Alpine.js):

```javascript
// Listen for OrderCreated events on the kitchen channel
Echo.private('kitchen')
    .listen('OrderCreated', (e) => {
        console.log('New order created:', e);
        // Update your UI here
    });

// Listen for OrderStatusUpdated events
Echo.private('kitchen')
    .listen('OrderStatusUpdated', (e) => {
        console.log('Order status updated:', e);
        // Update your UI here
    });
```

### Option 4: Testing WebSocket Connection

1. Open your browser's developer console
2. Navigate to your Laravel application
3. Check the Network tab for WebSocket connections
4. You should see a connection to `ws://localhost:8080`

## Broadcasting Channels

### Available Channels

1. **orders** - All orders channel
   - Accessible to: managers, waiters, kitchen_staff, bar_staff

2. **kitchen** - Kitchen display channel
   - Accessible to: kitchen_staff, managers

3. **bar** - Bar display channel
   - Accessible to: bar_staff, managers

4. **waiter.{waiterId}** - Individual waiter channel
   - Accessible to: specific waiter only

## Event Payloads

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
  "updated_at": "2024-01-30T12:34:56.000000Z"
}
```

## Integration Points

### Kitchen Display System

The Kitchen Display should listen on the `kitchen` channel:

```javascript
Echo.private('kitchen')
    .listen('OrderCreated', (e) => {
        // Add new order to kitchen display
    })
    .listen('OrderStatusUpdated', (e) => {
        // Update order status on display
    });
```

### Bar Display System

The Bar Display should listen on the `bar` channel:

```javascript
Echo.private('bar')
    .listen('OrderCreated', (e) => {
        // Add new order to bar display (if it has bar items)
    })
    .listen('OrderStatusUpdated', (e) => {
        // Update order status on display
    });
```

### Manager Dashboard

The Dashboard should listen on the `orders` channel:

```javascript
Echo.private('orders')
    .listen('OrderCreated', (e) => {
        // Add new order to dashboard
    })
    .listen('OrderStatusUpdated', (e) => {
        // Update order status on dashboard
    });
```

## Production Considerations

### Running Reverb in Production

1. Use a process manager like Supervisor to keep Reverb running
2. Configure TLS/SSL for secure WebSocket connections (wss://)
3. Update REVERB_SCHEME to 'https' in production .env
4. Consider using Reverb's scaling options with Redis

### Supervisor Configuration Example

```ini
[program:reverb]
command=php /path/to/laravel-app/artisan reverb:start
directory=/path/to/laravel-app
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/reverb.log
```

## Troubleshooting

### WebSocket Connection Fails

1. Ensure Reverb server is running: `php artisan reverb:start`
2. Check REVERB_HOST and REVERB_PORT in .env
3. Verify firewall allows connections on port 8080
4. Check browser console for connection errors

### Events Not Broadcasting

1. Verify BROADCAST_CONNECTION=reverb in .env
2. Ensure queue workers are running: `php artisan queue:work`
3. Check that events implement `ShouldBroadcast` interface
4. Verify channel authorization in routes/channels.php

### Authentication Issues

1. Ensure user is authenticated before subscribing to private channels
2. Check channel authorization logic in routes/channels.php
3. Verify user roles match authorization requirements

## Next Steps

To use broadcasting in your application:

1. Build your frontend components (Kitchen Display, Bar Display, Dashboard)
2. Add Echo listeners in your JavaScript/Livewire components
3. Dispatch events when orders are created or updated
4. Test real-time updates across multiple browser windows
5. Deploy with proper SSL/TLS configuration

## Resources

- [Laravel Reverb Documentation](https://laravel.com/docs/11.x/reverb)
- [Laravel Broadcasting Documentation](https://laravel.com/docs/11.x/broadcasting)
- [Laravel Echo Documentation](https://laravel.com/docs/11.x/broadcasting#client-side-installation)

---

**Story Status:** ✅ **COMPLETE**

All acceptance criteria have been met:
- ✅ Laravel Reverb installed
- ✅ Configuration published
- ✅ Environment variables configured
- ✅ Broadcasting config updated
- ✅ Events created with ShouldBroadcast
- ✅ Channel authorization configured
- ✅ Laravel Echo and Pusher JS installed
- ✅ Echo configured in JavaScript
- ✅ Reverb server tested and ready
- ✅ Test command created for verification
