# Laravel Reverb WebSocket Testing Guide

## Story 24: Setup Laravel Reverb for real-time WebSocket

This document provides instructions for testing the Laravel Reverb setup for real-time broadcasting.

## Prerequisites

All components have been installed and configured:
- Laravel Reverb package installed
- Configuration files updated
- Events created (OrderCreated, OrderStatusUpdated)
- Channel routes configured with authentication
- Laravel Echo and Pusher JS installed
- Frontend assets built

## Starting the Reverb Server

To start the Reverb WebSocket server, run:

```bash
cd /Volumes/DATA/PROJECTS/HOSPITALITYSYSTEM/laravel-app
php artisan reverb:start
```

The server will start on port 8080 (as configured in .env).

## Testing the Broadcast Setup

### Method 1: Using the Test Page

1. Start the Reverb server in one terminal:
   ```bash
   php artisan reverb:start
   ```

2. Start the Laravel development server in another terminal:
   ```bash
   php artisan serve
   ```

3. Open your browser and navigate to:
   ```
   http://localhost:8000/test-broadcast
   ```

4. Make sure you're logged in as a user with appropriate roles (kitchen_staff, bar_staff, or manager).

5. Open the browser console to see connection logs.

### Method 2: Using Tinker to Dispatch Events

1. Open a new terminal and start tinker:
   ```bash
   cd /Volumes/DATA/PROJECTS/HOSPITALITYSYSTEM/laravel-app
   php artisan tinker
   ```

2. Test OrderCreated event:
   ```php
   $order = App\Models\Order::first();
   if ($order) {
       event(new App\Events\OrderCreated($order));
       echo "OrderCreated event dispatched!";
   } else {
       echo "No orders found. Create an order first.";
   }
   ```

3. Test OrderStatusUpdated event:
   ```php
   $order = App\Models\Order::first();
   if ($order) {
       event(new App\Events\OrderStatusUpdated($order, 'pending', 'preparing'));
       echo "OrderStatusUpdated event dispatched!";
   }
   ```

### Method 3: Testing Through Application Workflow

1. Start the Reverb server and Laravel development server.

2. Log in as a waiter and create a new order:
   - Navigate to Orders → Create Order
   - Select a table, add menu items
   - Submit the order

3. Open the Kitchen Display in another browser tab/window:
   - Navigate to Kitchen Display
   - You should see the new order appear in real-time

4. Open the Bar Display in another tab if the order contains drinks.

5. Update the order status and watch it broadcast to all connected displays.

## Verification Checklist

- [ ] Reverb server starts without errors
- [ ] WebSocket connection established (check browser console)
- [ ] OrderCreated event broadcasts to kitchen, bar, and orders channels
- [ ] OrderStatusUpdated event broadcasts to all relevant channels
- [ ] Kitchen Display receives real-time updates
- [ ] Bar Display receives real-time updates
- [ ] Manager Dashboard receives real-time updates
- [ ] Authentication works for private channels
- [ ] Only authorized users can subscribe to channels

## Configuration Summary

### .env Settings
```
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=369538
REVERB_APP_KEY=dpv56o7sphki4t7j5nq3
REVERB_APP_SECRET=mrthb4xsvwyjasgogltp
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Channels Configured
- `kitchen` - Kitchen staff and managers only
- `bar` - Bar staff and managers only
- `orders` - All authorized staff (managers, waiters, kitchen, bar)
- `dashboard` - Managers only
- `waiter.{waiterId}` - Individual waiter channels

### Events Created
1. **OrderCreated** (app/Events/OrderCreated.php)
   - Broadcasts to: kitchen, bar, orders, waiter.{waiterId}
   - Data: order_id, table, status, items_count

2. **OrderStatusUpdated** (app/Events/OrderStatusUpdated.php)
   - Broadcasts to: kitchen, bar, orders, waiter.{waiterId}
   - Data: order_id, table, old_status, new_status, updated_at

## Troubleshooting

### WebSocket Connection Failed
- Ensure Reverb server is running: `php artisan reverb:start`
- Check firewall settings for port 8080
- Verify .env configuration matches

### No Events Received
- Check browser console for connection errors
- Verify user has proper role/permissions
- Check channel authentication in routes/channels.php
- Ensure events implement ShouldBroadcast interface

### Events Not Broadcasting
- Make sure BROADCAST_CONNECTION=reverb in .env
- Check queue is running if using queued events
- Verify event is dispatched correctly: `event(new OrderCreated($order))`

## Production Deployment Notes

For production deployment:
1. Use SSL/TLS (wss://) instead of ws://
2. Update REVERB_SCHEME=https in .env
3. Configure proper port forwarding/proxy
4. Use process manager (Supervisor) to keep Reverb running
5. Monitor Reverb logs for errors

## Additional Resources

- [Laravel Broadcasting Documentation](https://laravel.com/docs/broadcasting)
- [Laravel Reverb Documentation](https://laravel.com/docs/reverb)
- [Laravel Echo Documentation](https://laravel.com/docs/echo)
