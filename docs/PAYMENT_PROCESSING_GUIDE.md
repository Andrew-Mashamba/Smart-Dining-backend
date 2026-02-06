# Payment Processing Guide - Story 30

## Overview
The Payment Processing system allows staff to process payments for orders with support for multiple payment methods, split payments, tips, and receipt generation.

## Access
**Route**: `/orders/{order}/payment`
**Named Route**: `orders.payment`
**Middleware**: `auth`
**Component**: `App\Livewire\ProcessPayment`

## Features

### 1. Payment Methods Supported
- **Cash** - Physical currency payments
- **Card** - Credit/Debit card payments
- **Mobile** - Mobile payment services
- **Gateway** - Online payment gateway

### 2. Split Payment Support
The system fully supports split payments:
- Make multiple partial payments until order is fully paid
- System tracks total paid and remaining balance
- Validation prevents overpayment
- Each payment is recorded separately
- Order status updates to 'paid' only when fully paid

**Example Split Payment Flow**:
1. Order total: $100.00
2. First payment: $60.00 (cash)
3. Remaining balance: $40.00
4. Second payment: $40.00 (card)
5. Order status → 'paid'

### 3. Tip Management
- **Suggested Tips**: Quick buttons for 10%, 15%, 20% of order total
- **Custom Amount**: Enter any tip amount
- **Tip Methods**: Cash, Card, or Mobile
- **Waiter Assignment**: Tips automatically assigned to order's waiter
- **Optional**: Can skip tip if not desired

### 4. Receipt Generation
- **Format**: PDF
- **Styling**: Monochrome (professional black and white)
- **Filename**: `receipt-{ORDER_NUMBER}.pdf`
- **Contents**:
  - Business information and logo
  - Order number and date
  - Table, waiter, and guest information
  - Complete item list with quantities and prices
  - Subtotal, tax (18%), and total
  - Payment history with methods and timestamps
  - Tip information (if added)
  - Special instructions (if any)

## Usage Guide

### Processing a Full Payment

1. Navigate to order details page
2. Click payment button or navigate to `/orders/{order_id}/payment`
3. Select payment method (cash, card, mobile, or gateway)
4. Amount field is pre-filled with order total
5. Click "Process Payment" button
6. Success message appears
7. Order status updates to 'paid'
8. Tip section appears

### Processing Split Payments

1. Navigate to payment page
2. Select payment method
3. Enter partial amount (e.g., $50 of $100 total)
4. Click "Process Payment"
5. System shows remaining balance ($50)
6. Form resets and pre-fills with remaining balance
7. Repeat for additional payments
8. When fully paid, order status updates to 'paid'

### Adding a Tip

1. After payment processed, tip section appears
2. Option 1: Click suggested tip button (10%, 15%, or 20%)
3. Option 2: Enter custom tip amount
4. Select tip method (cash, card, or mobile)
5. Click "Add Tip" button
6. Tip recorded and assigned to waiter

OR

- Click "Skip Tip" to proceed without adding a tip

### Downloading Receipt

1. Click "Download Receipt" button
2. PDF automatically downloads
3. Receipt includes all order and payment details
4. Professional monochrome styling

## UI Elements

### Payment Form
```
┌─────────────────────────────────────────┐
│ Order Summary                           │
│ ┌─────────────────────────────────────┐ │
│ │ Order #: ORD-20260206-0001          │ │
│ │ Date: Feb 06, 2026 14:30           │ │
│ │ Table: Table 5                      │ │
│ │ Waiter: John Doe                    │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ Payment Method                          │
│ ┌───────┐ ┌───────┐ ┌───────┐ ┌──────┐│
│ │ Cash  │ │ Card  │ │Mobile │ │Gateway││
│ └───────┘ └───────┘ └───────┘ └──────┘│
│                                         │
│ Payment Amount                          │
│ ┌─────────────────────────────────────┐ │
│ │ $ 100.00                            │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ ┌─────────────────────────────────────┐ │
│ │      Process Payment                │ │
│ └─────────────────────────────────────┘ │
└─────────────────────────────────────────┘
```

### Tip Section
```
┌─────────────────────────────────────────┐
│ Add Tip (Optional)                      │
│                                         │
│ Suggested Amounts                       │
│ ┌─────┐ ┌─────┐ ┌─────┐               │
│ │ 10% │ │ 15% │ │ 20% │               │
│ │$10.00│ │$15.00│ │$20.00│             │
│ └─────┘ └─────┘ └─────┘               │
│                                         │
│ Custom Tip Amount                       │
│ ┌─────────────────────────────────────┐ │
│ │ $ 0.00                              │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ Tip Method: [Cash ▼]                   │
│                                         │
│ ┌────────────┐ ┌────────────┐         │
│ │  Add Tip   │ │  Skip Tip  │         │
│ └────────────┘ └────────────┘         │
└─────────────────────────────────────────┘
```

## Validation Rules

### Payment Validation
- **payment_method**: Required, must be one of: cash, card, mobile, gateway
- **amount**: Required, numeric, minimum $0.01, cannot exceed remaining balance

### Tip Validation
- **tip_amount**: Required, numeric, minimum $0.01
- **tip_method**: Required, must be one of: cash, card, mobile

## Database Structure

### Payment Record
```php
[
    'order_id' => 1,
    'payment_method' => 'cash',
    'amount' => 100.00,
    'status' => 'completed',
    'transaction_id' => null, // For gateway payments
    'gateway_response' => null, // For gateway payments
]
```

### Tip Record
```php
[
    'order_id' => 1,
    'waiter_id' => 5,
    'amount' => 15.00,
    'tip_method' => 'card',
]
```

## API Methods

### ProcessPayment Component

#### `mount(Order $order)`
Initializes component with order data

#### `processPayment()`
Processes a payment transaction
- Validates payment data
- Creates Payment record
- Updates order status if fully paid
- Shows tip section

#### `processTip()`
Adds a tip to the order
- Validates tip data
- Creates or updates Tip record
- Associates with waiter

#### `downloadReceipt()`
Generates and downloads PDF receipt

#### `setTipAmount($percentage)`
Sets tip amount based on percentage (10, 15, or 20)

#### `skipTip()`
Skips tip and redirects to orders list

## Payment Status Flow

```
Order Created (status: pending)
        ↓
First Payment Received (status: pending)
        ↓
Additional Payments... (status: pending)
        ↓
Fully Paid (status: paid) ← Order status changes here
        ↓
Tip Added (optional)
        ↓
Receipt Generated
```

## Code Examples

### Accessing Payment Page from Blade
```blade
<a href="{{ route('orders.payment', $order->id) }}">
    Process Payment
</a>
```

### Checking Payment Status
```php
$order = Order::find(1);
$totalPaid = $order->payments()->where('status', 'completed')->sum('amount');
$remainingBalance = $order->total - $totalPaid;

if ($order->isPaid()) {
    // Order fully paid
}
```

### Accessing Payment History
```php
$order = Order::find(1);
$payments = $order->payments;

foreach ($payments as $payment) {
    echo $payment->payment_method . ': $' . $payment->amount;
}
```

### Checking Tip
```php
$order = Order::find(1);

if ($order->tip) {
    echo 'Tip: $' . $order->tip->amount;
    echo 'Tip Method: ' . $order->tip->tip_method;
}
```

## Error Handling

### Common Errors and Solutions

1. **"Payment amount cannot exceed remaining balance"**
   - Amount entered is more than what's owed
   - Solution: Enter amount ≤ remaining balance

2. **"Please select a payment method"**
   - No payment method selected
   - Solution: Click one of the payment method options

3. **"Please enter a payment amount"**
   - Amount field is empty
   - Solution: Enter a valid amount

4. **"Failed to process payment"**
   - Database error or validation failure
   - Solution: Check logs, verify database connection

## Security Features

- **Authentication Required**: Only authenticated users can access
- **Database Transactions**: All payments wrapped in transactions
- **Validation**: Comprehensive input validation
- **Error Handling**: Try-catch blocks prevent data corruption
- **CSRF Protection**: Laravel CSRF tokens on all forms

## Performance Considerations

- **Eager Loading**: Order relationships loaded efficiently
- **Calculation Caching**: Payment totals calculated once per load
- **PDF Generation**: On-demand generation, not stored
- **Session Flash**: Temporary success messages

## Troubleshooting

### Payment Not Processing
1. Check database connection
2. Verify order exists and is not cancelled
3. Check validation errors in form
4. Review application logs

### Receipt Not Downloading
1. Verify DomPDF is installed: `composer show barryvdh/laravel-dompdf`
2. Check PDF template exists: `resources/views/pdf/receipt.blade.php`
3. Verify order has items
4. Check browser download settings

### Tip Not Saving
1. Verify order has a waiter assigned
2. Check tip amount is valid
3. Verify tip method is selected
4. Check database connection

## Related Components

- **OrderDetails** (`/orders/{order}`) - View order details and quick payment
- **OrdersList** (`/orders`) - List all orders with payment status
- **Reports** (`/reports`) - Payment and tip reports

## File Locations

- **Component**: `/app/Livewire/ProcessPayment.php`
- **View**: `/resources/views/livewire/process-payment.blade.php`
- **Receipt Template**: `/resources/views/pdf/receipt.blade.php`
- **Payment Model**: `/app/Models/Payment.php`
- **Tip Model**: `/app/Models/Tip.php`
- **Route**: `/routes/web.php:73`

---

**Last Updated**: 2026-02-06
**Version**: 1.0
**Story**: Story 30
