<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Production Readiness Test Suite
 *
 * Comprehensive test coverage for Story 52:
 * - Complete order workflow across all roles
 * - API endpoint testing
 * - Real-time update verification
 * - Payment processing
 * - Error handling and edge cases
 */
class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected Staff $waiter;

    protected Staff $chef;

    protected Staff $bartender;

    protected Guest $guest;

    protected Table $table;

    protected MenuItem $foodItem;

    protected MenuItem $drinkItem;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with specific roles
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => 'admin',
        ]);

        $this->manager = User::factory()->create([
            'email' => 'manager@test.com',
            'role' => 'manager',
        ]);

        $this->waiter = Staff::factory()->create([
            'email' => 'waiter@test.com',
            'role' => 'waiter',
        ]);

        $this->chef = Staff::factory()->create([
            'email' => 'chef@test.com',
            'role' => 'chef',
        ]);

        $this->bartender = Staff::factory()->create([
            'email' => 'bartender@test.com',
            'role' => 'bartender',
        ]);

        // Create test data
        $this->guest = Guest::factory()->create();
        $this->table = Table::factory()->create(['status' => 'available']);

        $foodCategory = MenuCategory::factory()->create(['name' => 'Main Course']);
        $drinkCategory = MenuCategory::factory()->create(['name' => 'Beverages']);

        $this->foodItem = MenuItem::factory()->create([
            'category_id' => $foodCategory->id,
            'name' => 'Grilled Chicken',
            'price' => 25.00,
            'prep_area' => 'kitchen',
            'stock_quantity' => 50,
        ]);

        $this->drinkItem = MenuItem::factory()->create([
            'category_id' => $drinkCategory->id,
            'name' => 'Mojito',
            'price' => 10.00,
            'prep_area' => 'bar',
            'stock_quantity' => 30,
        ]);
    }

    /**
     * Test 1: Complete Order Workflow - Waiter Role
     * Tests full order lifecycle from creation to payment
     */
    public function test_complete_order_workflow_as_waiter(): void
    {
        // Step 1: Waiter creates order
        $response = $this->actingAs($this->waiter, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $this->table->id,
                'guest_id' => $this->guest->id,
                'waiter_id' => $this->waiter->id,
                'items' => [
                    [
                        'menu_item_id' => $this->foodItem->id,
                        'quantity' => 2,
                        'special_instructions' => 'No onions',
                    ],
                    [
                        'menu_item_id' => $this->drinkItem->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'order' => [
                    'id',
                    'order_number',
                    'status',
                    'total',
                    'subtotal',
                    'tax',
                ],
            ]);

        $orderId = $response->json('order.id');
        $order = Order::find($orderId);

        // Verify order was created correctly
        $this->assertNotNull($order);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals(2, $order->orderItems()->count());
        $this->assertEquals(60.00, $order->subtotal); // (25 * 2) + (10 * 1)

        // Step 2: Chef marks kitchen item as received
        $kitchenItem = $order->orderItems()->where('prep_area', 'kitchen')->first();

        $response = $this->actingAs($this->chef, 'sanctum')
            ->postJson("/api/order-items/{$kitchenItem->id}/received");

        $response->assertStatus(200);
        $this->assertEquals('received', $kitchenItem->fresh()->prep_status);

        // Step 3: Chef marks kitchen item as done
        $response = $this->actingAs($this->chef, 'sanctum')
            ->postJson("/api/order-items/{$kitchenItem->id}/done");

        $response->assertStatus(200);
        $this->assertEquals('done', $kitchenItem->fresh()->prep_status);

        // Step 4: Bartender processes drink
        $barItem = $order->orderItems()->where('prep_area', 'bar')->first();

        $this->actingAs($this->bartender, 'sanctum')
            ->postJson("/api/order-items/{$barItem->id}/received");

        $this->actingAs($this->bartender, 'sanctum')
            ->postJson("/api/order-items/{$barItem->id}/done");

        $this->assertEquals('done', $barItem->fresh()->prep_status);

        // Step 5: Waiter marks order as served
        $response = $this->actingAs($this->waiter, 'sanctum')
            ->postJson("/api/orders/{$orderId}/serve");

        $response->assertStatus(200);
        $this->assertEquals('served', $order->fresh()->status);

        // Step 6: Process payment
        $response = $this->actingAs($this->waiter, 'sanctum')
            ->postJson('/api/payments', [
                'order_id' => $orderId,
                'amount' => $order->fresh()->total,
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'payment' => [
                    'id',
                    'order_id',
                    'amount',
                    'payment_method',
                    'status',
                ],
            ]);

        // Verify payment was recorded
        $payment = Payment::where('order_id', $orderId)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('completed', $payment->status);

        // Verify order status is paid
        $this->assertEquals('paid', $order->fresh()->status);
    }

    /**
     * Test 2: Menu API Endpoints (Public Access)
     */
    public function test_public_menu_endpoints(): void
    {
        // Test GET /api/menu
        $response = $this->getJson('/api/menu');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'categories' => [
                    '*' => [
                        'id',
                        'name',
                        'items',
                    ],
                ],
            ]);

        // Test GET /api/menu/items
        $response = $this->getJson('/api/menu/items');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'items' => [
                    '*' => [
                        'id',
                        'name',
                        'price',
                        'category_id',
                        'is_available',
                    ],
                ],
            ]);

        // Test GET /api/menu/categories
        $response = $this->getJson('/api/menu/categories');
        $response->assertStatus(200)
            ->assertJsonStructure(['categories']);

        // Test GET /api/menu/{id}
        $response = $this->getJson("/api/menu/{$this->foodItem->id}");
        $response->assertStatus(200)
            ->assertJsonStructure([
                'item' => [
                    'id',
                    'name',
                    'description',
                    'price',
                ],
            ]);
    }

    /**
     * Test 3: Role-Based Access Control
     */
    public function test_role_based_access_control(): void
    {
        // Waiter can create orders
        $response = $this->actingAs($this->waiter, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $this->table->id,
                'guest_id' => $this->guest->id,
                'waiter_id' => $this->waiter->id,
                'items' => [
                    [
                        'menu_item_id' => $this->foodItem->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        // Chef cannot create orders
        $response = $this->actingAs($this->chef, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $this->table->id,
                'guest_id' => $this->guest->id,
                'waiter_id' => $this->waiter->id,
                'items' => [
                    [
                        'menu_item_id' => $this->foodItem->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertStatus(403);

        // Manager can access all endpoints
        $response = $this->actingAs($this->manager, 'sanctum')
            ->getJson('/api/orders');

        $response->assertStatus(200);
    }

    /**
     * Test 4: Payment Processing
     */
    public function test_payment_processing(): void
    {
        $order = Order::factory()->create([
            'guest_id' => $this->guest->id,
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'status' => 'served',
            'subtotal' => 50.00,
            'tax' => 9.00,
            'total' => 59.00,
        ]);

        // Test cash payment
        $response = $this->actingAs($this->waiter, 'sanctum')
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'amount' => 59.00,
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(201);
        $this->assertEquals('paid', $order->fresh()->status);

        // Test partial payment (should fail)
        $order2 = Order::factory()->create([
            'guest_id' => $this->guest->id,
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'status' => 'served',
            'subtotal' => 100.00,
            'tax' => 18.00,
            'total' => 118.00,
        ]);

        $response = $this->actingAs($this->waiter, 'sanctum')
            ->postJson('/api/payments', [
                'order_id' => $order2->id,
                'amount' => 50.00, // Less than total
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test 5: Inventory Deduction
     */
    public function test_inventory_deduction_on_order(): void
    {
        $initialStock = $this->foodItem->stock_quantity;

        $response = $this->actingAs($this->waiter, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $this->table->id,
                'guest_id' => $this->guest->id,
                'waiter_id' => $this->waiter->id,
                'items' => [
                    [
                        'menu_item_id' => $this->foodItem->id,
                        'quantity' => 3,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        // Verify stock was deducted
        $this->assertEquals($initialStock - 3, $this->foodItem->fresh()->stock_quantity);
    }

    /**
     * Test 6: Order Cancellation
     */
    public function test_order_cancellation(): void
    {
        $order = Order::factory()->create([
            'guest_id' => $this->guest->id,
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'status' => 'pending',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $this->foodItem->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($this->waiter, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel", [
                'reason' => 'Customer changed mind',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('cancelled', $order->fresh()->status);
    }

    /**
     * Test 7: Receipt Generation
     */
    public function test_receipt_generation(): void
    {
        $order = Order::factory()->create([
            'guest_id' => $this->guest->id,
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'status' => 'paid',
            'subtotal' => 50.00,
            'tax' => 9.00,
            'total' => 59.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $this->foodItem->id,
            'quantity' => 2,
            'unit_price' => 25.00,
            'subtotal' => 50.00,
        ]);

        $response = $this->actingAs($this->waiter, 'sanctum')
            ->getJson("/api/orders/{$order->id}/receipt");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * Test 8: Table Status Management
     */
    public function test_table_status_management(): void
    {
        // Get all tables
        $response = $this->actingAs($this->waiter, 'sanctum')
            ->getJson('/api/tables');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'tables' => [
                    '*' => [
                        'id',
                        'name',
                        'status',
                        'capacity',
                    ],
                ],
            ]);

        // Update table status
        $response = $this->actingAs($this->waiter, 'sanctum')
            ->patchJson("/api/tables/{$this->table->id}/status", [
                'status' => 'occupied',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('occupied', $this->table->fresh()->status);
    }

    /**
     * Test 9: Authentication Required
     */
    public function test_protected_endpoints_require_authentication(): void
    {
        $endpoints = [
            ['method' => 'post', 'url' => '/api/orders'],
            ['method' => 'get', 'url' => '/api/orders'],
            ['method' => 'post', 'url' => '/api/payments'],
            ['method' => 'get', 'url' => '/api/order-items/pending'],
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->{$endpoint['method'].'Json'}($endpoint['url']);
            $response->assertStatus(401);
        }
    }

    /**
     * Test 10: Error Handling
     */
    public function test_error_handling_for_invalid_requests(): void
    {
        // Invalid menu item
        $response = $this->actingAs($this->waiter, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $this->table->id,
                'guest_id' => $this->guest->id,
                'waiter_id' => $this->waiter->id,
                'items' => [
                    [
                        'menu_item_id' => 99999, // Non-existent
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertStatus(422);

        // Invalid table
        $response = $this->actingAs($this->waiter, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => 99999, // Non-existent
                'guest_id' => $this->guest->id,
                'waiter_id' => $this->waiter->id,
                'items' => [
                    [
                        'menu_item_id' => $this->foodItem->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertStatus(422);

        // Missing required fields
        $response = $this->actingAs($this->waiter, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $this->table->id,
                // Missing guest_id, waiter_id, items
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test 11: Bill Generation
     */
    public function test_bill_generation(): void
    {
        $order = Order::factory()->create([
            'guest_id' => $this->guest->id,
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'status' => 'served',
            'subtotal' => 100.00,
            'tax' => 18.00,
            'total' => 118.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $this->foodItem->id,
            'quantity' => 4,
            'unit_price' => 25.00,
            'subtotal' => 100.00,
        ]);

        $response = $this->actingAs($this->waiter, 'sanctum')
            ->getJson("/api/orders/{$order->id}/bill");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'bill' => [
                    'order_id',
                    'order_number',
                    'items',
                    'subtotal',
                    'tax',
                    'total',
                ],
            ]);
    }

    /**
     * Test 12: Tip Suggestions
     */
    public function test_tip_suggestions(): void
    {
        $order = Order::factory()->create([
            'guest_id' => $this->guest->id,
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'total' => 100.00,
        ]);

        $response = $this->actingAs($this->waiter, 'sanctum')
            ->getJson("/api/orders/{$order->id}/tip-suggestions");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'suggestions' => [
                    '*' => [
                        'percentage',
                        'amount',
                    ],
                ],
            ]);
    }
}
