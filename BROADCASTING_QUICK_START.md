# Laravel Reverb Broadcasting - Quick Start Guide

## Quick Start

### Start Development Services

```bash
# All-in-one command (recommended)
composer run dev

# Or start services individually:
php artisan serve          # Terminal 1: Laravel app
php artisan reverb:start   # Terminal 2: WebSocket server
php artisan queue:work     # Terminal 3: Queue workers
npm run dev                # Terminal 4: Vite
```

---

## Listen for Events

### Kitchen Display

```javascript
Echo.private('kitchen')
    .listen('OrderCreated', (e) => {
        // New order: e.order_id, e.table, e.status, e.items_count
    })
    .listen('OrderStatusUpdated', (e) => {
        // Status change: e.order_id, e.old_status, e.new_status
    });
```

### Bar Display

```javascript
Echo.private('bar')
    .listen('OrderCreated', (e) => {
        // New bar order
    })
    .listen('OrderStatusUpdated', (e) => {
        // Bar order status changed
    });
```

### Manager Dashboard

```javascript
Echo.private('orders')
    .listen('OrderCreated', (e) => {
        // All new orders
    })
    .listen('OrderStatusUpdated', (e) => {
        // All order updates
    });
```

### Waiter Notifications

```javascript
// Use authenticated user's ID
Echo.private(`waiter.${userId}`)
    .listen('OrderCreated', (e) => {
        // Waiter's new order
    })
    .listen('OrderStatusUpdated', (e) => {
        // Waiter's order updated
    });
```

---

## Dispatch Events

### When Creating Orders

```php
use App\Events\OrderCreated;

$order = Order::create([...]);
event(new OrderCreated($order));
```

### When Updating Status

```php
use App\Events\OrderStatusUpdated;

$oldStatus = $order->status;
$order->update(['status' => 'preparing']);
event(new OrderStatusUpdated($order, $oldStatus, 'preparing'));
```

---

## Test Broadcasting

```bash
# Make sure Reverb is running
php artisan reverb:start

# Test with command
php artisan test:broadcasting

# Or use Tinker
php artisan tinker
>>> event(new App\Events\OrderCreated(Order::first()));
```

---

## Channel Access

| Channel | Access |
|---------|--------|
| `orders` | Managers, waiters, kitchen staff, bar staff |
| `kitchen` | Kitchen staff, managers |
| `bar` | Bar staff, managers |
| `waiter.{id}` | Specific waiter only |

---

## Event Payloads

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
  "updated_at": "2024-01-30T12:34:56.000000Z"
}
```

---

## Troubleshooting

**No events received?**
- Check Reverb is running: `php artisan reverb:start`
- Check queue workers: `php artisan queue:work`
- Check `.env`: `BROADCAST_CONNECTION=reverb`

**Connection failed?**
- Check Vite is running: `npm run dev`
- Check port 8080 is open
- Check browser console for errors

**Authorization failed?**
- Ensure user is authenticated
- Check user role matches channel requirements
- Review `routes/channels.php`

---

## Production Deployment

Update `.env`:
```env
REVERB_HOST=your-domain.com
REVERB_PORT=443
REVERB_SCHEME=https
```

Use Supervisor to keep Reverb running:
```bash
sudo supervisorctl start reverb
```

---

## Files Reference

**Backend:**
- `app/Events/OrderCreated.php` - Order creation event
- `app/Events/OrderStatusUpdated.php` - Status update event
- `routes/channels.php` - Channel authorization
- `config/broadcasting.php` - Broadcasting config

**Frontend:**
- `resources/js/echo.js` - Echo configuration
- `resources/js/bootstrap.js` - Imports Echo

**Environment:**
- `.env` - Reverb credentials (lines 36, 67-77)

---

For full documentation, see:
- `REVERB_SETUP_COMPLETE.md`
- `STORY_24_VERIFICATION_SUMMARY.md`
