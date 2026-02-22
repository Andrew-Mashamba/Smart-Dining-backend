<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Guest;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\Table;
use App\Models\WhatsAppSession;
use App\Services\WhatsApp\ConversationManager;
use App\Services\WhatsApp\FlowManager;
use App\Services\WhatsApp\MenuBot;
use App\Services\WhatsApp\PaymentBot;
use App\Services\WhatsApp\ReservationBot;
use App\Services\Payment\PaymentService;
use App\Services\OrderManagement\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotFlowTest extends TestCase
{
    use RefreshDatabase;

    protected ConversationManager $cm;
    protected string $phone = '+255700111222';

    protected function setUp(): void
    {
        parent::setUp();

        $this->cm = app(ConversationManager::class);

        // Seed base data
        Table::create([
            'name' => 'Table 1',
            'location' => 'indoor',
            'capacity' => 4,
            'status' => 'available',
        ]);

        $cat = MenuCategory::create([
            'name' => 'Main Course',
            'description' => 'Main dishes',
            'display_order' => 1,
            'status' => 'active',
        ]);

        MenuItem::create([
            'category_id' => $cat->id,
            'name' => 'Grilled Fish',
            'description' => 'Fresh grilled fish',
            'price' => 25000,
            'status' => 'available',
            'prep_area' => 'kitchen',
            'prep_time_minutes' => 20,
        ]);

        MenuItem::create([
            'category_id' => $cat->id,
            'name' => 'Chicken Wings',
            'description' => 'Crispy wings',
            'price' => 15000,
            'status' => 'available',
            'prep_area' => 'kitchen',
            'prep_time_minutes' => 15,
        ]);
    }

    public function test_whatsapp_session_table_name(): void
    {
        $session = new WhatsAppSession;
        $this->assertEquals('whatsapp_sessions', $session->getTable());
    }

    public function test_new_session_starts_at_main_menu(): void
    {
        $session = $this->cm->getSession($this->phone);

        $this->assertEquals('MAIN_MENU', $session->state);
        $this->assertNotNull($session->last_activity_at);
    }

    public function test_state_transitions_correctly(): void
    {
        $this->cm->getSession($this->phone);

        $this->cm->setState($this->phone, 'RESERVATION_GUESTS', ['flow' => 'reservation']);
        $this->assertEquals('RESERVATION_GUESTS', $this->cm->getState($this->phone));

        $this->cm->setState($this->phone, 'RESERVATION_DATE', ['guests' => 4]);
        $this->assertEquals('RESERVATION_DATE', $this->cm->getState($this->phone));
    }

    public function test_session_data_merges_correctly(): void
    {
        $this->cm->getSession($this->phone);

        $this->cm->setState($this->phone, 'RESERVATION_GUESTS', ['flow' => 'reservation']);
        $this->cm->setState($this->phone, 'RESERVATION_DATE', ['guests' => 4]);

        $data = $this->cm->getSessionData($this->phone);
        $this->assertEquals('reservation', $data['flow']);
        $this->assertEquals(4, $data['guests']);
    }

    public function test_update_session_data_key(): void
    {
        $this->cm->getSession($this->phone);
        $this->cm->updateSessionData($this->phone, 'selected_item', 42);

        $data = $this->cm->getSessionData($this->phone);
        $this->assertEquals(42, $data['selected_item']);
    }

    public function test_clear_session_preserves_table_by_default(): void
    {
        $table = Table::first();
        $this->cm->getSession($this->phone);
        $this->cm->setCurrentTable($this->phone, $table->id);
        $this->cm->setState($this->phone, 'ORDER_ITEMS');

        $this->cm->clearSession($this->phone, false);

        $session = $this->cm->getSession($this->phone);
        $this->assertEquals('MAIN_MENU', $session->state);
        $this->assertEquals($table->id, $session->current_table_id);
        $this->assertNull($session->current_order_id);
    }

    public function test_clear_session_with_table_clears_everything(): void
    {
        $table = Table::first();
        $this->cm->getSession($this->phone);
        $this->cm->setCurrentTable($this->phone, $table->id);

        $this->cm->clearSession($this->phone, true);

        $session = $this->cm->getSession($this->phone);
        $this->assertNull($session->current_table_id);
    }

    public function test_expired_session_auto_resets(): void
    {
        $session = $this->cm->getSession($this->phone);
        $this->cm->setState($this->phone, 'ORDER_CART');

        // Manually expire
        $session->update(['last_activity_at' => now()->subHours(25)]);

        // getSession should auto-reset
        $session = $this->cm->getSession($this->phone);
        $this->assertEquals('MAIN_MENU', $session->state);
        $this->assertNull($session->data);
    }

    public function test_link_guest_to_session(): void
    {
        $guest = Guest::create([
            'phone_number' => $this->phone,
            'name' => 'Test Guest',
            'first_visit_at' => now(),
            'last_visit_at' => now(),
        ]);

        $this->cm->getSession($this->phone);
        $this->cm->linkGuest($this->phone, $guest->id);

        $session = $this->cm->getSession($this->phone);
        $this->assertEquals($guest->id, $session->guest_id);
    }

    public function test_reservation_model_generates_reference(): void
    {
        $guest = Guest::create([
            'phone_number' => $this->phone,
            'name' => 'Test',
            'first_visit_at' => now(),
            'last_visit_at' => now(),
        ]);

        $reservation = Reservation::create([
            'guest_id' => $guest->id,
            'reservation_date' => '2026-03-15',
            'reservation_time' => '19:30',
            'party_size' => 4,
            'location' => 'indoor',
            'status' => 'pending',
            'source' => 'whatsapp',
        ]);

        $this->assertNotNull($reservation->reference_number);
        $this->assertStringStartsWith('RES-20260315-', $reservation->reference_number);
    }

    public function test_reservation_upcoming_scope(): void
    {
        $guest = Guest::create([
            'phone_number' => $this->phone,
            'name' => 'Test',
            'first_visit_at' => now(),
            'last_visit_at' => now(),
        ]);

        Reservation::create([
            'guest_id' => $guest->id,
            'reservation_date' => now()->addDays(5)->format('Y-m-d'),
            'reservation_time' => '19:00',
            'party_size' => 2,
            'location' => 'indoor',
            'status' => 'confirmed',
            'source' => 'whatsapp',
        ]);

        $this->assertEquals(1, Reservation::upcoming()->count());
    }

    public function test_payment_model_has_token_fields(): void
    {
        $this->assertContains('token', (new \App\Models\Payment)->getFillable());
        $this->assertContains('token_expires_at', (new \App\Models\Payment)->getFillable());
        $this->assertContains('completed_at', (new \App\Models\Payment)->getFillable());
        $this->assertContains('gateway_response', (new \App\Models\Payment)->getFillable());
    }

    public function test_payment_model_casts_correctly(): void
    {
        $payment = new \App\Models\Payment;
        $payment->gateway_response = ['test' => 'value'];
        $payment->token_expires_at = '2026-03-01 12:00:00';
        $payment->completed_at = '2026-03-01 13:00:00';

        $this->assertIsArray($payment->gateway_response);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $payment->token_expires_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $payment->completed_at);
    }

    public function test_payment_service_cash_uses_gateway_response(): void
    {
        $guest = Guest::create([
            'phone_number' => $this->phone,
            'name' => 'Test',
            'first_visit_at' => now(),
            'last_visit_at' => now(),
        ]);

        $table = Table::first();
        $menuItem = MenuItem::first();

        $order = Order::create([
            'order_number' => 'ORD-TEST-001',
            'guest_id' => $guest->id,
            'table_id' => $table->id,
            'order_source' => 'whatsapp',
            'status' => 'served',
            'subtotal' => 25000,
            'tax' => 4500,
            'total' => 29500,
        ]);

        $ps = app(PaymentService::class);
        $payment = $ps->processPayment($order, [
            'amount' => 29500,
            'payment_method' => 'cash',
            'tendered' => 30000,
        ]);

        $this->assertEquals('completed', $payment->status);
        $this->assertNotNull($payment->completed_at);
        $this->assertIsArray($payment->gateway_response);
        $this->assertEquals(30000, $payment->gateway_response['tendered']);
        $this->assertEquals(500, $payment->gateway_response['change']);
    }

    public function test_payment_service_mobile_uses_gateway_response(): void
    {
        $guest = Guest::create([
            'phone_number' => $this->phone,
            'name' => 'Test',
            'first_visit_at' => now(),
            'last_visit_at' => now(),
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-002',
            'guest_id' => $guest->id,
            'table_id' => Table::first()->id,
            'order_source' => 'whatsapp',
            'status' => 'served',
            'subtotal' => 25000,
            'tax' => 4500,
            'total' => 29500,
        ]);

        $ps = app(PaymentService::class);
        $payment = $ps->processPayment($order, [
            'amount' => 29500,
            'payment_method' => 'mobile',
            'phone_number' => '+255712345678',
            'provider' => 'mpesa',
        ]);

        $this->assertEquals('pending', $payment->status);
        $this->assertIsArray($payment->gateway_response);
        $this->assertEquals('+255712345678', $payment->gateway_response['phone_number']);
        $this->assertEquals('mpesa', $payment->gateway_response['provider']);
    }

    public function test_confirm_payment_sets_completed_at(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST-003',
            'table_id' => Table::first()->id,
            'order_source' => 'pos',
            'status' => 'served',
            'subtotal' => 10000,
            'tax' => 1800,
            'total' => 11800,
        ]);

        $payment = \App\Models\Payment::create([
            'order_id' => $order->id,
            'amount' => 11800,
            'payment_method' => 'mobile',
            'status' => 'pending',
            'transaction_id' => 'TXN-TEST',
        ]);

        $ps = app(PaymentService::class);
        $ps->confirmPayment($payment);

        $payment->refresh();
        $this->assertEquals('completed', $payment->status);
        $this->assertNotNull($payment->completed_at);
    }

    public function test_refund_payment_uses_gateway_response(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST-004',
            'table_id' => Table::first()->id,
            'order_source' => 'pos',
            'status' => 'served',
            'subtotal' => 10000,
            'tax' => 1800,
            'total' => 11800,
        ]);

        $payment = \App\Models\Payment::create([
            'order_id' => $order->id,
            'amount' => 11800,
            'payment_method' => 'cash',
            'status' => 'completed',
            'transaction_id' => 'TXN-TEST-REF',
            'completed_at' => now(),
        ]);

        $ps = app(PaymentService::class);
        $ps->refundPayment($payment, 'Customer request');

        $payment->refresh();
        $this->assertEquals('refunded', $payment->status);
        $this->assertEquals('Customer request', $payment->gateway_response['refund_reason']);
    }

    public function test_order_summary_uses_correct_field_names(): void
    {
        $table = Table::first();
        $menuItem = MenuItem::first();

        $order = Order::create([
            'order_number' => 'ORD-TEST-005',
            'table_id' => $table->id,
            'order_source' => 'pos',
            'status' => 'pending',
            'subtotal' => 25000,
            'tax' => 4500,
            'total' => 29500,
        ]);

        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'unit_price' => 25000,
            'subtotal' => 25000,
            'prep_status' => 'pending',
        ]);

        $os = app(OrderService::class);
        $summary = $os->getOrderSummary($order);

        // Verify correct keys exist
        $this->assertArrayHasKey('total', $summary['totals']);
        $this->assertArrayNotHasKey('total_amount', $summary['totals']);
        $this->assertArrayNotHasKey('service_charge', $summary['totals']);

        // Verify items use prep_status
        $firstItem = $summary['items'][0];
        $this->assertArrayHasKey('prep_status', $firstItem);
        $this->assertArrayNotHasKey('status', $firstItem);
        $this->assertEquals('pending', $firstItem['prep_status']);
    }

    public function test_all_chatbot_states_are_defined(): void
    {
        $expectedStates = [
            'STATE_MAIN_MENU',
            'STATE_RESERVATION_GUESTS',
            'STATE_RESERVATION_DATE',
            'STATE_RESERVATION_TIME',
            'STATE_RESERVATION_LOCATION',
            'STATE_RESERVATION_NAME',
            'STATE_RESERVATION_CONFIRM',
            'STATE_ORDER_TABLE',
            'STATE_ORDER_CATEGORY',
            'STATE_ORDER_ITEMS',
            'STATE_ORDER_QUANTITY',
            'STATE_ORDER_SPECIAL',
            'STATE_ORDER_CART',
            'STATE_ORDER_CONFIRM',
            'STATE_CURRENT_ORDER',
            'STATE_NOTIFICATION_TABLE',
            'STATE_PAYMENT_METHOD',
            'STATE_PAYMENT_MPESA',
            'STATE_PAYMENT_PROCESSING',
        ];

        foreach ($expectedStates as $const) {
            $this->assertTrue(
                defined(ConversationManager::class . '::' . $const),
                "Missing constant: ConversationManager::{$const}"
            );
        }
    }

    public function test_events_broadcast_safely_with_null_waiter(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST-006',
            'table_id' => Table::first()->id,
            'waiter_id' => null,
            'order_source' => 'whatsapp',
            'status' => 'pending',
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
        ]);

        // OrderCreated should not throw
        $event = new \App\Events\OrderCreated($order);
        $channels = $event->broadcastOn();
        $this->assertCount(3, $channels); // orders, kitchen, bar — no waiter channel

        // broadcastWith should handle null table gracefully
        $data = $event->broadcastWith();
        $this->assertArrayHasKey('order_id', $data);
    }

    public function test_webhook_endpoint_returns_200(): void
    {
        // Set a known webhook secret for testing
        config(['whatsapp.webhook_secret' => 'test_secret']);

        // Verify routes are registered (non-HTTP assertion)
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('whatsapp.verify'),
            'WhatsApp verify route should be registered'
        );
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('whatsapp.webhook'),
            'WhatsApp webhook route should be registered'
        );

        // Test the controller directly
        $controller = app(\App\Http\Controllers\WhatsApp\WebhookController::class);
        $request = \Illuminate\Http\Request::create('/webhooks/whatsapp', 'GET', [
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'test_secret',
            'hub_challenge' => 'test_challenge_123',
        ]);
        $response = $controller->verify($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('test_challenge_123', $response->getContent());
    }

    public function test_service_resolution_via_di(): void
    {
        $services = [
            FlowManager::class,
            ConversationManager::class,
            MenuBot::class,
            ReservationBot::class,
            PaymentBot::class,
            PaymentService::class,
            OrderService::class,
        ];

        foreach ($services as $service) {
            $instance = app($service);
            $this->assertInstanceOf($service, $instance, "Failed to resolve {$service}");
        }
    }

    public function test_generate_bill_handles_null_relationships(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST-007',
            'table_id' => null,
            'guest_id' => null,
            'waiter_id' => null,
            'order_source' => 'whatsapp',
            'status' => 'pending',
            'subtotal' => 25000,
            'tax' => 4500,
            'total' => 29500,
        ]);

        $ps = app(PaymentService::class);
        $bill = $ps->generateBill($order);

        $this->assertNull($bill['guest']['name']);
        $this->assertNull($bill['table']);
        $this->assertNull($bill['waiter']);
        $this->assertEquals(29500, $bill['breakdown']['total']);
    }
}
