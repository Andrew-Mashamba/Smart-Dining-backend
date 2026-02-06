# SeaCliff POS - Administrator Guide

## Table of Contents
1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Dashboard Overview](#dashboard-overview)
4. [Staff Management](#staff-management)
5. [User Management](#user-management)
6. [Menu Management](#menu-management)
7. [Table Management](#table-management)
8. [Guest Management](#guest-management)
9. [Order Management](#order-management)
10. [Inventory Management](#inventory-management)
11. [Reports](#reports)
12. [System Settings](#system-settings)
13. [Security & Permissions](#security--permissions)
14. [Troubleshooting](#troubleshooting)

---

## Introduction

Welcome to the SeaCliff POS Administrator Guide. As an administrator, you have complete access to all system features and configurations. This guide will help you manage your restaurant operations efficiently.

### Your Role
As an **Admin**, you can:
- Manage all staff and users
- Configure system settings
- Access all reports and analytics
- Oversee all operations (menu, tables, orders, inventory)
- Configure payment integrations
- Manage security and permissions
- View audit logs
- Handle system troubleshooting

---

## Getting Started

### Logging In
1. Navigate to your SeaCliff POS URL
2. Enter your admin credentials
3. Click "Login"
4. You'll be redirected to the admin dashboard

### First Time Setup
After logging in for the first time:
1. Review and update system settings
2. Configure tax rates
3. Set up payment methods (Stripe, cash, card)
4. Create staff accounts
5. Set up tables
6. Configure menu categories and items
7. Set inventory thresholds

---

## Dashboard Overview

The admin dashboard provides a comprehensive overview of your restaurant operations.

### Key Metrics (Top Cards)
- **Today's Orders**: Total number of orders placed today
- **Today's Revenue**: Total revenue generated today
- **Active Tables**: Number of currently occupied tables
- **Staff on Duty**: Number of staff members currently active

### Revenue Chart
- 7-day revenue trend visualization
- Helps identify peak days and patterns
- Hover over bars to see exact amounts

### Top Selling Items
- List of best-performing menu items
- Shows quantity sold
- Helps with inventory planning

### Low Stock Alerts
- Real-time inventory alerts
- Items below minimum threshold
- Action required indicators

### Recent Orders
- Latest orders with status
- Quick access to order details
- Real-time updates

### Staff on Duty
- Currently active staff members
- Role identification
- Quick access to staff details

---

## Staff Management

### Accessing Staff Management
Navigate to: **Dashboard → Staff** or `/staff`

### Creating New Staff
1. Click "Create New Staff" button
2. Fill in required fields:
   - **Name**: Full name of staff member
   - **Email**: Unique email address
   - **Password**: Secure password (min. 8 characters)
   - **Role**: Select from Admin, Manager, Waiter, Chef, Bartender
   - **Status**: Active or Inactive
3. Click "Save"

### Staff Roles Explained
- **Admin**: Full system access, all permissions
- **Manager**: Business operations, reports, menu, inventory
- **Waiter**: Order creation, payment processing
- **Chef**: Kitchen Display System access
- **Bartender**: Bar Display System access

### Editing Staff
1. Click "Edit" button next to staff member
2. Modify fields as needed
3. Click "Update"

### Deactivating Staff
1. Click "Edit" on the staff member
2. Change status to "Inactive"
3. Click "Update"
4. Inactive staff cannot log in

### Deleting Staff
1. Click "Delete" button next to staff member
2. Confirm deletion
3. **Warning**: This action cannot be undone

### Best Practices
- Use unique email addresses for each staff member
- Enforce strong passwords
- Regularly review active staff
- Deactivate rather than delete for audit trail
- Assign minimum required role for security

---

## User Management

### Accessing User Management
Navigate to: **Dashboard → Users** or `/users`

### User vs Staff
- **Staff**: Internal employees with system access
- **Users**: May include external users or alternative authentication

### Managing Users
Similar to staff management:
1. Create new users with "Create User"
2. Assign appropriate roles
3. Edit or deactivate as needed
4. Monitor user activity

---

## Menu Management

### Accessing Menu Management
Navigate to: **Dashboard → Menu** or `/menu`

### Menu Structure
- **Categories**: Group related items (Appetizers, Mains, Desserts, Drinks)
- **Items**: Individual menu items with pricing and details

### Managing Categories

#### Creating a Category
1. Click "Add Category"
2. Enter:
   - **Name**: Category name
   - **Display Order**: Numeric order (lower = higher priority)
   - **Status**: Active or Inactive
3. Click "Save"

#### Reordering Categories
1. Use drag-and-drop functionality
2. Categories automatically update display order
3. Changes reflect immediately

#### Editing Categories
1. Click "Edit" on category
2. Modify name or status
3. Click "Update"

### Managing Menu Items

#### Creating a Menu Item
1. Click "Add Menu Item"
2. Fill in details:
   - **Name**: Item name
   - **Description**: Brief description
   - **Category**: Select category
   - **Price**: Decimal price (e.g., 12.99)
   - **Prep Area**: Kitchen or Bar
   - **Prep Time**: Estimated minutes
   - **Stock Quantity**: Current stock
   - **Low Stock Threshold**: Alert threshold
   - **Unit**: pieces, kg, liters, etc.
   - **Status**: Active or Inactive
3. Click "Save"

#### Editing Menu Items
1. Click "Edit" on menu item
2. Modify any field
3. Click "Update"

#### Stock Management
- **Current Stock**: Real-time quantity
- **Low Stock Alert**: Appears when below threshold
- **Auto-deduction**: Stock reduces when orders placed
- **Manual Adjustment**: Edit item to update stock

#### Availability Toggle
- Quick enable/disable items
- Useful for seasonal items or out-of-stock
- Toggle switch next to each item

### Menu Best Practices
- Keep descriptions clear and concise
- Accurate prep times improve kitchen efficiency
- Monitor stock levels regularly
- Deactivate items rather than delete
- Use consistent pricing format
- Organize categories logically

---

## Table Management

### Accessing Table Management
Navigate to: **Dashboard → Tables** or `/tables`

### Creating Tables
1. Click "Create Table"
2. Enter:
   - **Name**: Table identifier (e.g., "T1", "Table 5", "Patio A")
   - **Location**: Section (e.g., "Main Dining", "Patio", "VIP")
   - **Capacity**: Maximum number of guests
   - **Status**: Available, Occupied, Reserved
3. Click "Save"

### Table Status
- **Available**: Ready for seating
- **Occupied**: Currently has guests
- **Reserved**: Booked for future guests

### Editing Tables
1. Click "Edit" on table
2. Modify details
3. Click "Update"

### QR Code Generation
Each table can have a QR code for guest self-ordering:
1. QR codes generated automatically per table
2. Guests scan to view menu and order
3. Orders linked to table automatically

### Table Management Tips
- Name tables consistently
- Update status in real-time
- Use capacity for reservation planning
- Generate QR codes for contactless ordering
- Monitor table turnover in dashboard

---

## Guest Management

### Accessing Guest Management
Navigate to: **Dashboard → Guests** or `/guests`

### Guest Features
- Phone-based identification
- Order history tracking
- Loyalty points system
- Preferences storage

### Creating Guests
1. Click "Create Guest"
2. Enter:
   - **Name**: Guest name
   - **Phone**: Unique phone number
   - **Email**: Optional email
   - **Loyalty Points**: Starting points
   - **Preferences**: Special requests or notes
3. Click "Save"

### Viewing Guest Details
1. Click on guest name
2. View:
   - Contact information
   - Total orders
   - Total spent
   - Loyalty points
   - Order history
   - Preferences

### Editing Guests
1. Click "Edit" on guest
2. Update information
3. Click "Save"

### Guest Lookup
- Search by phone number
- Search by name
- Filter by loyalty points
- View recent activity

### Loyalty System
- Points awarded per order
- Configurable point rules
- View point balance
- Track redemptions

---

## Order Management

### Accessing Orders
Navigate to: **Dashboard → Orders** or `/orders`

### Order Workflow
Orders move through these statuses:
1. **Pending**: Just created, not yet preparing
2. **Preparing**: Kitchen/bar working on items
3. **Ready**: All items complete, ready to serve
4. **Delivered**: Served to guest
5. **Paid**: Payment completed

### Viewing Orders
- List view with filters
- Status-based filtering
- Search by order number
- Real-time status updates

### Order Details
Click on any order to view:
- Order number
- Table and guest information
- Waiter assigned
- Order items with quantities
- Item preparation status
- Special instructions
- Subtotal, tax, total
- Payment information
- Status history

### Managing Orders

#### Modifying Order Status
1. Navigate to order details
2. Use status buttons to advance
3. System validates transitions
4. Audit log tracks changes

#### Cancelling Orders
1. Go to order details
2. Click "Cancel Order"
3. Confirm cancellation
4. Order marked as cancelled

### Order Sources
- **POS**: Created by waiters in-app
- **WhatsApp**: Guest orders via WhatsApp

### Receipts
1. Navigate to order
2. Click "Generate Receipt"
3. PDF downloads automatically
4. Includes all items, payments, tips

---

## Inventory Management

### Accessing Inventory
Navigate to: **Dashboard → Inventory** or `/inventory`

### Inventory Overview
- Current stock levels
- Low stock alerts
- Restock history
- Usage patterns

### Stock Monitoring
- **Current Quantity**: Real-time stock
- **Low Stock Threshold**: Alert level
- **Unit**: Measurement unit
- **Status**: In stock, Low stock, Out of stock

### Recording Restocks
1. Click "Restock" on item
2. Enter:
   - **Quantity**: Amount to add
   - **Unit**: Confirm measurement
   - **Notes**: Optional details
3. Click "Save"
4. Stock quantity updates automatically

### Inventory Transactions
View transaction history:
- Restock entries
- Automatic deductions (orders)
- Manual adjustments
- Timestamp and user tracking

### Low Stock Alerts
- Dashboard notification
- Highlighted items in inventory list
- Configurable thresholds
- Proactive ordering reminders

### Inventory Best Practices
- Set realistic thresholds
- Regular stock counts
- Monitor usage patterns
- Plan restocks based on reports
- Track waste and spoilage

---

## Reports

### Accessing Reports
Navigate to: **Dashboard → Reports** or `/reports`

### Report Types

#### Sales Reports
**Path**: `/reports/sales`

Features:
- Date range selection
- Revenue breakdown
- Category performance
- Peak hours analysis
- Average order value
- Payment method breakdown

**How to Use**:
1. Select date range
2. Click "Generate Report"
3. View charts and tables
4. Export to PDF

#### Staff Reports
**Path**: `/reports/staff`

Features:
- Orders per staff member
- Tips earned
- Performance metrics
- Hours worked
- Top performers

**How to Use**:
1. Select date range
2. Choose staff member (or all)
3. Click "Generate Report"
4. View performance data

#### Inventory Reports
**Path**: `/reports/inventory`

Features:
- Stock movement
- Usage patterns
- Popular items
- Waste tracking
- Restock recommendations

**How to Use**:
1. Select date range
2. Filter by category (optional)
3. Click "Generate Report"
4. Review stock data

### Exporting Reports
- PDF format available
- Print-friendly layouts
- Include charts and tables
- Date stamp on reports

### Report Best Practices
- Run daily sales reports
- Weekly staff performance reviews
- Monthly inventory analysis
- Compare periods for trends
- Use data for decision-making

---

## System Settings

### Accessing Settings
Navigate to: **Dashboard → Settings** or `/settings`

### Available Settings

#### Tax Configuration
- **Tax Rate**: Percentage (e.g., 8.5 for 8.5%)
- Applied automatically to all orders
- Displayed on receipts

#### Payment Settings
- Enable/disable payment methods
- Stripe configuration
- Cash handling rules
- Card terminal settings

#### Restaurant Information
- Restaurant name
- Address
- Contact information
- Logo upload
- Receipt footer text

#### Order Settings
- Order number format
- Default order status
- Auto-advance settings
- Notification preferences

#### Notification Settings
- Email notifications
- SMS alerts
- WhatsApp integration
- Real-time push notifications

#### System Preferences
- Date format
- Time zone
- Currency symbol
- Language preferences

### Modifying Settings
1. Navigate to settings page
2. Click "Edit" on setting category
3. Modify values
4. Click "Save"
5. Changes apply immediately

### Critical Settings
- **Tax Rate**: Ensure compliance with local laws
- **Stripe Keys**: Secure API credentials
- **WhatsApp Token**: Valid authentication token
- **Notification Channels**: Proper configuration

---

## Security & Permissions

### Role-Based Access Control
Each role has specific permissions:

#### Admin
- All permissions
- User/staff management
- System configuration
- Security settings

#### Manager
- Business operations
- Reports access
- Menu and inventory
- Staff oversight (limited)

#### Waiter
- Order creation
- Payment processing
- Guest lookup
- Limited order viewing

#### Chef
- Kitchen display
- Order item status
- No order modification

#### Bartender
- Bar display
- Drink item status
- No order modification

### Password Security
- Minimum 8 characters
- Require strong passwords
- Regular password changes
- Never share credentials

### Audit Logging
System tracks:
- User logins
- Order modifications
- Payment transactions
- Settings changes
- User/staff changes

### Accessing Audit Logs
1. Database table: `audit_logs`
2. Records include:
   - Event type
   - User ID
   - Timestamp
   - Old/new values
   - IP address

### Security Best Practices
- Regular password updates
- Monitor audit logs
- Deactivate unused accounts
- Use HTTPS only
- Secure API credentials
- Regular backups
- Update Laravel regularly

---

## Troubleshooting

### Common Issues

#### Staff Cannot Login
**Symptoms**: Login fails with valid credentials
**Solutions**:
1. Verify email is correct
2. Check status is "Active"
3. Reset password
4. Clear browser cache
5. Check role assignment

#### Orders Not Appearing
**Symptoms**: Orders not showing in list
**Solutions**:
1. Check status filter
2. Verify date range
3. Refresh page
4. Check real-time connection
5. Review error logs

#### Payment Processing Fails
**Symptoms**: Stripe payment errors
**Solutions**:
1. Verify Stripe credentials
2. Check internet connection
3. Validate card details
4. Review Stripe dashboard
5. Check webhook configuration

#### Kitchen Display Not Updating
**Symptoms**: Orders not appearing in real-time
**Solutions**:
1. Check WebSocket connection
2. Verify Laravel Reverb is running
3. Refresh display
4. Check broadcasting configuration
5. Review console for errors

#### Low Stock Alerts Not Showing
**Symptoms**: No alerts despite low stock
**Solutions**:
1. Verify threshold settings
2. Check item stock quantity
3. Refresh dashboard
4. Review inventory settings

#### Reports Not Generating
**Symptoms**: Report generation fails
**Solutions**:
1. Check date range validity
2. Verify data exists for period
3. Clear cache
4. Check PDF library installation
5. Review server logs

### Getting Help
1. Check error logs: `/storage/logs/laravel.log`
2. Review error_logs table in database
3. Contact technical support
4. Check Laravel documentation
5. Review API documentation

### System Maintenance
Regular maintenance tasks:
- Database backups
- Log file rotation
- Cache clearing
- Session cleanup
- Performance monitoring

### Emergency Procedures
**System Down**:
1. Check server status
2. Verify database connection
3. Review recent changes
4. Restore from backup if needed
5. Contact hosting provider

**Data Loss**:
1. Stop all operations
2. Do not modify database
3. Restore from latest backup
4. Verify data integrity
5. Document incident

---

## Appendix

### Keyboard Shortcuts
- `Ctrl/Cmd + K`: Quick search
- `Ctrl/Cmd + /`: Help menu
- `Esc`: Close modals

### Important Paths
- Dashboard: `/dashboard`
- Staff: `/staff`
- Menu: `/menu`
- Tables: `/tables`
- Orders: `/orders`
- Inventory: `/inventory`
- Reports: `/reports`
- Settings: `/settings`

### Default Credentials
Change these immediately after installation:
- Admin Email: admin@example.com
- Default Password: password

### Technical Specifications
- Laravel Version: 12.x
- PHP Version: 8.2+
- Database: MySQL 8.0+
- WebSocket: Laravel Reverb
- Frontend: Livewire 4.x + Alpine.js

### Contact Information
For technical support or questions:
- Documentation: `/help`
- Support Email: support@seacliffpos.com
- Emergency: Contact system administrator

---

**Document Version**: 1.0
**Last Updated**: 2026-02-06
**For**: SeaCliff POS v1.0
