# Testing Documentation - Hospitality POS System

## Test Summary

**Date:** February 6, 2026
**Laravel Version:** 11.48.0
**Test Status:** In Progress - Story 52 Implementation

## Test Suite Overview

### Current Test Results
- **Total Tests:** 220
- **Passing:** 160
- **Failing:** 52
- **Risky:** 1
- **Skipped:** 7
- **Coverage Target:** >80%

## Known Test Issues and Resolutions

### 1. Model Inconsistency Issues
**Problem:** Tests mix `User` and `Staff` models inconsistently.
**Resolution Required:**
- `RoleBasedApiAccessTest.php` - Uses `Staff` model but should use `User`
- All API tests should use `User` model as per Sanctum authentication
- Staff model is for internal record-keeping, User model is for authentication

### 2. Order Creation Test Failures
**Problem:** Tests fail due to missing required fields (guest_id, waiter_id, guest_session_id).
**Resolution:**
- Ensure all order creation tests include `guest_session_id`
- Foreign key constraints require proper test data setup
- Use factories to create complete object graphs

### 3. Security Audit Test Issues
**Issues Identified:**
- Some API endpoints are public (menu endpoints) - expected behavior
- `/api/user` endpoint returns 404 - should be `/api/auth/me`
- XSS test needs guest_session_id
- CSRF test expects 419 or 302 response codes

### 4. Role-Based Access Control Tests
**Issues:**
- Chef/Bartender permissions on order items need prep_area validation
- Order item update endpoints require checking item prep_area against user role
- Some tests use wrong endpoints (e.g., `/preparing`, `/ready` vs `/received`, `/done`)

## Manual Testing Checklist

### Complete Order Workflow Testing

#### As Waiter
- [ ] Login as waiter user
- [ ] Create guest session for table
- [ ] Create order with multiple items (kitchen + bar)
- [ ] View order status in real-time
- [ ] Mark order as served when items ready
- [ ] Process payment (cash, card, Stripe)
- [ ] Verify receipt generation
- [ ] Verify table status updates
- [ ] Check tip processing

#### As Chef
- [ ] Login as chef user
- [ ] View pending kitchen orders
- [ ] Mark items as received
- [ ] Mark items as done/ready
- [ ] Verify kitchen display updates real-time
- [ ] Verify cannot access bartender functions
- [ ] Verify cannot process payments

#### As Bartender
- [ ] Login as bartender user
- [ ] View pending bar orders
- [ ] Mark bar items as received
- [ ] Mark bar items as done
- [ ] Verify bar display updates real-time
- [ ] Verify cannot access kitchen functions
- [ ] Verify cannot process payments

#### As Manager
- [ ] Login as manager user
- [ ] Access all order functions
- [ ] Update order statuses
- [ ] Cancel orders
- [ ] Process refunds
- [ ] Update menu availability
- [ ] View reports and analytics
- [ ] Manage staff permissions

#### As Admin
- [ ] Login as admin user
- [ ] Full system access verification
- [ ] User management
- [ ] System settings
- [ ] Database migrations
- [ ] Backup/restore operations

### Cross-Browser Testing

#### Desktop Browsers
- [ ] **Chrome** (latest) - Primary browser
  - Order creation flow
  - Payment processing
  - Real-time updates
  - PDF generation
- [ ] **Firefox** (latest)
  - Full workflow testing
  - WebSocket connections
  - Form submissions
- [ ] **Safari** (latest)
  - macOS compatibility
  - iOS WebKit compatibility
  - Date/time pickers
- [ ] **Edge** (latest)
  - Windows compatibility
  - Chromium-based features

#### Mobile Browsers
- [ ] Chrome Mobile (Android)
- [ ] Safari Mobile (iOS)
- [ ] Samsung Internet
- [ ] Mobile responsive design

### Mobile Responsive Testing

#### Screen Sizes to Test
- [ ] iPhone SE (375x667)
- [ ] iPhone 12/13/14 (390x844)
- [ ] iPhone 14 Pro Max (430x932)
- [ ] iPad (768x1024)
- [ ] iPad Pro (1024x1366)
- [ ] Android Phone (360x740)
- [ ] Android Tablet (800x1280)

#### Components to Verify
- [ ] Navigation menu (collapsible)
- [ ] Order creation form
- [ ] Kitchen/bar displays
- [ ] Table management grid
- [ ] Payment interface
- [ ] Menu browsing
- [ ] Touch interactions
- [ ] Swipe gestures

### Real-Time Testing

#### Kitchen Display
- [ ] Start Reverb server: `php artisan reverb:start`
- [ ] Create order from waiter interface
- [ ] Verify order appears in kitchen display immediately
- [ ] Update order status
- [ ] Verify status updates in real-time
- [ ] Test with multiple concurrent orders
- [ ] Test connection recovery after disconnect

#### Bar Display
- [ ] Similar tests as kitchen for bar items
- [ ] Verify filtering (only bar items shown)
- [ ] Test concurrent kitchen/bar updates

#### Multi-Device Testing
- [ ] Open kitchen display on one device
- [ ] Create orders from another device
- [ ] Verify real-time synchronization
- [ ] Test with 3+ concurrent connections

### API Testing with Postman

#### Authentication Endpoints
```
POST /api/auth/login
- Test valid credentials
- Test invalid credentials
- Verify token generation

POST /api/auth/logout
- Test with valid token
- Test with expired token

GET /api/auth/me
- Test authenticated user info
- Verify sensitive data hidden
```

#### Order Endpoints
```
GET /api/orders
- Test as different roles
- Verify role-based filtering
- Test pagination

POST /api/orders
- Test order creation
- Test validation rules
- Test stock deduction
- Test table status update

PATCH /api/orders/{id}/status
- Test valid transitions
- Test invalid transitions
- Test role permissions
```

#### Payment Endpoints
```
POST /api/payments
- Test cash payment
- Test card payment
- Test Stripe payment
- Test amount validation
- Test tip processing
```

### Performance Testing

#### Using Laravel Telescope
1. **Enable Telescope:**
   ```bash
   php artisan telescope:install
   php artisan migrate
   ```

2. **Monitor Slow Queries:**
   - Access `/telescope/queries`
   - Identify queries >100ms
   - Check N+1 query problems
   - Optimize with eager loading

3. **Database Query Optimization:**
   - [ ] Add indexes on foreign keys
   - [ ] Optimize order item queries with `with(['menuItem', 'order'])`
   - [ ] Cache frequently accessed data (menu items, categories)
   - [ ] Use query builder for complex reports

4. **Caching Strategy:**
   ```bash
   # Cache menu items
   php artisan cache:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

5. **Performance Benchmarks:**
   - [ ] Order creation: <500ms
   - [ ] Menu loading: <200ms
   - [ ] Payment processing: <1s
   - [ ] Report generation: <2s
   - [ ] Real-time broadcast: <100ms

### Security Audit

#### SQL Injection Testing
- [ ] Test order queries with injection attempts
- [ ] Test search parameters with SQL keywords
- [ ] Verify Eloquent ORM protection
- [ ] Test raw queries for proper binding

#### XSS (Cross-Site Scripting)
- [ ] Test special instructions with `<script>` tags
- [ ] Test menu item names with HTML
- [ ] Test guest names with JavaScript
- [ ] Verify Blade escaping: `{{ }}` vs `{!! !!}`

#### CSRF Protection
- [ ] Verify CSRF tokens on all forms
- [ ] Test form submission without token
- [ ] Verify API routes use Sanctum (not CSRF)
- [ ] Check `VerifyCsrfToken` middleware

#### Authorization Testing
- [ ] Waiter cannot access admin functions
- [ ] Chef cannot process payments
- [ ] Bartender cannot modify kitchen orders
- [ ] Users cannot access other users' data
- [ ] Verify `Gate` and `Policy` implementations

#### Authentication Security
- [ ] Password hashing (bcrypt)
- [ ] Token expiration (Sanctum)
- [ ] Rate limiting on login endpoint
- [ ] Brute force protection
- [ ] Session security

#### Data Exposure
- [ ] Passwords never in API responses
- [ ] Hidden fields in User model
- [ ] API resources hide sensitive data
- [ ] Error messages don't expose system info

## Automated Test Fixes Required

### Priority 1: Critical Fixes
1. **Fix RoleBasedApiAccessTest**
   - Change `Staff` to `User` model
   - Update authentication approach
   - Fix endpoint URLs

2. **Fix CompleteOrderWorkflowTest**
   - Add guest_session_id to all order creations
   - Use proper endpoint URLs for order item updates
   - Fix foreign key constraint violations

3. **Fix SecurityAuditTest**
   - Update API user endpoint to `/api/auth/me`
   - Add required fields to order creation
   - Fix payment amount validation test
   - Update CSRF protection test

### Priority 2: Test Coverage
4. **Add Missing Tests**
   - Inventory deduction edge cases
   - Payment refund workflows
   - Order cancellation with stock restoration
   - Concurrent order handling
   - WebSocket connection handling

5. **Improve Test Data**
   - Use seeders for consistent test data
   - Create test factories for all models
   - Add trait for common test setups

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run with Coverage
```bash
php artisan test --coverage --min=80
```

### Run Specific Test Suite
```bash
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Run Specific Test File
```bash
php artisan test tests/Feature/CompleteOrderWorkflowTest.php
```

### Run Parallel Tests (faster)
```bash
php artisan test --parallel
```

### Generate Coverage Report
```bash
php artisan test --coverage-html coverage
```

## Clean Database Testing

### Fresh Migration Test
```bash
php artisan migrate:fresh
# Should complete without errors
# All tables created successfully
```

### Seeder Test
```bash
php artisan migrate:fresh --seed
# Should complete without errors
# Creates demo data for testing
```

### Production Migration Test
```bash
# On staging environment
php artisan down
php artisan migrate --force
php artisan up
```

## Code Quality Checks

### Remove Debug Statements
```bash
# Search for debug code
grep -r "dd(" app/
grep -r "dump(" app/
grep -r "var_dump" app/
grep -r "print_r" app/
grep -r "console.log" resources/
```

### Remove Commented Code
```bash
# Manual review of files
# Check for:
# - Large blocks of commented code
# - Old implementation attempts
# - Unused imports
```

### Code Style
```bash
# Run PHP CS Fixer (if installed)
./vendor/bin/php-cs-fixer fix

# Run Laravel Pint
./vendor/bin/pint
```

## Browser-Specific Issues to Test

### Chrome
- WebSocket connections
- Service workers
- Push notifications
- IndexedDB for offline support

### Firefox
- WebSocket handling
- PDF rendering
- Form validation

### Safari
- Date picker compatibility
- WebSocket connections
- localStorage
- CSS Grid layouts

### Mobile Browsers
- Touch events
- Viewport scaling
- Fixed positioning
- Keyboard interactions

## Performance Optimization Results

### Database Optimization
- [ ] Indexes added to all foreign keys
- [ ] Eager loading implemented
- [ ] Query count reduced by X%
- [ ] Average query time: Xms

### Caching Implementation
- [ ] Menu items cached (1 hour)
- [ ] Config cached
- [ ] Routes cached
- [ ] Views compiled

### Asset Optimization
- [ ] CSS minified
- [ ] JavaScript bundled
- [ ] Images optimized
- [ ] Lazy loading implemented

## Production Readiness Checklist

See `DEPLOYMENT.md` for complete production deployment checklist.

### Key Items
- [ ] All tests passing (>80% coverage)
- [ ] No debug statements in code
- [ ] Environment variables configured
- [ ] Database migrations tested
- [ ] Seeders verified
- [ ] Security audit complete
- [ ] Performance optimization complete
- [ ] Documentation complete
- [ ] Backup strategy in place
- [ ] Monitoring configured

## Release Tag v1.0.0

After all acceptance criteria met:
```bash
git add .
git commit -m "Story 52: Final testing and production preparation complete"
git tag -a v1.0.0 -m "Release v1.0.0 - Production ready"
git push origin master --tags
```

## Notes

- Tests use SQLite in-memory database for speed
- Some tests are environment-specific
- Real-time tests require Reverb server running
- Payment tests use Stripe test mode
- Mobile testing requires physical devices or emulators

## Test Maintenance

- Review and update tests with each new feature
- Keep test data factories up to date
- Document new test requirements
- Maintain test coverage above 80%
- Run tests before each deployment
