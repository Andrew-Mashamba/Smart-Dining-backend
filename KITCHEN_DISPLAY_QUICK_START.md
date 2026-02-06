# Kitchen Display System - Quick Start Guide

## Access the Kitchen Display

### URL
```
http://your-domain.com/kitchen
```

### Required Permissions
- Must be logged in
- Role must be one of: `chef`, `manager`, or `admin`

---

## Using the Kitchen Display

### 1. View Active Orders
- All pending kitchen orders display automatically
- Orders grouped by table
- Each card shows:
  - **Order Number**: Bold at top (e.g., ORD-20260206-0001)
  - **Table Name**: Below order number
  - **Elapsed Time**: Time since order was placed
  - **Items**: List of all kitchen items with quantities

### 2. Update Item Status
Each item has three status buttons:

1. **Received** (Light gray)
   - Click when order ticket is received
   - Acknowledges order is in queue

2. **Preparing** (Medium gray)
   - Click when actively cooking/preparing
   - Shows kitchen is working on item

3. **Ready** (Dark gray/black)
   - Click when item is complete
   - Signals waiter to pick up
   - Item disappears from display after all items ready

### 3. Priority Orders
Orders older than 15 minutes:
- **Gray background** (bg-gray-200)
- **"PRIORITY" badge** displayed
- **Darker border** for emphasis
- Sort to top automatically

### 4. New Order Alerts
When a new order arrives:
- **Audio beep** plays (800Hz tone)
- **Visual flash** on new order card
- **Automatic display update**

### 5. Fullscreen Mode

#### Enter Fullscreen
Click the **"Fullscreen"** button in header (top-right)

#### Exit Fullscreen
- Click **"Exit Fullscreen"** button (visible in fullscreen)
- Press `ESC` key
- Click exit fullscreen icon

#### Benefits
- Removes sidebar and navigation
- Maximizes order visibility
- Ideal for wall-mounted displays
- Reduces distractions

---

## Auto-Refresh

The display automatically refreshes every **5 seconds**:
- Shows "Updating..." indicator bottom-right
- Polls server for new orders
- Updates elapsed times
- No manual refresh needed

**Plus**: Real-time WebSocket updates (if Echo configured)

---

## Understanding Order Cards

### Card Layout
```
┌─────────────────────────────────────┐
│ ORD-20260206-0001    │    15 min    │ ← Order # & Time
│ Table: A5            │   PRIORITY   │ ← Table & Priority
├─────────────────────────────────────┤
│ ┌─────────────────────────────────┐ │
│ │ 2x Grilled Salmon               │ │ ← Item with quantity
│ │ Note: No butter                 │ │ ← Special instructions
│ │                                 │ │
│ │ [Received] [Preparing] [Ready]  │ │ ← Status buttons
│ │ Status: PREPARING               │ │ ← Current status
│ └─────────────────────────────────┘ │
│ ┌─────────────────────────────────┐ │
│ │ 1x Caesar Salad                 │ │ ← Another item
│ │ [Received] [Preparing] [Ready]  │ │
│ │ Status: RECEIVED                │ │
│ └─────────────────────────────────┘ │
├─────────────────────────────────────┤
│ Order placed at 14:35               │ ← Timestamp
└─────────────────────────────────────┘
```

### Color Coding (Monochrome Design)
- **White background**: Normal priority orders
- **Light gray (bg-gray-200)**: Priority orders (>15 min)
- **Dark text**: High importance info (order numbers)
- **Medium gray text**: Secondary info (table, notes)

---

## Workflow Example

### Typical Order Flow

1. **New Order Arrives**
   ```
   [BEEP!]
   ORD-20260206-0015
   Table: B12
   2x Steak Medium Rare
   Status: PENDING
   ```

2. **Chef Acknowledges**
   ```
   Click: [Received]
   Status changes to: RECEIVED
   ```

3. **Start Cooking**
   ```
   Click: [Preparing]
   Status changes to: PREPARING
   Card may highlight if taking long
   ```

4. **Complete Item**
   ```
   Click: [Ready]
   Status changes to: READY
   If all items ready → card disappears
   ```

---

## Troubleshooting

### No Audio Alert?
- **Cause**: Browser blocks audio without user interaction
- **Fix**: Click anywhere on page first, then audio will work
- **Note**: Web Audio API requires initial user gesture

### Display Not Updating?
- **Check**: Internet connection
- **Check**: Still logged in (session timeout?)
- **Try**: Manual refresh (F5)
- **Verify**: Livewire scripts loaded (check console)

### Can't Access Kitchen Route?
- **Error**: 403 Forbidden or redirect to dashboard
- **Cause**: Insufficient role permissions
- **Fix**: User must have `chef`, `manager`, or `admin` role
- **Admin**: Update user role in Users management

### Fullscreen Not Working?
- **Cause**: Browser doesn't support Fullscreen API
- **Alternative**: Alpine.js fullscreen still works (hides header)
- **Browsers**: Works in Chrome, Firefox, Safari, Edge (modern versions)

### Items Not Showing?
- **Check**: MenuItem `prep_area` is set to `'kitchen'`
- **Check**: OrderItem `prep_status` is not `'ready'`
- **Check**: Order actually has items
- **Database**: Verify menu_items.prep_area column

---

## Tips for Efficient Use

### Best Practices
1. **Update status promptly** - Keeps waiters informed
2. **Check priority orders first** - Oldest at top
3. **Read special instructions** - Displayed under item name
4. **Use fullscreen** - For dedicated kitchen displays
5. **Keep audio on** - Don't miss new orders

### Multi-Display Setup
- Open on multiple screens
- All sync in real-time
- Update on one → all update
- Great for large kitchens

### Peak Hours
- Orders sort by age (oldest first)
- Priority highlighting helps focus
- Fullscreen maximizes visibility
- Auto-refresh keeps current

---

## Keyboard Shortcuts

### Browser Standard
- `F11`: Toggle browser fullscreen
- `ESC`: Exit fullscreen
- `F5`: Manual refresh (usually not needed)
- `Ctrl/Cmd + R`: Reload page

### Fullscreen Button
- Programmatic fullscreen via Alpine.js
- Works alongside browser fullscreen
- Combined effect for best visibility

---

## Technical Details

### Update Frequency
- **Polling**: Every 5 seconds
- **WebSocket**: Instant (if configured)
- **Manual**: Click any status button

### Data Filtering
- **prep_area**: Only `'kitchen'` items
- **prep_status**: `pending`, `received`, `preparing`
- **Excluded**: `ready` items (removed from display)

### Performance
- **Optimized queries**: Eager loading
- **Efficient grouping**: Minimal database load
- **Cached relationships**: Fast rendering

---

## Support

### Common Issues
Most issues resolve with:
1. Refresh page (F5)
2. Re-login
3. Check user role permissions
4. Verify browser is modern/updated

### Report Problems
If issues persist:
1. Check browser console for errors (F12)
2. Verify Laravel logs: `storage/logs/laravel.log`
3. Contact system administrator
4. Provide: order number, time, exact error message

---

## Summary

The Kitchen Display System provides:
- ✅ Real-time order updates
- ✅ Visual priority indicators
- ✅ Audio alerts for new orders
- ✅ Simple three-button workflow
- ✅ Fullscreen mode for dedicated displays
- ✅ Automatic synchronization across devices

**Access**: `/kitchen` (chef, manager, admin only)

**Questions?** Refer to full implementation docs in `STORY_25_IMPLEMENTATION_SUMMARY.md`
