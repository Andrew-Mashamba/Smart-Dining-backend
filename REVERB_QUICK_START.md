# Laravel Reverb Quick Start Guide

## Start the System

```bash
# Terminal 1: Reverb WebSocket Server
php artisan reverb:start

# Terminal 2: Queue Worker (required for broadcasting)
php artisan queue:work

# Terminal 3: Application Server
php artisan serve

# Terminal 4: Vite Dev Server
npm run dev
```

## Available Events

### OrderCreated
```php
use App\Events\OrderCreated;

event(new OrderCreated($order));
```

**Broadcasts to:** `orders`, `kitchen`, `bar`, `waiter.{waiter_id}`

**Payload:**
```javascript
{
    order_id: 123,
    table: "Table 5",
    status: "pending",
    items_count: 3
}
```

### OrderStatusUpdated
```php
use App\Events\OrderStatusUpdated;

event(new OrderStatusUpdated($order, 'pending', 'preparing'));
```

**Broadcasts to:** `orders`, `kitchen`, `bar`, `waiter.{waiter_id}`

**Payload:**
```javascript
{
    order_id: 123,
    table: "Table 5",
    old_status: "pending",
    new_status: "preparing",
    updated_at: "2026-02-06T12:00:00.000Z"
}
```

## Frontend Usage

### Listen to Kitchen Channel
```javascript
Echo.private('kitchen')
    .listen('OrderCreated', (e) => {
        console.log('New order for kitchen:', e);
        // Update kitchen display
    })
    .listen('OrderStatusUpdated', (e) => {
        console.log('Order status updated:', e);
        // Update order card
    });
```

### Listen to Bar Channel
```javascript
Echo.private('bar')
    .listen('OrderCreated', (e) => {
        console.log('New order for bar:', e);
        // Update bar display
    });
```

### Listen to Orders Channel (Dashboard)
```javascript
Echo.private('orders')
    .listen('OrderCreated', (e) => {
        console.log('New order:', e);
        // Add to order list
    })
    .listen('OrderStatusUpdated', (e) => {
        console.log('Order updated:', e);
        // Update order in list
    });
```

### Listen to Waiter-Specific Channel
```javascript
// Replace {userId} with actual waiter ID
Echo.private(`waiter.${userId}`)
    .listen('OrderStatusUpdated', (e) => {
        console.log('Your order was updated:', e);
        // Show notification
    });
```

## Channel Authorization

| Channel | Authorized Roles |
|---------|-----------------|
| `kitchen` | kitchen_staff, manager |
| `bar` | bar_staff, manager |
| `orders` | manager, waiter, kitchen_staff, bar_staff |
| `waiter.{id}` | waiter (own ID only) |

## Test Commands

```bash
# Test acceptance criteria
php test-reverb-setup.php

# Test event broadcasting
php test-broadcast-event.php
```

## Troubleshooting

### Port Already in Use
```bash
lsof -i :8080
kill -9 <PID>
```

### Events Not Broadcasting
1. Check: `BROADCAST_CONNECTION=reverb` in `.env`
2. Ensure queue worker is running
3. Check event implements `ShouldBroadcast`

### Frontend Not Receiving
1. Check browser console for errors
2. Verify user is authenticated
3. Ensure Vite is running: `npm run dev`
4. Check VITE_REVERB_* variables in `.env`

## Configuration Files

- `config/reverb.php` - Reverb server config
- `config/broadcasting.php` - Broadcasting config
- `routes/channels.php` - Channel authorization
- `resources/js/echo.js` - Echo configuration
- `.env` - Environment variables

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

## Production Checklist

- [ ] Change `REVERB_SCHEME` to `https`
- [ ] Change `REVERB_PORT` to `443`
- [ ] Configure allowed origins in `config/reverb.php`
- [ ] Enable Reverb scaling with Redis
- [ ] Set max connections limit
- [ ] Use supervisor for process management
- [ ] Enable SSL/TLS certificates

---

For complete documentation, see: `STORY_24_COMPLETE.md`
