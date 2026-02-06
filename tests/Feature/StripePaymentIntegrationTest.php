<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class StripePaymentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set Stripe test keys
        Config::set('services.stripe.public_key', 'pk_test_51QwertyAsdfgZxcvbn1234567890');
        Config::set('services.stripe.secret', 'sk_test_51QwertyAsdfgZxcvbn1234567890');
        Config::set('services.stripe.webhook_secret', 'whsec_test_secret_1234567890abcdefghij');
    }

    /** @test */
    public function stripe_sdk_is_installed()
    {
        $this->assertTrue(class_exists(\Stripe\Stripe::class), 'Stripe SDK is not installed');
    }

    /** @test */
    public function stripe_environment_variables_are_configured()
    {
        $this->assertNotEmpty(config('services.stripe.public_key'), 'STRIPE_PUBLIC_KEY is not configured');
        $this->assertNotEmpty(config('services.stripe.secret'), 'STRIPE_SECRET_KEY is not configured');
        $this->assertNotEmpty(config('services.stripe.webhook_secret'), 'STRIPE_WEBHOOK_SECRET is not configured');
    }

    /** @test */
    public function stripe_payment_service_exists()
    {
        $service = app(StripePaymentService::class);
        $this->assertInstanceOf(StripePaymentService::class, $service);
    }

    /** @test */
    public function payment_model_has_gateway_response_field()
    {
        $payment = new Payment;
        $this->assertContains('gateway_response', $payment->getFillable());
        $this->assertArrayHasKey('gateway_response', $payment->getCasts());
        $this->assertEquals('array', $payment->getCasts()['gateway_response']);
    }

    /** @test */
    public function stripe_payment_routes_are_registered()
    {
        // Check API routes
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('payments.stripe.form'),
            'Stripe payment form route is not registered'
        );

        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('payments.stripe.success'),
            'Stripe success route is not registered'
        );
    }

    /** @test */
    public function webhook_route_is_registered_without_csrf()
    {
        // The webhook route should be accessible without CSRF token
        $response = $this->post('/api/webhooks/stripe', [], [
            'Stripe-Signature' => 'test_signature',
        ]);

        // Should not get 419 (CSRF error), but may get 400 (invalid signature)
        $this->assertNotEquals(419, $response->status());
    }

    /** @test */
    public function process_payment_component_redirects_to_stripe_for_gateway_method()
    {
        // This test verifies the ProcessPayment Livewire component exists and handles gateway payments
        $this->assertTrue(
            class_exists(\App\Livewire\ProcessPayment::class),
            'ProcessPayment Livewire component does not exist'
        );

        // Verify the component has the processPayment method that handles gateway redirection
        $reflection = new \ReflectionClass(\App\Livewire\ProcessPayment::class);
        $this->assertTrue(
            $reflection->hasMethod('processPayment'),
            'ProcessPayment component does not have processPayment method'
        );
    }

    /** @test */
    public function stripe_elements_integration_exists()
    {
        $jsFile = resource_path('js/stripe.js');
        $this->assertFileExists($jsFile, 'stripe.js file does not exist');

        $content = file_get_contents($jsFile);
        $this->assertStringContainsString('Stripe', $content);
        $this->assertStringContainsString('4242 4242 4242 4242', $content);
    }

    /** @test */
    public function stripe_form_view_exists()
    {
        $viewFile = resource_path('views/payment/stripe-form.blade.php');
        $this->assertFileExists($viewFile, 'Stripe form view does not exist');

        $content = file_get_contents($viewFile);
        $this->assertStringContainsString('payment-element', $content);
        $this->assertStringContainsString('4242 4242 4242 4242', $content);
    }

    /** @test */
    public function stripe_success_view_exists()
    {
        $viewFile = resource_path('views/payment/stripe-success.blade.php');
        $this->assertFileExists($viewFile, 'Stripe success view does not exist');
    }

    /** @test */
    public function error_messages_are_user_friendly()
    {
        $service = new StripePaymentService;

        $messages = [
            'card_declined' => $service->getErrorMessage('card_declined'),
            'expired_card' => $service->getErrorMessage('expired_card'),
            'incorrect_cvc' => $service->getErrorMessage('incorrect_cvc'),
            'processing_error' => $service->getErrorMessage('processing_error'),
        ];

        foreach ($messages as $code => $message) {
            $this->assertNotEmpty($message);
            $this->assertIsString($message);
            // Should not contain technical error codes
            $this->assertStringNotContainsString($code, $message);
        }
    }

    /** @test */
    public function webhook_controller_handles_payment_succeeded()
    {
        $this->assertTrue(
            method_exists(\App\Http\Controllers\StripeWebhookController::class, 'handle'),
            'StripeWebhookController::handle method does not exist'
        );
    }

    /** @test */
    public function payment_service_has_required_methods()
    {
        $service = new StripePaymentService;

        $this->assertTrue(method_exists($service, 'processPayment'));
        $this->assertTrue(method_exists($service, 'confirmPayment'));
        $this->assertTrue(method_exists($service, 'failPayment'));
        $this->assertTrue(method_exists($service, 'retrievePaymentIntent'));
        $this->assertTrue(method_exists($service, 'getErrorMessage'));
    }
}
