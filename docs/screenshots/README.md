# Screenshots Directory

This directory contains screenshots used in the user documentation guides. Screenshots help users understand the interface and follow step-by-step instructions more easily.

## Adding Screenshots

To add screenshots to the documentation:

1. **Capture Screenshots**
   - Take clear, high-resolution screenshots of key interfaces
   - Use a consistent browser window size (recommended: 1920x1080)
   - Ensure no sensitive data (customer names, real orders, etc.) is visible
   - Use test data or mock data when capturing screenshots

2. **Naming Convention**
   - Use descriptive, lowercase names with hyphens
   - Include the role or feature in the filename
   - Examples:
     - `admin-dashboard-overview.png`
     - `waiter-create-order-interface.png`
     - `kitchen-display-active-orders.png`
     - `manager-sales-report.png`

3. **Image Format**
   - Preferred format: PNG for interface screenshots
   - JPG for photos or images with many colors
   - Maximum file size: 500KB (optimize if larger)

4. **Recommended Screenshots by Guide**

### Admin Guide (ADMIN_GUIDE.md)
- `admin-dashboard.png` - Dashboard overview
- `admin-staff-management.png` - Staff management interface
- `admin-user-management.png` - User management page
- `admin-settings.png` - System settings page
- `admin-reports.png` - Reports dashboard
- `admin-audit-logs.png` - Audit logs view

### Manager Guide (MANAGER_GUIDE.md)
- `manager-dashboard.png` - Manager dashboard
- `manager-menu-management.png` - Menu management interface
- `manager-add-menu-item.png` - Adding a new menu item
- `manager-table-management.png` - Table management view
- `manager-inventory.png` - Inventory management
- `manager-sales-report.png` - Sales report example

### Waiter Guide (WAITER_GUIDE.md)
- `waiter-pos-interface.png` - POS order creation screen
- `waiter-select-table.png` - Table selection
- `waiter-add-items.png` - Adding items to order
- `waiter-order-summary.png` - Order summary before submission
- `waiter-payment-processing.png` - Payment processing screen
- `waiter-receipt.png` - Generated receipt

### Chef Guide (CHEF_GUIDE.md)
- `chef-kitchen-display.png` - Kitchen display system overview
- `chef-order-details.png` - Order item details
- `chef-mark-preparing.png` - Marking item as preparing
- `chef-mark-complete.png` - Marking item as complete

### Bartender Guide (BARTENDER_GUIDE.md)
- `bar-display.png` - Bar display system overview
- `bar-drink-orders.png` - Active drink orders
- `bar-order-status.png` - Updating order status

## Embedding Screenshots in Documentation

To include a screenshot in a markdown guide:

```markdown
![Screenshot Description](screenshots/filename.png)
```

Example:
```markdown
### Dashboard Overview

The admin dashboard provides a comprehensive view of your restaurant operations:

![Admin Dashboard](screenshots/admin-dashboard.png)

As shown above, the dashboard displays:
- Today's orders and revenue
- Active tables
- Recent order activity
- Low stock alerts
```

## Screenshot Guidelines

1. **Privacy & Security**
   - Never include real customer data
   - Use demo/test accounts
   - Blur or redact any sensitive information

2. **Quality Standards**
   - Clear, in-focus images
   - Proper lighting (avoid dark mode unless specifically documenting it)
   - Readable text (12pt or larger in screenshots)
   - No browser extensions or bookmarks visible

3. **Consistency**
   - Use the same test data across related screenshots
   - Maintain consistent UI state (logged in as same user)
   - Use same browser and window size

4. **Annotations** (Optional)
   - Add arrows or highlights to draw attention to specific features
   - Use red boxes or circles for important UI elements
   - Keep annotations minimal and clear

## Tools for Taking Screenshots

- **macOS**: Cmd+Shift+4 (select area) or Cmd+Shift+3 (full screen)
- **Windows**: Windows+Shift+S (Snipping Tool)
- **Linux**: Flameshot, GNOME Screenshot
- **Browser Extensions**:
  - Awesome Screenshot
  - Nimbus Screenshot
  - FireShot

## Image Optimization

Before adding screenshots, optimize them to reduce file size:

```bash
# Using ImageOptim (macOS)
# Drag and drop images into ImageOptim app

# Using optipng (command line)
optipng -o7 screenshot.png

# Using pngquant (command line)
pngquant --quality=65-80 screenshot.png
```

## Current Status

This directory is currently empty. Screenshots need to be captured for all user guides.

**Priority Screenshots Needed:**
1. Dashboard views for each role
2. Key workflows (creating orders, processing payments)
3. Kitchen/Bar display systems
4. Settings and configuration pages
5. Report examples

## Contributing

When adding screenshots:
1. Follow the naming convention above
2. Optimize image file size
3. Update the relevant documentation guide to reference the screenshot
4. Test that the image displays correctly in the markdown preview
5. Commit both the screenshot and updated documentation together

---

For questions or issues with screenshots, contact the development team.
