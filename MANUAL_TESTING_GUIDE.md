# Manual Testing Guide - Complete Order Workflow

## Prerequisites

1. **Environment Setup:**
   ```bash
   cd /Volumes/DATA/PROJECTS/HOSPITALITYSYSTEM/laravel-app
   cp .env.example .env
   php artisan key:generate
   php artisan migrate:fresh --seed
   ```

2. **Start Services:**
   ```bash
   # Terminal 1: Laravel Server
   php artisan serve

   # Terminal 2: Reverb WebSocket Server
   php artisan reverb:start

   # Terminal 3: Queue Worker
   php artisan queue:work

   # Terminal 4: Vite Dev Server (for assets)
   npm run dev
   ```

3. **Test User Credentials:**
   ```
   Admin:     admin@example.com / password
   Manager:   manager@example.com / password
   Waiter:    waiter@example.com / password
   Chef:      chef@example.com / password
   Bartender: bartender@example.com / password
   ```

## Test Scenario 1: Complete Order Workflow (Waiter Role)

### Step 1: Login as Waiter
1. Open browser: `http://localhost:8000`
2. Login with: `waiter@example.com` / `password`
3. **Verify:** Dashboard loads successfully
4. **Verify:** Navigation shows waiter-specific options

### Step 2: Select Available Table
1. Navigate to Tables page
2. **Verify:** List of tables displayed
3. **Verify:** Table statuses shown (available/occupied)
4. Click on an available table (e.g., Table 5)
5. **Verify:** Table details displayed

### Step 3: Create Guest Session
1. Click "Start Session" or "New Order" button
2. Fill in guest details:
   - Guest Name: "John Doe"
   - Guest Count: 2
   - Phone: "+1234567890" (optional)
3. Click "Create Session"
4. **Verify:** Guest session created successfully
5. **Verify:** Table status changes to "occupied"
6. **Verify:** Session ID displayed

### Step 4: Create Order with Multiple Items
1. Click "Add Items" or "Create Order"
2. Browse menu or search for items
3. Add kitchen items:
   - "Grilled Chicken" x 2
   - Special instructions: "No salt"
4. Add bar items:
   - "Mojito" x 2
   - Special instructions: "Extra ice"
5. **Verify:** Items added to cart
6. **Verify:** Prices displayed correctly
7. **Verify:** Subtotal calculated
8. Click "Place Order"
9. **Verify:** Order created with order number
10. **Verify:** Order status is "pending"
11. **Verify:** Stock quantities decreased

### Step 5: Monitor Order Status
1. Navigate to Orders page
2. Find the created order
3. **Verify:** Order status is "pending"
4. **Verify:** Order items listed with prep_status
5. **Verify:** Kitchen items show prep_area: "kitchen"
6. **Verify:** Bar items show prep_area: "bar"
7. Keep this page open to see real-time updates

### Step 6: Verify Real-Time Kitchen Updates
1. Open new browser window/tab
2. Login as Chef: `chef@example.com` / `password`
3. Navigate to Kitchen Display
4. **Verify:** Order appears in kitchen display
5. **Verify:** Only kitchen items shown (not bar items)
6. Click item → "Mark as Preparing"
7. **Switch to waiter tab**
8. **Verify:** Order item status updates to "preparing" in real-time (no page refresh)
9. **Switch to chef tab**
10. Wait a moment, then click "Mark as Ready"
11. **Switch to waiter tab**
12. **Verify:** Order item status updates to "ready" in real-time

### Step 7: Verify Real-Time Bar Updates
1. Open new browser window/tab
2. Login as Bartender: `bartender@example.com` / `password`
3. Navigate to Bar Display
4. **Verify:** Order appears in bar display
5. **Verify:** Only bar items shown (not kitchen items)
6. Click item → "Mark as Preparing"
7. **Switch to waiter tab**
8. **Verify:** Bar item status updates to "preparing" in real-time
9. **Switch to bartender tab**
10. Click "Mark as Ready"
11. **Switch to waiter tab**
12. **Verify:** Bar item status updates to "ready" in real-time

### Step 8: Mark Order as Served
1. **In waiter tab:** Navigate to order details
2. **Verify:** All items show prep_status: "ready"
3. Click "Mark as Served" or update status to "served"
4. **Verify:** Order status changes to "served"
5. **Verify:** served_at timestamp recorded

### Step 9: Process Payment
1. Click "Process Payment" button
2. **Verify:** Order total displayed correctly
3. **Verify:** Calculation: (Grilled Chicken: 150 x 2) + (Mojito: 80 x 2) = 460
4. Select payment method: "Cash"
5. Enter amount tendered: 500.00
6. **Verify:** Change calculated: 40.00
7. Optionally add tip: 50.00
8. Click "Complete Payment"
9. **Verify:** Payment processed successfully
10. **Verify:** Payment status: "completed"
11. **Verify:** Order status changes to "paid"
12. **Verify:** paid_at timestamp recorded

### Step 10: Verify Post-Payment Updates
1. **Verify:** Table status changes back to "available"
2. **Verify:** Guest session status changes to "closed"
3. Navigate to order receipt
4. **Verify:** Receipt displays all order details
5. **Verify:** Payment details included
6. **Verify:** Tip amount shown (if added)
7. Click "Generate PDF" or "Print Receipt"
8. **Verify:** PDF generates successfully
9. **Verify:** PDF contains all order information

### Step 11: Verify Order History
1. Navigate to Orders > History
2. Find the completed order
3. **Verify:** Order status shows "paid"
4. **Verify:** All timestamps visible
5. **Verify:** Can view order details
6. **Verify:** Cannot modify completed order

## Test Scenario 2: Order Workflow (Manager Role)

### Manager Can Do Everything
1. Login as Manager: `manager@example.com` / `password`
2. **Test:** Create guest session - Should work
3. **Test:** Create order - Should work
4. **Test:** Update order status manually - Should work
5. **Test:** Cancel order - Should work
6. **Test:** Process payment - Should work
7. **Test:** View all orders (not just own) - Should work
8. **Test:** Update menu availability - Should work
9. **Test:** View reports - Should work

### Specific Manager Tests
1. **Cancel Order:**
   - Create a new order
   - Click "Cancel Order"
   - Enter cancellation reason
   - **Verify:** Order cancelled
   - **Verify:** Stock restored
   - **Verify:** Table freed

2. **Update Menu:**
   - Navigate to Menu Management
   - Toggle item availability
   - **Verify:** Changes reflected immediately
   - **Verify:** Unavailable items not shown in order form

## Test Scenario 3: Chef Restrictions

### What Chef CAN Do:
1. Login as Chef
2. View Kitchen Display - **Should work**
3. View pending kitchen orders - **Should work**
4. Update kitchen item prep_status - **Should work**
5. Mark kitchen items as preparing/ready - **Should work**

### What Chef CANNOT Do:
1. Try to create order - **Should show 403 Forbidden**
2. Try to process payment - **Should show 403 Forbidden**
3. Try to update bar items - **Should show 403 Forbidden**
4. Try to access admin functions - **Should show 403 Forbidden**
5. Try to view other chefs' assignments - **Should be restricted**

### Test Chef Prep Area Restrictions:
1. Create order with both kitchen and bar items
2. Login as Chef
3. View order items
4. Try to update kitchen item - **Should work**
5. Try to update bar item - **Should fail with error**
6. **Verify:** Error message: "You can only update kitchen items"

## Test Scenario 4: Bartender Restrictions

### What Bartender CAN Do:
1. Login as Bartender
2. View Bar Display - **Should work**
3. View pending bar orders - **Should work**
4. Update bar item prep_status - **Should work**
5. Mark bar items as preparing/ready - **Should work**

### What Bartender CANNOT Do:
1. Try to create order - **Should show 403 Forbidden**
2. Try to process payment - **Should show 403 Forbidden**
3. Try to update kitchen items - **Should show 403 Forbidden**
4. Try to access admin functions - **Should show 403 Forbidden**

## Test Scenario 5: Cross-Browser Testing

### Chrome (Primary Browser)
1. Repeat Scenario 1 in Chrome
2. **Verify:** All features work
3. **Verify:** WebSocket connections stable
4. **Verify:** PDF generation works
5. **Verify:** Real-time updates instant

### Firefox
1. Repeat Scenario 1 in Firefox
2. **Verify:** Same behavior as Chrome
3. **Test:** Form validation
4. **Test:** Date pickers
5. **Test:** WebSocket reconnection

### Safari
1. Repeat Scenario 1 in Safari
2. **Verify:** Same behavior as Chrome
3. **Test:** iOS-specific features
4. **Test:** Touch events (on iPad)
5. **Test:** Date/time pickers

### Edge
1. Repeat Scenario 1 in Edge
2. **Verify:** Same behavior as Chrome
3. **Test:** Windows-specific features

## Test Scenario 6: Mobile Responsive Testing

### Using Browser DevTools
1. Open Chrome DevTools (F12)
2. Click device toggle (Ctrl+Shift+M)
3. Select device: iPhone 12 Pro

### Test on Mobile Viewport
1. **Navigation:**
   - **Verify:** Hamburger menu visible
   - **Verify:** Menu collapses properly
   - **Verify:** All links accessible

2. **Order Creation:**
   - **Verify:** Form fields stack vertically
   - **Verify:** Buttons full-width
   - **Verify:** Easy to tap
   - **Verify:** No horizontal scroll

3. **Kitchen/Bar Display:**
   - **Verify:** Cards stack vertically
   - **Verify:** Readable text size
   - **Verify:** Buttons large enough to tap
   - **Verify:** Status updates visible

4. **Payment Screen:**
   - **Verify:** Number pad accessible
   - **Verify:** Amount fields visible
   - **Verify:** Payment method selection easy

### Test Different Screen Sizes
- [ ] iPhone SE (375x667) - Smallest
- [ ] iPhone 12 (390x844) - Standard
- [ ] iPhone 14 Pro Max (430x932) - Large
- [ ] iPad (768x1024) - Tablet
- [ ] iPad Pro (1024x1366) - Large Tablet

## Test Scenario 7: Real-Time Multi-Device Testing

### Setup: 3 Devices/Browser Windows
1. **Device 1:** Waiter view - Order management
2. **Device 2:** Kitchen Display - Chef view
3. **Device 3:** Bar Display - Bartender view

### Test Procedure:
1. **Device 1:** Create new order with 2 kitchen items, 2 bar items
2. **Device 2 & 3:** **Verify:** Order appears immediately (within 1 second)
3. **Device 2:** Mark kitchen item as preparing
4. **Device 1 & 3:** **Verify:** Status update visible immediately
5. **Device 3:** Mark bar item as preparing
6. **Device 1 & 2:** **Verify:** Status update visible immediately
7. **Device 2:** Mark kitchen item as ready
8. **Device 1:** **Verify:** Status updates
9. **Device 3:** Mark bar item as ready
10. **Device 1:** **Verify:** All items ready, can serve

### Test Connection Recovery:
1. Stop Reverb server
2. **Verify:** UI shows "disconnected" indicator
3. Try to update order status
4. **Verify:** Graceful error handling
5. Restart Reverb server
6. **Verify:** Automatic reconnection
7. **Verify:** Updates resume working

## Test Scenario 8: Performance Testing

### Load Testing - Multiple Concurrent Orders
1. Open 5 browser tabs as different waiters
2. Create 5 orders simultaneously
3. **Verify:** All orders created successfully
4. **Verify:** No duplicate order numbers
5. **Verify:** Stock deducted correctly
6. **Verify:** Real-time updates for all orders

### Stress Testing - Large Orders
1. Create order with 50+ items
2. **Verify:** Order created successfully
3. **Verify:** Page loads in <2 seconds
4. **Verify:** All items displayed
5. **Verify:** Total calculated correctly

### Database Query Performance
1. Enable Laravel Debugbar or Telescope
2. Perform common operations
3. **Measure:** Order creation query count
4. **Measure:** Menu loading query count
5. **Target:** <10 queries for most operations
6. **Identify:** N+1 query problems

## Test Scenario 9: Security Testing

### Test 1: Unauthorized Access
1. Logout
2. Try to access: `http://localhost:8000/api/orders`
3. **Verify:** 401 Unauthorized response

### Test 2: Role-Based Access
1. Login as Waiter
2. Try to access: `http://localhost:8000/api/admin/users`
3. **Verify:** 403 Forbidden response

### Test 3: XSS Attempt
1. Create order with special instructions:
   ```
   <script>alert('XSS')</script>
   ```
2. Save order
3. View order details
4. **Verify:** Script not executed
5. **Verify:** HTML escaped in display

### Test 4: SQL Injection Attempt
1. In order search, try:
   ```
   1' OR '1'='1
   ```
2. **Verify:** No SQL error
3. **Verify:** Either no results or proper filtered results

### Test 5: Payment Manipulation
1. Create order with total: $100
2. Using browser console or Postman, try to pay:
   ```json
   {
     "order_id": 1,
     "amount": 10.00
   }
   ```
3. **Verify:** Payment rejected
4. **Verify:** Error: "Amount must equal order total"

## Test Scenario 10: Error Handling

### Test Network Errors
1. Disable network
2. Try to create order
3. **Verify:** Graceful error message
4. **Verify:** No data loss
5. **Verify:** Can retry when online

### Test Invalid Data
1. Try to create order with:
   - Negative quantity
   - Invalid menu item ID
   - Missing required fields
2. **Verify:** Validation errors displayed
3. **Verify:** User-friendly error messages
4. **Verify:** Form highlights problem fields

### Test Stock Depletion
1. Find item with low stock (quantity: 2)
2. Try to order quantity: 10
3. **Verify:** Error: "Insufficient stock"
4. **Verify:** Order not created
5. **Verify:** Stock not modified

## Success Criteria

All scenarios must pass with:
- ✅ No console errors
- ✅ No network errors (except intentional tests)
- ✅ Real-time updates working (<1s latency)
- ✅ Proper authorization enforcement
- ✅ Data integrity maintained
- ✅ Responsive design working
- ✅ Cross-browser compatibility
- ✅ Performance within acceptable limits

## Reporting Issues

When you find an issue, document:
1. **Scenario:** Which test scenario
2. **Steps:** Exact steps to reproduce
3. **Expected:** What should happen
4. **Actual:** What actually happened
5. **Browser:** Browser and version
6. **Screenshots:** If applicable
7. **Console errors:** Any JavaScript errors
8. **Network tab:** Failed requests

## Next Steps After Testing

1. Document all issues found
2. Prioritize: Critical, High, Medium, Low
3. Fix critical and high priority issues
4. Re-test after fixes
5. Update automated tests to cover found issues
6. Update this guide with any new scenarios discovered
