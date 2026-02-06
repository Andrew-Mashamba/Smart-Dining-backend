#!/bin/bash

echo "=== Stripe Integration Verification ==="
echo ""

# Check if files exist
echo "✓ Checking critical files..."
files=(
    "app/Services/StripePaymentService.php"
    "app/Http/Controllers/StripePaymentController.php"
    "app/Http/Controllers/StripeWebhookController.php"
    "app/Http/Controllers/Web/StripePaymentWebController.php"
    "resources/js/stripe.js"
    "resources/views/payment/stripe-form.blade.php"
    "resources/views/payment/stripe-success.blade.php"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "  ✓ $file"
    else
        echo "  ✗ MISSING: $file"
    fi
done

echo ""
echo "✓ Checking environment variables..."
if grep -q "STRIPE_PUBLIC_KEY" .env; then
    echo "  ✓ STRIPE_PUBLIC_KEY configured"
else
    echo "  ✗ STRIPE_PUBLIC_KEY missing"
fi

if grep -q "STRIPE_SECRET_KEY" .env; then
    echo "  ✓ STRIPE_SECRET_KEY configured"
else
    echo "  ✗ STRIPE_SECRET_KEY missing"
fi

if grep -q "STRIPE_WEBHOOK_SECRET" .env; then
    echo "  ✓ STRIPE_WEBHOOK_SECRET configured"
else
    echo "  ✗ STRIPE_WEBHOOK_SECRET missing"
fi

echo ""
echo "✓ Checking routes..."
php artisan route:list --name=stripe --json | grep -q "stripe.webhook" && echo "  ✓ Webhook route registered" || echo "  ✗ Webhook route missing"
php artisan route:list --name=payment --json | grep -q "payments.stripe" && echo "  ✓ Payment routes registered" || echo "  ✗ Payment routes missing"

echo ""
echo "✓ Checking Stripe package..."
composer show stripe/stripe-php &>/dev/null && echo "  ✓ stripe/stripe-php installed" || echo "  ✗ stripe/stripe-php not installed"

echo ""
echo "=== Verification Complete ==="
