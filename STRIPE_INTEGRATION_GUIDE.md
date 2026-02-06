# Stripe Payment Gateway Integration Guide

This guide provides comprehensive instructions for using and testing the Stripe payment integration.

## Overview

The application integrates Stripe for secure card and mobile payments with the following features:
- Stripe Elements for secure payment form
- PaymentIntent API for payment processing
- Webhook handling for payment status updates
- Transaction tracking and gateway response storage
- User-friendly error messages for declined cards

## Configuration

### 1. Environment Variables

Update your `.env` file with your Stripe keys:

```env
STRIPE_PUBLIC_KEY=pk_test_51...
STRIPE_SECRET_KEY=sk_test_51...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_CURRENCY=usd
```

### 2. Get Stripe Keys

#### Test Mode (Development)
1. Go to [Stripe Dashboard](https://dashboard.stripe.com/test/apikeys)
2. Copy your **Publishable key** → `STRIPE_PUBLIC_KEY`
3. Copy your **Secret key** → `STRIPE_SECRET_KEY`

#### Production Mode
1. Go to [Stripe Dashboard](https://dashboard.stripe.com/apikeys)
2. Enable your account for production
3. Copy production keys to your `.env` file

### 3. Webhook Configuration

#### Local Development (using Stripe CLI)
```bash
# Install Stripe CLI
brew install stripe/stripe-cli/stripe

# Login to Stripe
stripe login

# Forward webhooks to local server
stripe listen --forward-to localhost:8000/api/webhooks/stripe

# Copy the webhook signing secret to .env
# Look for "whsec_..." in the output
```

#### Production
1. Go to [Stripe Webhooks](https://dashboard.stripe.com/webhooks)
2. Click "Add endpoint"
3. Enter your webhook URL: `https://yourdomain.com/api/webhooks/stripe`
4. Select events to listen to:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `payment_intent.failed`
   - `payment_intent.canceled`
5. Copy the **Signing secret** → `STRIPE_WEBHOOK_SECRET`

## Architecture

### Backend Components

#### 1. StripePaymentService (`app/Services/StripePaymentService.php`)
Main service for Stripe payment operations:
- `processPayment($orderId, $amount)` - Creates PaymentIntent
- `confirmPayment($paymentIntentId, $stripeResponse)` - Confirms successful payment
- `failPayment($paymentIntentId, $stripeResponse)` - Marks payment as failed
- `retrievePaymentIntent($paymentIntentId)` - Retrieves PaymentIntent from Stripe
- `getErrorMessage($errorCode)` - Returns user-friendly error messages

#### 2. StripePaymentController (`app/Http/Controllers/StripePaymentController.php`)
API endpoints for frontend:
- `POST /api/payments/stripe/create-intent` - Creates PaymentIntent
- `POST /api/payments/stripe/confirm` - Confirms payment (optional, webhook handles this)

#### 3. StripeWebhookController (`app/Http/Controllers/StripeWebhookController.php`)
Handles Stripe webhook events:
- `POST /api/webhooks/stripe` - Webhook endpoint
- Verifies webhook signature
- Handles events: `payment_intent.succeeded`, `payment_intent.failed`, etc.

### Frontend Components

#### 1. Stripe Elements Integration (`resources/js/stripe.js`)
JavaScript module for Stripe Elements:
- `initializeStripeElements(clientSecret)` - Initializes Stripe Elements
- `handlePaymentSubmit(form, returnUrl)` - Processes payment submission
- `handlePaymentReturn()` - Handles redirect after payment
- `createPaymentIntent(orderId, amount)` - Creates PaymentIntent via API

#### 2. Payment Form View (`resources/views/payments/stripe-form.blade.php`)
Payment form with Stripe Elements:
- Order summary
- Stripe Payment Element container
- Error message display
- Test card information (development only)

## Testing

### Test Cards

Stripe provides test cards for different scenarios:

#### Successful Payment
```
Card Number: 4242 4242 4242 4242
Expiry: Any future date (e.g., 12/25)
CVC: Any 3 digits (e.g., 123)
ZIP: Any 5 digits (e.g., 12345)
```

#### Card Declined
```
Card Number: 4000 0000 0000 0002
Result: Generic decline
```

#### Insufficient Funds
```
Card Number: 4000 0000 0000 9995
Result: Card has insufficient funds
```

#### Expired Card
```
Card Number: 4000 0000 0000 0069
Result: Expired card
```

#### Processing Error
```
Card Number: 4000 0000 0000 0119
Result: Processing error
```

#### 3D Secure Authentication Required
```
Card Number: 4000 0025 0000 3155
Result: Requires authentication
```

### Testing Payment Flow

1. **Create an Order**
   - Navigate to the order creation page
   - Add items to the order
   - Submit the order

2. **Initiate Payment**
   - Select "Gateway" as payment method
   - The system creates a PaymentIntent
   - User is redirected to payment form

3. **Complete Payment**
   - Fill in test card details: `4242 4242 4242 4242`
   - Enter any future expiry date
   - Enter any 3-digit CVC
   - Click "Pay" button

4. **Verify Payment**
   - Check payment success page
   - Verify Payment record in database:
     ```sql
     SELECT * FROM payments WHERE order_id = ?;
     ```
   - Confirm `status = 'completed'`
   - Check `gateway_response` contains Stripe data

5. **Test Webhook**
   ```bash
   # Using Stripe CLI (with local forwarding active)
   stripe trigger payment_intent.succeeded

   # Check logs
   tail -f storage/logs/laravel.log
   ```

### Testing Error Handling

1. **Declined Card**
   - Use card: `4000 0000 0000 0002`
   - Verify error message: "Your card was declined..."

2. **Insufficient Funds**
   - Use card: `4000 0000 0000 9995`
   - Verify error message: "Your card has insufficient funds..."

3. **Expired Card**
   - Use card: `4000 0000 0000 0069`
   - Verify error message: "Your card has expired..."

### Testing Webhooks

1. **Test Successful Payment**
   ```bash
   stripe trigger payment_intent.succeeded
   ```

2. **Test Failed Payment**
   ```bash
   stripe trigger payment_intent.payment_failed
   ```

3. **Verify Webhook Signature**
   - Webhook controller verifies signature automatically
   - Invalid signatures are rejected with 400 error
   - Check logs for verification failures

## API Usage

### Create Payment Intent

**Endpoint:** `POST /api/payments/stripe/create-intent`

**Request:**
```json
{
  "order_id": 123,
  "amount": 50.00
}
```

**Response (Success):**
```json
{
  "success": true,
  "client_secret": "pi_xxx_secret_xxx",
  "payment_intent_id": "pi_xxx",
  "payment_id": 456
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Failed to create payment: Invalid amount",
  "error": "Amount must be at least $0.50"
}
```

### Confirm Payment (Optional)

**Endpoint:** `POST /api/payments/stripe/confirm`

**Request:**
```json
{
  "payment_intent_id": "pi_xxx"
}
```

**Response:**
```json
{
  "success": true,
  "payment": {
    "id": 456,
    "order_id": 123,
    "status": "completed",
    "amount": "50.00",
    "transaction_id": "pi_xxx"
  },
  "message": "Payment confirmed successfully"
}
```

## Database Schema

### Payments Table

The `payments` table stores all payment records:

```sql
CREATE TABLE payments (
    id BIGINT PRIMARY KEY,
    order_id BIGINT NOT NULL,
    payment_method VARCHAR(50),
    amount DECIMAL(10,2),
    status VARCHAR(20),
    transaction_id VARCHAR(255),
    gateway_response JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Fields:**
- `order_id` - Links to orders table
- `payment_method` - Set to 'gateway' for Stripe
- `amount` - Payment amount
- `status` - Payment status: pending, completed, failed
- `transaction_id` - Stripe PaymentIntent ID (e.g., pi_xxx)
- `gateway_response` - JSON containing full Stripe response

**Example gateway_response:**
```json
{
  "payment_intent_id": "pi_3ABC123xyz",
  "status": "succeeded",
  "created_at": "2024-02-06T10:30:00Z",
  "completed_at": "2024-02-06T10:30:15Z",
  "stripe_response": {
    "id": "pi_3ABC123xyz",
    "amount": 5000,
    "currency": "usd",
    "payment_method": "pm_1ABC123xyz",
    "charges": [...]
  }
}
```

## Security

### Webhook Signature Verification

All webhooks are verified using the Stripe webhook secret:
```php
$event = Webhook::constructEvent(
    $payload,
    $sigHeader,
    $webhookSecret
);
```

Invalid signatures are rejected with a 400 error.

### API Authentication

Payment endpoints require authentication via Laravel Sanctum:
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('payments/stripe/create-intent', ...);
    Route::post('payments/stripe/confirm', ...);
});
```

### HTTPS in Production

Always use HTTPS in production to protect:
- Stripe API keys
- Payment information
- Webhook payloads

## Troubleshooting

### Issue: "No such PaymentIntent"
**Solution:** Verify the payment_intent_id is correct and PaymentIntent exists in your Stripe account.

### Issue: "Webhook signature verification failed"
**Solution:**
- Verify `STRIPE_WEBHOOK_SECRET` is correct
- Check webhook endpoint is receiving raw request body
- For local testing, ensure Stripe CLI is running

### Issue: "Payment not updating after successful charge"
**Solution:**
- Check webhook is configured correctly
- Verify webhook events include `payment_intent.succeeded`
- Check application logs for webhook errors
- Test webhook manually: `stripe trigger payment_intent.succeeded`

### Issue: "Test card declined"
**Solution:** Use test card `4242 4242 4242 4242` in test mode

### Issue: "Invalid API key"
**Solution:**
- Verify you're using test keys (pk_test_... / sk_test_...) in development
- Check `.env` file has correct keys
- Run `php artisan config:cache` after changing .env

## Production Checklist

Before going live:

- [ ] Replace test keys with production keys
- [ ] Configure production webhook endpoint
- [ ] Verify webhook signature in production
- [ ] Test with real card (small amount)
- [ ] Enable HTTPS on your domain
- [ ] Review Stripe Dashboard settings
- [ ] Set up dispute notifications
- [ ] Configure email receipts (optional)
- [ ] Review fraud detection settings
- [ ] Test refund process (if needed)
- [ ] Document payment reconciliation process

## Support

- Stripe Documentation: https://stripe.com/docs
- Stripe API Reference: https://stripe.com/docs/api
- Stripe Testing: https://stripe.com/docs/testing
- Stripe CLI: https://stripe.com/docs/stripe-cli

## Acceptance Criteria Checklist

All acceptance criteria for Story 39 have been implemented:

- [x] Install Stripe SDK: `composer require stripe/stripe-php`
- [x] Configure .env: STRIPE_PUBLIC_KEY, STRIPE_SECRET_KEY, STRIPE_WEBHOOK_SECRET
- [x] Payment service: `app/Services/StripePaymentService.php` with `processPayment($orderId, $amount)` method
- [x] Create Stripe PaymentIntent when payment_method='gateway' selected
- [x] Frontend: Stripe Elements in `resources/js/stripe.js`
- [x] Handle payment confirmation: create Payment record with transaction_id from Stripe
- [x] Webhook route: `Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])`
- [x] Webhook signature verification: validate using STRIPE_WEBHOOK_SECRET
- [x] Handle webhook events: payment_intent.succeeded, payment_intent.failed
- [x] Update Payment status on webhook: completed or failed based on event
- [x] Store gateway_response: JSON of Stripe response in Payment.gateway_response
- [x] Test mode: configured with test keys and test card 4242 4242 4242 4242
- [x] Error handling: user-friendly messages for declined cards
