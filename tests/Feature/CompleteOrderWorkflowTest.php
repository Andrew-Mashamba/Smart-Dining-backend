<?php

namespace Tests\Feature;

use App\Events\OrderStatusChanged;
use App\Models\GuestSession;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CompleteOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $waiter;

    private User $chef;

    private User $bartender;

    private Table $table;

    private MenuItem $foodItem;

    private MenuItem $drinkItem;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with all roles
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
        ]);

        $this->manager = User::factory()->create([
            'role' => 'manager',
            'email' => 'manager@test.com',
        ]);

        $this->waiter = User::factory()->create([
            'role' => 'waiter',
            'email' => 'waiter@test.com',
        ]);

        $this->chef = User::factory()->create([
            'role' => 'chef',
            'email' => 'chef@test.com',
        ]);

        $this->bartender = User::factory()->create([
            'role' => 'bartender',
            'email' => 'bartender@test.com',
        ]);

        // Create table
        $this->table = Table::factory()->create([
            'name' => 'Table 5',
            'status' => 'available',
            'capacity' => 4,
        ]);

        // Create menu categories
        $foodCategory = MenuCategory::create([
            'name' => 'Main Courses',
            'display_order' => 1,
        ]);

        $drinkCategory = MenuCategory::create([
            'name' => 'Beverages',
            'display_order' => 2,
        ]);

        // Create menu items
        $this->foodItem = MenuItem::factory()->create([
            'name' => 'Grilled Chicken',
            'category_id' => $foodCategory->id,
            'price' => 150.00,
            'prep_area' => 'kitchen',
            'status' => 'available',
            'stock_quantity' => 10,
        ]);

        $this->drinkItem = MenuItem::factory()->create([
            'name' => 'Mojito',
            'category_id' => $drinkCategory->id,
            'price' => 80.00,
            'prep_area' => 'bar',
            'status' => 'available',
            'stock_quantity' => 20,
        ]);
    }

    /** @test */
    public function complete_order_workflow_from_creation_to_payment_as_waiter()
    {
        Event::fake([OrderStatusChanged::class]);

        // Step 1: Waiter creates a guest session
        $sessionResponse = $this->actingAs($this->waiter, 'sanctum')
            ->postJson('/api/tables/'.$this->table->id.'/sessions', [
                'guest_name' => 'John Doe',
                'guest_count' => 2,
            ]);

        $sessionResponse->assertStatus(201);
        $guestSession = GuestSession::find($sessionResponse->json('session.id'));
        $this->assertNotNull($guestSession);

        // Step 2: Waiter creates an order for the table
        $orderResponse = $this->actingAs($this->waiter, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $this->table->id,
                'guest_session_id' => $guestSession->id,
                'items' => [
                    [
                        'menu_item_id' => $this->foodItem->id,
                        'quantity' => 2,
                        'special_instructions' => 'No salt',
                    ],
                    [
                        'menu_item_id' => $this->drinkItem->id,
                        'quantity' => 2,
                        'special_instructions' => 'Extra ice',
                    ],
                ],
            ]);

        $orderResponse->assertStatus(201);
        $order = Order::find($orderResponse->json('order.id'));
        $this->assertNotNull($order);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals(2, $order->orderItems()->count());

        // Verify table is now occupied
        $this->table->refresh();
        $this->assertEquals('occupied', $this->table->status);

        // Verify stock deduction
        $this->foodItem->refresh();
        $this->assertEquals(8, $this->foodItem->stock_quantity);
        $this->drinkItem->refresh();
        $this->assertEquals(18, $this->drinkItem->stock_quantity);

        // Step 3: Chef marks kitchen items as preparing
        $kitchenItem = $order->orderItems()->where('menu_item_id', $this->foodItem->id)->first();
        $chefUpdateResponse = $this->actingAs($this->chef, 'sanctum')
            ->postJson("/api/order-items/{$kitchenItem->id}/preparing");

        $chefUpdateResponse->assertStatus(200);
        $kitchenItem->refresh();
        $this->assertEquals('preparing', $kitchenItem->prep_status);

        // Step 4: Bartender marks bar items as preparing
        $barItem = $order->orderItems()->where('menu_item_id', $this->drinkItem->id)->first();
        $bartenderUpdateResponse = $this->actingAs($this->bartender, 'sanctum')
            ->postJson("/api/order-items/{$barItem->id}/preparing");

        $bartenderUpdateResponse->assertStatus(200);
        $barItem->refresh();
        $this->assertEquals('preparing', $barItem->prep_status);

        // Step 5: Chef marks kitchen items as ready
        $chefReadyResponse = $this->actingAs($this->chef, 'sanctum')
            ->postJson("/api/order-items/{$kitchenItem->id}/ready");

        $chefReadyResponse->assertStatus(200);
        $kitchenItem->refresh();
        $this->assertEquals('ready', $kitchenItem->prep_status);

        // Step 6: Bartender marks bar items as ready
        $bartenderReadyResponse = $this->actingAs($this->bartender, 'sanctum')
            ->postJson("/api/order-items/{$barItem->id}/ready");

        $bartenderReadyResponse->assertStatus(200);
        $barItem->refresh();
        $this->assertEquals('ready', $barItem->prep_status);

        // Step 7: Waiter marks order as served
        $servedResponse = $this->actingAs($this->waiter, 'sanctum')
            ->patchJson("/api/orders/{$order->id}/status", [
                'status' => 'served',
            ]);

        $servedResponse->assertStatus(200);
        $order->refresh();
        $this->assertEquals('served', $order->status);

        // Step 8: Waiter processes payment
        $totalAmount = ($this->foodItem->price * 2) + ($this->drinkItem->price * 2);
        $paymentResponse = $this->actingAs($this->waiter, 'sanctum')
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'amount' => $totalAmount,
                'payment_method' => 'cash',
                'tip_amount' => 50.00,
            ]);

        $paymentResponse->assertStatus(201);
        $payment = Payment::find($paymentResponse->json('payment.id'));
        $this->assertNotNull($payment);
        $this->assertEquals($totalAmount, $payment->amount);
        $this->assertEquals('completed', $payment->status);

        // Verify order is marked as paid
        $order->refresh();
        $this->assertEquals('paid', $order->status);

        // Verify table is available again
        $this->table->refresh();
        $this->assertEquals('available', $this->table->status);

        // Verify guest session is closed
        $guestSession->refresh();
        $this->assertEquals('closed', $guestSession->status);

        // Verify events were dispatched
        Event::assertDispatched(OrderStatusChanged::class);
    }

    /** @test */
    public function complete_order_workflow_from_creation_to_payment_as_manager()
    {
        // Manager should be able to perform all operations
        Event::fake([OrderStatusChanged::class]);

        // Create guest session
        $sessionResponse = $this->actingAs($this->manager, 'sanctum')
            ->postJson('/api/tables/'.$this->table->id.'/sessions', [
                'guest_name' => 'Jane Smith',
                'guest_count' => 4,
            ]);

        $sessionResponse->assertStatus(201);
        $guestSession = GuestSession::find($sessionResponse->json('session.id'));

        // Create order
        $orderResponse = $this->actingAs($this->manager, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $this->table->id,
                'guest_session_id' => $guestSession->id,
                'items' => [
                    [
                        'menu_item_id' => $this->foodItem->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $orderResponse->assertStatus(201);
        $order = Order::find($orderResponse->json('order.id'));

        // Manager processes through all statuses
        $this->actingAs($this->manager, 'sanctum')
            ->patchJson("/api/orders/{$order->id}/status", [
                'status' => 'preparing',
            ])->assertStatus(200);

        $this->actingAs($this->manager, 'sanctum')
            ->patchJson("/api/orders/{$order->id}/status", [
                'status' => 'ready',
            ])->assertStatus(200);

        $this->actingAs($this->manager, 'sanctum')
            ->patchJson("/api/orders/{$order->id}/status", [
                'status' => 'served',
            ])->assertStatus(200);

        // Process payment
        $paymentResponse = $this->actingAs($this->manager, 'sanctum')
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'amount' => $this->foodItem->price,
                'payment_method' => 'card',
            ]);

        $paymentResponse->assertStatus(201);
        $order->refresh();
        $this->assertEquals('paid', $order->status);
    }

    /** @test */
    public function order_workflow_handles_order_cancellation()
    {
        // Create order
        $guestSession = GuestSession::factory()->create([
            'table_id' => $this->table->id,
            'status' => 'active',
        ]);

        $order = Order::factory()->create([
            'table_id' => $this->table->id,
            'guest_session_id' => $guestSession->id,
            'waiter_id' => $this->waiter->id,
            'status' => 'pending',
        ]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $this->foodItem->id,
            'quantity' => 2,
            'price' => $this->foodItem->price,
        ]);

        // Manager cancels the order
        $response = $this->actingAs($this->manager, 'sanctum')
            ->patchJson("/api/orders/{$order->id}/status", [
                'status' => 'cancelled',
                'cancellation_reason' => 'Customer changed mind',
            ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('cancelled', $order->status);

        // Verify stock is restored
        $this->foodItem->refresh();
        $this->assertEquals(10, $this->foodItem->stock_quantity);
    }

    /** @test */
    public function order_workflow_prevents_invalid_status_transitions()
    {
        $guestSession = GuestSession::factory()->create([
            'table_id' => $this->table->id,
            'status' => 'active',
        ]);

        $order = Order::factory()->create([
            'table_id' => $this->table->id,
            'guest_session_id' => $guestSession->id,
            'waiter_id' => $this->waiter->id,
            'status' => 'served',
        ]);

        // Try to move from 'served' back to 'pending' (invalid transition)
        $response = $this->actingAs($this->manager, 'sanctum')
            ->patchJson("/api/orders/{$order->id}/status", [
                'status' => 'pending',
            ]);

        $response->assertStatus(422);
        $order->refresh();
        $this->assertEquals('served', $order->status);
    }

    /** @test */
    public function order_workflow_tracks_order_times()
    {
        $guestSession = GuestSession::factory()->create([
            'table_id' => $this->table->id,
            'status' => 'active',
        ]);

        $order = Order::factory()->create([
            'table_id' => $this->table->id,
            'guest_session_id' => $guestSession->id,
            'waiter_id' => $this->waiter->id,
            'status' => 'pending',
        ]);

        $this->assertNotNull($order->created_at);
        $this->assertNull($order->served_at);
        $this->assertNull($order->paid_at);

        // Mark as served
        $this->actingAs($this->manager, 'sanctum')
            ->patchJson("/api/orders/{$order->id}/status", [
                'status' => 'served',
            ]);

        $order->refresh();
        $this->assertNotNull($order->served_at);
        $this->assertNull($order->paid_at);

        // Process payment
        $this->actingAs($this->manager, 'sanctum')
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'amount' => 100.00,
                'payment_method' => 'cash',
            ]);

        $order->refresh();
        $this->assertNotNull($order->paid_at);
    }

    /** @test */
    public function order_workflow_calculates_total_correctly()
    {
        $guestSession = GuestSession::factory()->create([
            'table_id' => $this->table->id,
            'status' => 'active',
        ]);

        $orderResponse = $this->actingAs($this->waiter, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $this->table->id,
                'guest_session_id' => $guestSession->id,
                'items' => [
                    [
                        'menu_item_id' => $this->foodItem->id,
                        'quantity' => 3,
                    ],
                    [
                        'menu_item_id' => $this->drinkItem->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $orderResponse->assertStatus(201);
        $order = Order::find($orderResponse->json('order.id'));

        $expectedTotal = ($this->foodItem->price * 3) + ($this->drinkItem->price * 2);
        $this->assertEquals($expectedTotal, $order->total_amount);
    }
}
