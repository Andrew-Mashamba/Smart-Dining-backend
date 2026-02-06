# SeaCliff POS - Manager Guide

## Table of Contents
1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Dashboard Overview](#dashboard-overview)
4. [Menu Management](#menu-management)
5. [Table Management](#table-management)
6. [Inventory Management](#inventory-management)
7. [Order Oversight](#order-oversight)
8. [Reports & Analytics](#reports--analytics)
9. [Guest Management](#guest-management)
10. [Best Practices](#best-practices)
11. [Troubleshooting](#troubleshooting)

---

## Introduction

Welcome to the SeaCliff POS Manager Guide. As a manager, you oversee daily restaurant operations including menu management, inventory control, table assignments, and business analytics.

### Your Role
As a **Manager**, you can:
- Manage menu items and categories
- Oversee table assignments and layout
- Monitor and manage inventory
- View all orders and track performance
- Generate business reports
- Manage guest information
- Monitor staff performance
- Handle operational decisions

**Note**: You do not have access to system settings, staff account creation, or security configurations. Contact an administrator for these tasks.

---

## Getting Started

### Logging In
1. Navigate to the SeaCliff POS login page
2. Enter your manager credentials (email and password)
3. Click "Login"
4. You'll be redirected to the manager dashboard

### Dashboard Quick Tour
The manager dashboard shows:
- **Today's Metrics**: Orders, revenue, active tables
- **Revenue Chart**: 7-day sales trends
- **Top Selling Items**: Best performers
- **Low Stock Alerts**: Inventory warnings
- **Recent Orders**: Latest order activity
- **Staff on Duty**: Current shift status

---

## Dashboard Overview

### Key Performance Indicators

#### Today's Orders
- Total number of orders processed today
- Includes all statuses: pending, preparing, delivered, paid
- Real-time updates throughout the day

#### Today's Revenue
- Total sales revenue for current day
- Calculated from paid orders
- Includes tax but excludes tips

#### Active Tables
- Number of currently occupied tables
- Shows tables with active orders
- Helps with seating capacity planning

#### Staff on Duty
- Current employees logged in
- Shows roles and active status
- Monitor staffing levels

### Revenue Chart
- Visualizes last 7 days of sales
- Bar chart format
- Hover for exact daily amounts
- Identifies trends and peak days
- Use for staffing and inventory planning

### Top Selling Items
- Best performing menu items
- Sorted by quantity sold
- Shows item name and sales count
- Use for:
  - Menu optimization
  - Inventory planning
  - Promotional decisions
  - Special pricing

### Low Stock Alerts
- Real-time inventory warnings
- Items below minimum threshold
- Action required indicators
- Click to view item details
- Plan restocking immediately

### Recent Orders
- Latest order activity
- Shows order number, table, status
- Click for detailed view
- Monitor kitchen/bar flow
- Identify bottlenecks

---

## Menu Management

### Accessing Menu Management
Navigate to: **Dashboard → Menu** or `/menu`

### Understanding Menu Structure

#### Categories
- Group related menu items
- Examples: Appetizers, Mains, Desserts, Beverages, Bar
- Display order controls menu presentation
- Can be active or inactive

#### Menu Items
- Individual dishes/drinks
- Belong to one category
- Have pricing, prep times, stock info
- Can be toggled active/inactive

### Managing Categories

#### Creating a New Category
1. Click "Add Category" button
2. Fill in details:
   - **Name**: Category name (e.g., "Appetizers", "Cocktails")
   - **Display Order**: Number for sorting (1 = first, 2 = second, etc.)
   - **Status**: Active or Inactive
3. Click "Save"

**Tips**:
- Use logical ordering (appetizers before mains)
- Keep names clear and customer-friendly
- Inactive categories don't show on POS

#### Editing Categories
1. Locate category in list
2. Click "Edit" button
3. Modify name, order, or status
4. Click "Update"
5. Changes reflect immediately

#### Organizing Categories
- Use display order to control menu flow
- Lower numbers appear first
- Reorder anytime without affecting items
- Consider customer ordering patterns

### Managing Menu Items

#### Adding a New Menu Item
1. Click "Add Menu Item" button
2. Fill in all required fields:
   - **Name**: Item name as appears on menu
   - **Description**: Brief description (optional but recommended)
   - **Category**: Select from dropdown
   - **Price**: Decimal format (e.g., 12.99)
   - **Prep Area**: Kitchen or Bar
   - **Prep Time**: Estimated minutes
   - **Stock Quantity**: Current inventory
   - **Low Stock Threshold**: Alert level
   - **Unit**: Measurement (pieces, kg, liters, etc.)
   - **Status**: Active or Inactive
3. Click "Save"

**Field Details**:
- **Name**: Keep concise, avoid special characters
- **Price**: Use decimal point, no currency symbol
- **Prep Area**: Determines which display shows item (Kitchen Display or Bar Display)
- **Prep Time**: Helps with kitchen planning and customer expectations
- **Stock Quantity**: Real-time inventory amount
- **Low Stock Threshold**: System alerts when quantity falls below this
- **Unit**: Choose appropriate measurement for item type

#### Editing Menu Items
1. Find item in menu list
2. Click "Edit" button
3. Modify any field
4. Click "Update"
5. Changes take effect immediately

**Common Edits**:
- Price adjustments for promotions
- Stock updates after restocking
- Description improvements
- Status changes (seasonal items)

#### Quick Availability Toggle
- Toggle switch next to each item
- Turn items on/off quickly
- Useful for:
  - Sold-out items
  - Seasonal specials
  - Daily features
  - Temporary unavailability

#### Stock Management
Each menu item tracks inventory:
- **Current Stock**: Real-time quantity
- **Low Stock Alert**: Warning badge when below threshold
- **Auto-deduction**: Reduces when orders placed
- **Manual Updates**: Edit item to adjust stock

**Stock Best Practices**:
- Set realistic thresholds
- Monitor alerts daily
- Update stock after deliveries
- Review usage patterns in reports

### Menu Optimization Tips
1. **Pricing Strategy**:
   - Review competitor pricing
   - Calculate food cost percentages
   - Use psychological pricing ($9.99 vs $10.00)
   - Adjust based on popularity

2. **Description Writing**:
   - Highlight key ingredients
   - Use appetizing language
   - Mention preparation method
   - Note allergens if critical

3. **Category Organization**:
   - Follow dining progression
   - Group complementary items
   - Separate food and beverages
   - Consider seasonal sections

4. **Availability Management**:
   - Mark seasonal items clearly
   - Deactivate instead of delete
   - Keep sold-out items visible temporarily
   - Communicate changes to staff

---

## Table Management

### Accessing Tables
Navigate to: **Dashboard → Tables** or `/tables`

### Table Overview
Tables represent physical seating areas in your restaurant. Proper table management ensures smooth operations and accurate order tracking.

### Creating Tables

1. Click "Create Table" button
2. Enter details:
   - **Name**: Table identifier (e.g., "Table 1", "T5", "Patio A")
   - **Location**: Section name (e.g., "Main Dining", "Patio", "VIP Room")
   - **Capacity**: Maximum number of guests
   - **Status**: Available, Occupied, or Reserved
3. Click "Save"

**Naming Conventions**:
- Be consistent (all "Table #" or all "T#")
- Include location in name if helpful ("Patio 1")
- Use numbers or letters systematically
- Keep names short for POS display

### Table Status Management

#### Available
- Table is ready for new guests
- No active orders
- Clean and set

#### Occupied
- Guests currently seated
- Active order in progress
- Cannot be assigned to new guests

#### Reserved
- Booked for future guests
- Blocks table from other assignments
- Use for reservations

### Editing Tables
1. Locate table in list
2. Click "Edit" button
3. Update name, location, capacity, or status
4. Click "Update"

**When to Edit**:
- Capacity changes (table configurations)
- Location changes (layout reorganization)
- Status updates (manual override if needed)
- Name corrections

### Table Capacity Planning
- Set accurate capacity numbers
- Consider comfort (not maximum squeeze)
- Account for table combination options
- Use for reservation planning

### QR Code Features
Each table can generate a QR code:
- Guests scan to view menu
- Self-order capability
- Orders automatically link to table
- Contactless dining option

**QR Code Benefits**:
- Reduces waiter load
- Faster ordering
- Improved accuracy
- Better guest experience

### Table Management Best Practices
1. **Keep Status Updated**:
   - Mark occupied when seated
   - Change to available when cleared
   - Use reserved for bookings

2. **Capacity Accuracy**:
   - Set realistic numbers
   - Consider table spacing
   - Plan for combinations

3. **Location Zones**:
   - Group tables by area
   - Use for server assignments
   - Track section performance

4. **Table Turnover**:
   - Monitor average duration
   - Identify slow tables
   - Optimize seating flow

---

## Inventory Management

### Accessing Inventory
Navigate to: **Dashboard → Inventory** or `/inventory`

### Inventory Overview
Inventory tracks menu item stock levels in real-time, helping you:
- Prevent stockouts
- Plan purchases
- Reduce waste
- Maintain availability

### Understanding Inventory Data

For each menu item, you see:
- **Current Stock**: Real-time quantity
- **Low Stock Threshold**: Alert level
- **Unit**: Measurement type
- **Status Indicator**: In stock, Low stock, Out of stock

### Monitoring Stock Levels

#### Stock Status Indicators
- **Green**: In stock (above threshold)
- **Yellow**: Low stock (at or below threshold)
- **Red**: Out of stock (zero quantity)

#### Low Stock Alerts
- Dashboard notification badge
- Highlighted items in inventory list
- Helps prioritize restocking
- Prevents menu unavailability

### Recording Restocks

When you receive inventory deliveries:

1. Navigate to inventory page
2. Find the item to restock
3. Click "Restock" button
4. Enter restock details:
   - **Quantity**: Amount to add to existing stock
   - **Unit**: Confirm measurement (pre-filled)
   - **Notes**: Optional (supplier, date, etc.)
5. Click "Save"
6. Stock quantity updates automatically

**Example**:
- Current stock: 10 kg
- Restock quantity: 25 kg
- New stock: 35 kg

### Inventory Transactions
View complete stock history:
- All restocks logged
- Automatic deductions from orders
- Manual adjustments
- Timestamp and user tracking

**Transaction Details**:
- Date and time
- Type (restock, order deduction)
- Quantity change
- Running balance
- User who made change
- Notes

### Automatic Stock Deduction
When orders are placed:
- System calculates item quantities
- Deducts from current stock automatically
- Real-time inventory updates
- Prevents overselling

### Setting Stock Thresholds

Choose appropriate thresholds for each item:

**High-Volume Items**:
- Set higher thresholds
- Example: French fries threshold = 20 kg
- Allows time for ordering

**Low-Volume Items**:
- Set lower thresholds
- Example: Caviar threshold = 2 units
- Avoids over-ordering

**Perishable Items**:
- Balance availability vs. waste
- Order frequently in smaller amounts
- Adjust based on shelf life

### Inventory Best Practices

1. **Daily Checks**:
   - Review dashboard alerts each morning
   - Check low stock items
   - Plan restocks for next day

2. **Regular Counts**:
   - Weekly physical inventory count
   - Verify system matches reality
   - Adjust discrepancies

3. **Usage Analysis**:
   - Review inventory reports
   - Identify fast movers
   - Spot slow items
   - Adjust thresholds accordingly

4. **Supplier Management**:
   - Track reliable suppliers in notes
   - Record delivery times
   - Note quality issues
   - Build reorder lists

5. **Waste Tracking**:
   - Document spoilage in notes
   - Adjust ordering patterns
   - Review prep processes
   - Train staff on portions

---

## Order Oversight

### Accessing Orders
Navigate to: **Dashboard → Orders** or `/orders`

### Order Lifecycle
Understanding order flow helps you manage operations effectively.

#### Order Statuses
1. **Pending**: Order created, awaiting preparation
2. **Preparing**: Kitchen/bar actively working on items
3. **Ready**: All items complete, ready to serve
4. **Delivered**: Served to guest
5. **Paid**: Payment completed, order closed

### Viewing Orders

#### Order List View
- Shows all orders with key details
- Filter by status, date, table
- Search by order number
- Real-time status updates
- Click order for details

#### Order Details
Click any order to view:
- **Order Information**:
  - Order number
  - Date and time
  - Current status
- **Table & Guest**:
  - Table assignment
  - Guest name/phone (if recorded)
- **Staff**:
  - Waiter who created order
- **Items**:
  - All ordered items
  - Quantities
  - Individual prices
  - Special instructions
  - Item preparation status
- **Financial**:
  - Subtotal
  - Tax amount
  - Total
  - Payment method
  - Payment status
  - Tips

### Monitoring Order Flow

#### Kitchen Performance
- Check average time in "Preparing" status
- Identify bottlenecks
- Monitor prep area workload
- Ensure timely completion

#### Service Performance
- Track time from "Ready" to "Delivered"
- Monitor waiter efficiency
- Identify service delays
- Improve table turnover

### Order Management Actions

While managers don't typically create orders, you can:
- **View All Orders**: Complete visibility
- **Monitor Status**: Track progress
- **Assist with Issues**: Help resolve problems
- **Generate Reports**: Analyze performance

### Order Source Tracking
Orders come from:
- **POS**: Waiter-created in-app
- **WhatsApp**: Guest orders via messaging
- **QR Code**: Guest self-ordering

Track sources to:
- Understand ordering patterns
- Optimize channels
- Staff appropriately

### Receipt Management
1. Navigate to order details
2. Click "Generate Receipt"
3. PDF downloads automatically
4. Contains:
   - All items and prices
   - Tax breakdown
   - Payment information
   - Tips
   - Restaurant information

**Receipt Uses**:
- Customer copies
- Accounting records
- Audit trail
- Dispute resolution

---

## Reports & Analytics

### Accessing Reports
Navigate to: **Dashboard → Reports** or `/reports`

### Report Types

#### Sales Reports
**Path**: `/reports/sales`

**What You'll See**:
- Total revenue for selected period
- Revenue by category
- Revenue by day/hour
- Payment method breakdown
- Average order value
- Number of orders
- Tax collected

**How to Use**:
1. Select date range
2. Click "Generate Report"
3. Review charts and tables
4. Identify trends
5. Export to PDF if needed

**Business Insights**:
- Peak sales days
- Best performing categories
- Popular ordering times
- Payment preferences
- Revenue growth trends

#### Staff Reports
**Path**: `/reports/staff`

**What You'll See**:
- Orders per staff member
- Revenue generated by each waiter
- Tips earned
- Average order value per staff
- Performance rankings

**How to Use**:
1. Select date range
2. Choose specific staff or view all
3. Click "Generate Report"
4. Analyze performance data

**Management Uses**:
- Identify top performers
- Spot training needs
- Fair tip distribution verification
- Scheduling optimization
- Recognition and rewards

#### Inventory Reports
**Path**: `/reports/inventory`

**What You'll See**:
- Stock movements
- Consumption rates
- Popular items by quantity
- Restock history
- Current stock levels
- Low stock items

**How to Use**:
1. Select date range
2. Filter by category (optional)
3. Click "Generate Report"
4. Review usage patterns

**Operational Uses**:
- Purchasing decisions
- Waste reduction
- Menu optimization
- Cost control
- Supplier negotiations

### Exporting Reports
All reports can be exported:
- **PDF Format**: Professional, printable
- **Date Stamped**: Shows generation date
- **Complete Data**: All tables and charts included
- **Archival**: Keep for records

**Export Steps**:
1. Generate desired report
2. Click "Export to PDF"
3. PDF downloads to your device
4. Save or print as needed

### Using Reports for Decision Making

#### Daily Operations
- Morning: Check yesterday's sales
- Plan staffing based on expected volume
- Adjust inventory orders
- Identify items to promote

#### Weekly Reviews
- Compare day-to-day performance
- Analyze peak periods
- Review staff performance
- Plan weekly inventory orders

#### Monthly Analysis
- Trend identification
- Budget vs. actual comparison
- Menu performance evaluation
- Seasonal planning

#### Strategic Planning
- Long-term trend analysis
- Menu development decisions
- Pricing strategy adjustments
- Expansion considerations

---

## Guest Management

### Accessing Guests
Navigate to: **Dashboard → Guests** or `/guests`

### Guest Management Overview
Track returning customers, preferences, and loyalty to enhance service and build relationships.

### Creating Guest Profiles

1. Click "Create Guest"
2. Enter information:
   - **Name**: Guest full name
   - **Phone**: Unique phone number (primary identifier)
   - **Email**: Optional email address
   - **Loyalty Points**: Starting balance (usually 0)
   - **Preferences**: Notes about preferences, allergies, etc.
3. Click "Save"

**When to Create**:
- Guest requests
- Frequent visitors
- Reservation bookings
- Loyalty program signups

### Viewing Guest Information

Click on any guest to see:
- **Contact Details**: Name, phone, email
- **Order History**: All past orders
- **Statistics**:
  - Total orders placed
  - Total amount spent
  - Average order value
- **Loyalty Points**: Current balance
- **Preferences**: Saved notes

### Editing Guest Information
1. Click "Edit" on guest profile
2. Update any information
3. Click "Save"
4. Changes reflected immediately

**Update When**:
- Contact info changes
- Preferences updated
- Manual loyalty adjustments
- Correcting errors

### Guest Lookup
Find guests quickly:
- **Search by Phone**: Primary lookup method
- **Search by Name**: Browse alphabetically
- **Filter Options**: Sort by points, visits, etc.

**Use Cases**:
- Taking reservations
- Applying loyalty rewards
- Referencing preferences
- Order assignment

### Loyalty Points System

#### How It Works
- Points awarded per order
- Accumulate over time
- Redeem for rewards (configured by admin)
- Track in guest profile

#### Managing Points
- View current balance
- See point history
- Manual adjustments (if authorized)
- Track redemptions

**Loyalty Benefits**:
- Encourage repeat visits
- Track valuable customers
- Reward loyalty
- Build relationships

### Guest Preferences
Document important information:
- Dietary restrictions
- Allergies
- Favorite items
- Seating preferences
- Special occasions
- Service notes

**Using Preferences**:
- Personalize service
- Avoid issues (allergies)
- Anticipate needs
- Enhance experience

### Privacy & Data
- Treat guest data confidentially
- Don't share without permission
- Use for service enhancement only
- Comply with privacy regulations

---

## Best Practices

### Daily Manager Routines

#### Morning (Pre-Service)
1. Review overnight orders (if applicable)
2. Check inventory alerts
3. Verify staff schedule
4. Confirm reservations
5. Prepare daily specials in system
6. Quick menu availability check
7. Review yesterday's sales report

#### During Service
1. Monitor order flow
2. Watch kitchen/bar display times
3. Assist with issues
4. Check table turnover
5. Monitor inventory levels
6. Support waitstaff
7. Handle guest concerns

#### Evening (Post-Service)
1. Review day's sales
2. Generate daily report
3. Check closing inventory
4. Note any issues for follow-up
5. Plan tomorrow's prep
6. Secure sensitive data
7. Log out properly

### Weekly Management Tasks

#### Monday
- Review weekend performance
- Generate weekly sales report
- Plan week's inventory needs
- Schedule staff

#### Mid-Week
- Stock deliveries and updates
- Menu adjustments based on inventory
- Staff performance check-in
- Guest feedback review

#### Friday
- Prepare for weekend volume
- Ensure adequate inventory
- Verify staff coverage
- Review reservations

#### Sunday
- Week-in-review analysis
- Plan next week
- Update menu as needed
- Generate weekly reports

### Monthly Best Practices

1. **Financial Review**:
   - Complete sales analysis
   - Compare to budget/goals
   - Identify trends
   - Adjust strategies

2. **Inventory Assessment**:
   - Full physical count
   - Reconcile with system
   - Adjust thresholds
   - Evaluate suppliers

3. **Menu Evaluation**:
   - Review item performance
   - Update seasonal items
   - Adjust pricing if needed
   - Remove underperformers

4. **Staff Development**:
   - Performance reviews
   - Training needs assessment
   - Recognition for top performers
   - Address issues

### Operational Excellence

#### Communication
- Clear shift handoffs
- Document issues in notes
- Share important updates
- Regular team meetings

#### Quality Control
- Consistent standards
- Regular menu sampling
- Guest feedback monitoring
- Continuous improvement

#### Efficiency
- Streamline processes
- Optimize table layouts
- Reduce waste
- Improve speed of service

#### Customer Service
- Lead by example
- Handle escalations professionally
- Exceed expectations
- Build guest relationships

---

## Troubleshooting

### Common Issues

#### Cannot Access Certain Features
**Issue**: Features like staff management or settings are unavailable
**Reason**: Manager role has limited permissions
**Solution**: Contact an administrator for:
- System settings changes
- Staff account creation/modification
- Security configurations
- Advanced features

#### Inventory Not Updating
**Issue**: Stock levels don't change after orders
**Reason**: Possible system configuration issue
**Solutions**:
1. Refresh the page
2. Check if order was actually completed
3. Verify item has stock tracking enabled
4. Review inventory transaction log
5. Contact administrator if persistent

#### Reports Not Generating
**Issue**: Report page shows error or no data
**Solutions**:
1. Verify date range is valid
2. Check if data exists for selected period
3. Try different date range
4. Clear browser cache
5. Try different browser
6. Contact technical support

#### Menu Changes Not Appearing on POS
**Issue**: Updated menu items don't show for waiters
**Solutions**:
1. Verify item status is "Active"
2. Check category status is "Active"
3. Ask waiter to refresh their page
4. Clear application cache
5. Check if item price is set correctly

#### Low Stock Alerts Not Showing
**Issue**: Dashboard doesn't show inventory warnings
**Solutions**:
1. Verify thresholds are set on items
2. Check actual stock quantity vs. threshold
3. Refresh dashboard
4. Ensure items are active
5. Review inventory settings

### Getting Help

#### In-App Help
- Click **?** icon next to any feature
- Tooltip appears with quick guidance
- Available throughout the system

#### Documentation
- Visit `/help` page
- Access all role-based guides
- Search specific topics
- PDF versions available

#### Technical Support
- Contact system administrator
- Provide specific error details
- Note what you were doing
- Include screenshots if possible

#### Error Logging
If you encounter errors:
1. Note exact error message
2. Record what action caused it
3. Try to reproduce
4. Report to administrator

### Escalation
For critical issues:
1. Identify impact level
2. Try quick fixes first
3. Contact administrator immediately
4. Document the issue
5. Implement workaround if available

---

## Appendix

### Keyboard Shortcuts
- `Ctrl/Cmd + K`: Quick search
- `Ctrl/Cmd + /`: Help menu
- `Esc`: Close modals/dialogs

### Important Paths
- Dashboard: `/dashboard`
- Menu: `/menu`
- Tables: `/tables`
- Orders: `/orders`
- Guests: `/guests`
- Inventory: `/inventory`
- Reports: `/reports`
- Help: `/help`

### Manager Permissions Summary

**You CAN**:
- Manage menu items and categories
- Manage tables
- View all orders
- Manage inventory
- Generate reports
- Manage guests
- View staff on duty

**You CANNOT**:
- Create/edit staff accounts
- Access system settings
- Configure payment methods
- Change security settings
- Access audit logs
- Modify user roles

### Contact Information
- Administrator: Contact via internal channels
- Technical Support: support@seacliffpos.com
- Documentation: `/help`
- Emergency: Contact restaurant owner/administrator

### Tips for Success

1. **Stay Organized**:
   - Use reports regularly
   - Keep accurate notes
   - Follow routines consistently

2. **Be Proactive**:
   - Monitor alerts
   - Plan ahead
   - Anticipate needs

3. **Communicate Effectively**:
   - Clear instructions
   - Document issues
   - Share knowledge

4. **Focus on Data**:
   - Review reports
   - Track trends
   - Make informed decisions

5. **Continuous Learning**:
   - Explore features
   - Read documentation
   - Ask questions
   - Share feedback

---

**Document Version**: 1.0
**Last Updated**: 2026-02-06
**For**: SeaCliff POS v1.0
**Role**: Manager

*Related guides: [Admin Guide](ADMIN_GUIDE.md) | [Waiter Guide](WAITER_GUIDE.md) | [Chef Guide](CHEF_GUIDE.md) | [Bartender Guide](BARTENDER_GUIDE.md)*
