# SeaCliff POS - Bartender Guide

## Table of Contents
1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Bar Display System (BDS)](#bar-display-system-bds)
4. [Managing Drink Orders](#managing-drink-orders)
5. [Order Status Updates](#order-status-updates)
6. [Priority Management](#priority-management)
7. [Communication](#communication)
8. [Best Practices](#best-practices)
9. [Troubleshooting](#troubleshooting)

---

## Introduction

Welcome to the SeaCliff POS Bartender Guide. As a bartender, you use the Bar Display System (BDS) to view incoming drink orders, manage preparation, and update order status in real-time.

### Your Role
As a **Bartender**, you can:
- View bar orders in real-time
- See drink details and special instructions
- Update individual item status
- Mark items as preparing, ready, or completed
- View order priorities and timing
- Communicate order status to front-of-house

**Note**: You cannot create orders, process payments, manage menu items, or access administrative functions. Your focus is on preparing drinks efficiently.

---

## Getting Started

### Logging In
1. Navigate to the SeaCliff POS login page
2. Enter your bartender credentials (email and password)
3. Click "Login"
4. You'll be automatically redirected to the Bar Display System

### First Time Setup
After your first login:
1. Verify display is showing correctly
2. Test audio notifications (if enabled)
3. Familiarize yourself with the layout
4. Review any pending orders
5. Understand the color coding system
6. Ask questions if unclear

### Bar Display Overview
The Bar Display System shows:
- **Active Orders**: Current drink orders awaiting preparation
- **Order Number**: Unique identifier for tracking
- **Table Number**: Location for delivery
- **Item Details**: Drink name, quantity, special requests
- **Status Indicators**: Visual cues for order state
- **Timer**: Time elapsed since order placed
- **Priority Indicators**: Urgent or special priority orders

---

## Bar Display System (BDS)

### Accessing the Bar Display
- **URL**: `/bar`
- **Navigation**: Click "Bar Display" in the main navigation menu
- **Auto-refresh**: Display updates automatically when new orders arrive

### Display Layout
The Bar Display is organized into sections:

#### 1. New Orders Section
- Shows newly received drink orders
- Items flash or highlight when first received
- Audio notification plays (if enabled)
- Action: Mark as "Received" to acknowledge

#### 2. In Progress Section
- Shows drinks currently being prepared
- Timer shows preparation duration
- Items sorted by order time (oldest first)
- Action: Mark as "Ready" when complete

#### 3. Ready for Pickup Section
- Shows completed drinks awaiting delivery
- Waiters are notified automatically
- Items remain until picked up
- Action: Automatically removed when delivered

### Real-Time Updates
The display uses WebSocket technology for instant updates:
- New orders appear immediately
- Status changes reflect instantly
- No page refresh needed
- Works seamlessly across devices

---

## Managing Drink Orders

### Viewing Order Details
Each order card displays:
- **Order ID**: Unique order identifier (e.g., #1234)
- **Table Number**: Delivery location (e.g., Table 5)
- **Drink Name**: Item to prepare (e.g., Mojito, Espresso Martini)
- **Quantity**: Number of items to prepare
- **Special Instructions**: Custom requests (e.g., "Extra ice", "No sugar")
- **Allergies**: Important customer allergy information
- **Timer**: Duration since order received
- **Priority Badge**: If order is marked as urgent

### Order Item Statuses
Each drink item can have the following statuses:

1. **Pending** (Yellow)
   - Just received, awaiting acknowledgment
   - Action: Click "Mark as Received"

2. **Received** (Blue)
   - Acknowledged but not started
   - Action: Click "Start Preparing"

3. **Preparing** (Orange)
   - Currently being made
   - Timer shows preparation time
   - Action: Click "Mark as Ready" when done

4. **Ready** (Green)
   - Completed and awaiting pickup
   - Waiter is notified
   - Action: None (waiter will collect)

5. **Completed** (Gray)
   - Picked up and delivered to customer
   - Archived automatically

### Marking Orders as Received
When a new order appears:
1. Read the drink details and special instructions
2. Click the **"Mark as Received"** button
3. The order moves to the "In Progress" section
4. Timer starts tracking preparation time

### Updating Preparation Status
As you prepare drinks:
1. Click **"Start Preparing"** when you begin
2. The item status changes to "Preparing"
3. Focus on the order without interruption
4. Follow special instructions carefully

### Marking Orders as Ready
When a drink is complete:
1. Verify the drink matches the order
2. Check special instructions are followed
3. Click **"Mark as Ready"** button
4. Place drink in pickup area
5. Waiter receives automatic notification

---

## Order Status Updates

### Status Workflow
The standard workflow for drink orders:

```
Pending → Received → Preparing → Ready → Completed
```

### Visual Indicators
- **Yellow Badge**: New order (pending)
- **Blue Badge**: Acknowledged (received)
- **Orange Badge**: In progress (preparing)
- **Green Badge**: Done (ready)
- **Flashing**: Urgent or priority order
- **Red Border**: Over time threshold

### Automatic Notifications
The system sends automatic notifications:
- **To Bartender**: New drink order received (audio + visual)
- **To Waiter**: Drink ready for pickup (push notification)
- **To Manager**: Order taking too long (alert)

---

## Priority Management

### Understanding Priority
Orders may have priority levels:
- **Normal**: Standard service
- **Urgent**: VIP guest or time-sensitive
- **Rush**: Multiple drinks for large party

### Priority Indicators
- **Badge Color**: Red for urgent
- **Position**: Priority orders appear at top
- **Icon**: Exclamation mark (!) symbol

### Handling Priority Orders
1. Focus on priority orders first
2. Communicate any delays immediately
3. Alert manager if unable to fulfill quickly
4. Don't compromise quality for speed

---

## Communication

### With Front-of-House Staff
- Use the order notes to add comments
- Mark items ready promptly for pickup
- Alert waiters to special presentations
- Communicate any ingredient issues

### With Management
- Report inventory shortages immediately
- Alert to equipment malfunctions
- Suggest menu improvements
- Report consistent order issues

### System Notifications
You'll receive notifications for:
- New drink orders
- Priority orders
- Special instructions
- System updates

---

## Best Practices

### Order Management
1. **Acknowledge Quickly**: Mark orders as received immediately
2. **Check Details**: Read special instructions before starting
3. **Update Status**: Keep status current throughout preparation
4. **Quality First**: Never rush at expense of quality
5. **Communication**: Alert team to any issues

### Time Management
1. **Batch Similar Drinks**: Prepare multiple of same drink together
2. **Prep Ingredients**: Keep commonly used items ready
3. **Prioritize Efficiently**: Balance speed with order priority
4. **Stay Organized**: Keep workspace clean and organized
5. **Plan Ahead**: Review incoming orders while working

### Customer Satisfaction
1. **Follow Instructions**: Honor all special requests
2. **Allergy Awareness**: Take allergies very seriously
3. **Consistent Quality**: Maintain drink standards
4. **Presentation**: Ensure drinks look professional
5. **Timely Service**: Keep preparation times reasonable

### System Usage
1. **Keep Display Updated**: Always update status changes
2. **Monitor Active Orders**: Check display regularly
3. **Report Issues**: Alert IT to any system problems
4. **Stay Logged In**: Don't log out during service
5. **Use Audio Notifications**: Enable sound alerts

---

## Troubleshooting

### Common Issues and Solutions

#### Order Not Appearing
**Problem**: New order placed but not showing in display

**Solutions**:
1. Check your internet connection
2. Refresh the page (F5 or Cmd+R)
3. Verify you're on the Bar Display page
4. Check with manager if issue persists

#### Cannot Update Status
**Problem**: Clicking status buttons doesn't work

**Solutions**:
1. Refresh the browser page
2. Check internet connection
3. Log out and log back in
4. Clear browser cache
5. Contact IT support

#### Display Not Updating
**Problem**: New orders aren't appearing automatically

**Solutions**:
1. Check WebSocket connection indicator
2. Refresh the page manually
3. Check internet connection stability
4. Try a different browser
5. Report to IT department

#### Missing Order Details
**Problem**: Order shows but details are incomplete

**Solutions**:
1. Click on order card to expand
2. Refresh the page
3. Ask waiter for details
4. Contact manager if critical

#### Audio Notifications Not Working
**Problem**: No sound when new orders arrive

**Solutions**:
1. Check browser notification permissions
2. Verify device volume is not muted
3. Check browser sound settings
4. Enable notifications in system settings
5. Use visual alerts as backup

### Technical Support
If you encounter technical issues:
1. Try basic troubleshooting first
2. Note the error message if any
3. Document steps to reproduce
4. Contact your manager
5. Manager will escalate to IT if needed

### Emergency Procedures
In case of system failure:
1. Alert manager immediately
2. Switch to manual order tracking (pen & paper)
3. Check physical order tickets
4. Communicate with waiters directly
5. Continue service without interruption

---

## Quick Reference

### Keyboard Shortcuts
- **R**: Mark selected order as Received
- **P**: Mark selected order as Preparing
- **D**: Mark selected order as Done/Ready
- **F5**: Refresh display
- **Tab**: Navigate between orders

### Status Color Codes
- 🟡 **Yellow**: Pending (new order)
- 🔵 **Blue**: Received (acknowledged)
- 🟠 **Orange**: Preparing (in progress)
- 🟢 **Green**: Ready (completed)
- ⚪ **Gray**: Completed (archived)

### Important Times
- **Acknowledge orders**: Within 30 seconds
- **Standard drinks**: 3-5 minutes
- **Complex drinks**: 5-8 minutes
- **Priority orders**: As soon as possible

### Contact Information
- **Manager**: Available on floor during service
- **IT Support**: [Contact details from management]
- **Emergency**: Alert manager immediately

---

## Additional Resources

### Related Documentation
- [Payment Processing Guide](PAYMENT_PROCESSING_GUIDE.md) - For understanding order flow
- [Manager Guide](MANAGER_GUIDE.md) - For escalation procedures
- [Waiter Guide](WAITER_GUIDE.md) - For understanding front-of-house workflow

### Training Materials
- Contact your manager for hands-on training
- Review order history to understand patterns
- Ask experienced bartenders for tips
- Attend team meetings for updates

---

**Last Updated**: February 2026
**Version**: 1.0
**For Questions**: Contact your manager or IT support

---

*This guide is part of the SeaCliff POS documentation suite. For more information, visit the help page at `/help`.*
