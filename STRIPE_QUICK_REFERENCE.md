# Stripe Payment Gateway - Quick Reference

## 🚀 Quick Start Guide

### Setup (5 minutes)

1. **Get Stripe Test Keys**
   - Visit: https://dashboard.stripe.com/test/apikeys
   - Copy "Publishable key" (starts with `pk_test_`)
   - Copy "Secret key" (starts with `sk_test_`)

2. **Add to .env**
   ```env
   STRIPE_PUBLIC_KEY=pk_test_51...
   STRIPE_SECRET_KEY=sk_test_51...
   STRIPE_WEBHOOK_SECRET=whsec_...
   ```

3. **Clear Config**
   ```bash
   php artisan config:clear
   ```

4. **Test Payment**
   - Create an order
   - Go to payment page
   - Select "Gateway"
   - Use card: `4242 4242 4242 4242`
   - Expiry: `12/25`, CVC: `123`

---

## 📋 Key Information

### Routes

| Method | Route | Purpose |
|--------|-------|---------|
| POST | `/webhooks/stripe` | Webhook endpoint (for Stripe) |
| GET | `/payments/stripe/{order}` | Payment form |
| GET | `/payments/stripe/success` | Success page |
| POST | `/api/webhooks/stripe` | API webhook endpoint |

### Files

| File | Purpose |
|------|---------|
| `app/Services/StripePaymentService.php` | Main payment service |
| `app/Http/Controllers/StripeWebhookController.php` | Webhook handler |
| `resources/js/stripe.js` | Frontend integration |
| `resources/views/payment/stripe-form.blade.php` | Payment form |

### Test Cards

| Card | Result |
|------|--------|
| `4242 4242 4242 4242` | ✅ Success |
| `4000 0000 0000 0002` | ❌ Declined |
| `4000 0000 0000 9995` | ❌ Insufficient funds |

**Details:** Any future date, any 3-digit CVC, any ZIP

---

## 🔧 Common Tasks

### Test a Payment

1. Create order: `/orders/create`
2. Go to payment: `/orders/{order_id}/payment`
3. Select "Gateway" method
4. Click "Process Payment"
5. Enter test card: `4242 4242 4242 4242`
6. Complete payment

### Check Payment Status

```sql
SELECT id, order_id, amount, status, transaction_id
FROM payments
WHERE order_id = {order_id}
ORDER BY created_at DESC;
```

### View Logs

```bash
tail -f storage/logs/laravel.log | grep Stripe
```

### Test Webhooks Locally

```bash
# Install Stripe CLI
brew install stripe/stripe-cli/stripe

# Login
stripe login

# Forward webhooks
stripe listen --forward-to http://localhost:8000/webhooks/stripe

# Copy webhook secret to .env
# Update STRIPE_WEBHOOK_SECRET with the displayed secret
```

---

## 🐛 Troubleshooting

### Payment Creation Fails

**Check:**
1. `.env` has correct keys
2. Keys are test keys (start with `pk_test_` and `sk_test_`)
3. Run: `php artisan config:clear`

### Webhook Returns 400

**Solution:**
- Webhook secret mismatch
- Use Stripe CLI for local testing
- Copy webhook secret from CLI output

### Payment Stuck "Pending"

**Solution:**
- Webhook not received
- Check Laravel logs
- Verify webhook endpoint is accessible

---

## 🔒 Security Checklist

- ✅ Webhook signature verification enabled
- ✅ PaymentIntents created server-side only
- ✅ API keys in .env (not version controlled)
- ✅ CSRF protection on all forms
- ✅ User-friendly error messages (no technical details)

---

## 📦 What's Implemented

### ✅ All Features

- [x] Stripe SDK installed (v19.3.0)
- [x] Environment configuration
- [x] Payment service with processPayment()
- [x] PaymentIntent creation
- [x] Stripe Elements frontend
- [x] Payment confirmation
- [x] Webhook routes
- [x] Webhook signature verification
- [x] Event handling (succeeded, failed)
- [x] Status updates (completed, failed)
- [x] Gateway response storage
- [x] Test mode support
- [x] Error handling

### Payment Flow

```
User → Select Gateway → Redirect to Stripe Form
  ↓
Enter Card Details → Submit Payment
  ↓
Stripe Processes → Webhook Sent
  ↓
Status Updated → Success Page
```

---

## 🚀 Production Deployment

### Before Going Live

1. **Replace Test Keys**
   ```env
   STRIPE_PUBLIC_KEY=pk_live_...
   STRIPE_SECRET_KEY=sk_live_...
   ```

2. **Setup Webhook**
   - Go to: https://dashboard.stripe.com/webhooks
   - Add endpoint: `https://yourdomain.com/webhooks/stripe`
   - Select events: `payment_intent.succeeded`, `payment_intent.failed`
   - Copy signing secret to `.env`

3. **Verify**
   - SSL certificate valid
   - Webhook endpoint accessible
   - Test with real card

---

## 📞 Support

- **Stripe Docs:** https://stripe.com/docs
- **Test Cards:** https://stripe.com/docs/testing
- **Dashboard:** https://dashboard.stripe.com

---

## 💡 Tips

1. **Always use test mode first** - Test keys are safe and free
2. **Test webhooks locally** - Use Stripe CLI for development
3. **Check logs** - All events are logged for debugging
4. **Monitor dashboard** - Stripe dashboard shows all test payments
5. **Use test cards** - Never use real cards in test mode

---

## Quick Commands

```bash
# Clear config
php artisan config:clear

# View routes
php artisan route:list --path=stripe

# Check logs
tail -f storage/logs/laravel.log

# Test webhook (requires Stripe CLI)
stripe trigger payment_intent.succeeded
```

---

**Status:** ✅ Fully Implemented and Tested
**Documentation:** STRIPE_INTEGRATION_TESTING.md (detailed guide)
