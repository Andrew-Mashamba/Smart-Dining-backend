/**
 * Stripe Elements Integration
 *
 * This file handles Stripe payment processing with Stripe Elements
 * For test mode, use card: 4242 4242 4242 4242
 */

// Initialize Stripe with publishable key
const stripe = Stripe(window.stripePublicKey);

let elements;
let paymentElement;

/**
 * Initialize Stripe Elements
 * @param {string} clientSecret - The client secret from PaymentIntent
 */
export async function initializeStripeElements(clientSecret) {
    // Create Elements instance
    elements = stripe.elements({ clientSecret });

    // Create and mount the Payment Element
    paymentElement = elements.create('payment', {
        layout: {
            type: 'tabs',
            defaultCollapsed: false,
        },
        business: {
            name: window.appName || 'Hospitality System',
        },
    });

    paymentElement.mount('#payment-element');

    // Handle real-time validation errors
    paymentElement.on('change', (event) => {
        displayError(event.error ? event.error.message : '');
    });
}

/**
 * Handle payment form submission
 * @param {HTMLFormElement} form - The payment form element
 * @param {string} returnUrl - URL to return to after payment
 */
export async function handlePaymentSubmit(form, returnUrl) {
    setLoading(true);

    const { error } = await stripe.confirmPayment({
        elements,
        confirmParams: {
            return_url: returnUrl,
        },
    });

    if (error) {
        // Payment failed - show error to user
        const messageContainer = document.querySelector('#error-message');
        messageContainer.textContent = getErrorMessage(error);
        setLoading(false);
    } else {
        // Payment succeeded or requires additional action
        // The customer will be redirected to the return_url
        // Stripe will add payment_intent and payment_intent_client_secret to the URL
    }
}

/**
 * Handle payment confirmation after redirect
 * Call this on the return page to check payment status
 */
export async function handlePaymentReturn() {
    const clientSecret = new URLSearchParams(window.location.search).get(
        'payment_intent_client_secret'
    );

    if (!clientSecret) {
        return null;
    }

    setLoading(true);

    const { error, paymentIntent } = await stripe.retrievePaymentIntent(clientSecret);

    if (error) {
        displayError(error.message);
        setLoading(false);
        return { success: false, error };
    }

    setLoading(false);

    switch (paymentIntent.status) {
        case 'succeeded':
            return {
                success: true,
                status: 'succeeded',
                message: 'Payment succeeded!',
                paymentIntent,
            };

        case 'processing':
            return {
                success: true,
                status: 'processing',
                message: 'Your payment is processing.',
                paymentIntent,
            };

        case 'requires_payment_method':
            return {
                success: false,
                status: 'requires_payment_method',
                message: 'Your payment was not successful, please try again.',
                paymentIntent,
            };

        default:
            return {
                success: false,
                status: paymentIntent.status,
                message: 'Something went wrong.',
                paymentIntent,
            };
    }
}

/**
 * Create payment intent on the server
 * @param {number} orderId - The order ID
 * @param {number} amount - The payment amount
 * @returns {Promise<object>} Payment intent details
 */
export async function createPaymentIntent(orderId, amount) {
    try {
        const response = await fetch('/api/payments/stripe/create-intent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                order_id: orderId,
                amount: amount,
            }),
        });

        if (!response.ok) {
            throw new Error('Failed to create payment intent');
        }

        return await response.json();
    } catch (error) {
        console.error('Error creating payment intent:', error);
        throw error;
    }
}

/**
 * Display error message
 * @param {string} message - Error message to display
 */
function displayError(message) {
    const errorElement = document.querySelector('#error-message');
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = message ? 'block' : 'none';
    }
}

/**
 * Set loading state
 * @param {boolean} isLoading - Loading state
 */
function setLoading(isLoading) {
    const submitButton = document.querySelector('#submit-payment');
    const spinner = document.querySelector('#spinner');
    const buttonText = document.querySelector('#button-text');

    if (submitButton) {
        submitButton.disabled = isLoading;
    }

    if (spinner) {
        spinner.classList.toggle('hidden', !isLoading);
    }

    if (buttonText) {
        buttonText.classList.toggle('hidden', isLoading);
    }
}

/**
 * Get user-friendly error message
 * @param {object} error - Stripe error object
 * @returns {string} User-friendly error message
 */
function getErrorMessage(error) {
    const errorMessages = {
        card_declined: 'Your card was declined. Please try another payment method.',
        expired_card: 'Your card has expired. Please use a different card.',
        incorrect_cvc: 'The security code is incorrect. Please check and try again.',
        processing_error: 'An error occurred while processing your card. Please try again.',
        incorrect_number: 'The card number is incorrect. Please check and try again.',
        insufficient_funds: 'Your card has insufficient funds. Please use a different payment method.',
        invalid_expiry_month: 'The expiration month is invalid.',
        invalid_expiry_year: 'The expiration year is invalid.',
    };

    return errorMessages[error.code] || error.message || 'An unexpected error occurred. Please try again.';
}

/**
 * Initialize Stripe on page load if payment form exists
 */
document.addEventListener('DOMContentLoaded', () => {
    const paymentForm = document.querySelector('#payment-form');

    if (paymentForm) {
        // Get client secret from data attribute or create new payment intent
        const clientSecret = paymentForm.dataset.clientSecret;

        if (clientSecret) {
            initializeStripeElements(clientSecret);
        }

        // Handle form submission
        paymentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const returnUrl = paymentForm.dataset.returnUrl || window.location.origin + '/payment/success';
            await handlePaymentSubmit(paymentForm, returnUrl);
        });
    }

    // Check for payment return
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('payment_intent_client_secret')) {
        handlePaymentReturn().then((result) => {
            if (result) {
                // Display result to user
                const messageElement = document.querySelector('#payment-message');
                if (messageElement) {
                    messageElement.textContent = result.message;
                    messageElement.className = result.success ? 'success' : 'error';
                }
            }
        });
    }
});

// Export for use in other modules
export default {
    initializeStripeElements,
    handlePaymentSubmit,
    handlePaymentReturn,
    createPaymentIntent,
};
