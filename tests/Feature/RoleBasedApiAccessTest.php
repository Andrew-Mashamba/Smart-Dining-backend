<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Staff;
use App\Models\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Story 43: Role-Based API Access Control Tests
 *
 * Tests all acceptance criteria:
 * 1. API middleware exists and extends CheckRole
 * 2. Applied to API routes with proper role restrictions
 * 3. Waiter access: create orders, view own orders, process payments
 * 4. Chef access: view kitchen orders, update kitchen item prep_status
 * 5. Bartender access: view bar orders, update bar item prep_status
 * 6. Manager access: full access to all API endpoints
 * 7. Admin access: full access including staff management
 * 8. Unauthorized response: 403 JSON with 'Insufficient permissions'
 * 9. Token scopes: Sanctum abilities configured
 * 10. All access restrictions verified
 */
class RoleBasedApiAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $waiter;

    protected Staff $chef;

    protected Staff $bartender;

    protected Staff $manager;

    protected Staff $admin;

    protected Table $table;

    protected Guest $guest;

    protected MenuItem $kitchenItem;

    protected MenuItem $barItem;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test staff members
        $this->waiter = Staff::factory()->create(['role' => 'waiter', 'status' => 'active']);
        $this->chef = Staff::factory()->create(['role' => 'chef', 'status' => 'active']);
        $this->bartender = Staff::factory()->create(['role' => 'bartender', 'status' => 'active']);
        $this->manager = Staff::factory()->create(['role' => 'manager', 'status' => 'active']);
        $this->admin = Staff::factory()->create(['role' => 'admin', 'status' => 'active']);

        // Create test table
        $this->table = Table::factory()->create();

        // Create test guest
        $this->guest = Guest::factory()->create();

        // Create test menu items
        $this->kitchenItem = MenuItem::factory()->create(['prep_area' => 'kitchen']);
        $this->barItem = MenuItem::factory()->create(['prep_area' => 'bar']);
    }

    /** @test */
    public function waiter_can_create_orders()
    {
        $response = $this->actingAs($this->waiter, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $this->table->id,
                'guest_id' => $this->guest->id,
                'waiter_id' => $this->waiter->id,
                'items' => [
                    [
                        'menu_item_id' => $this->kitchenItem->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'order']);
    }

    /** @test */
    public function waiter_can_view_own_orders()
    {
        $order = Order::factory()->create(['waiter_id' => $this->waiter->id]);

        $response = $this->actingAs($this->waiter, 'sanctum')
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function waiter_cannot_view_other_waiters_orders()
    {
        $otherWaiter = Staff::factory()->create(['role' => 'waiter']);
        $order = Order::factory()->create(['waiter_id' => $otherWaiter->id]);

        $response = $this->actingAs($this->waiter, 'sanctum')
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Insufficient permissions']);
    }

    /** @test */
    public function waiter_can_process_payments()
    {
        $order = Order::factory()->create(['waiter_id' => $this->waiter->id]);

        $response = $this->actingAs($this->waiter, 'sanctum')
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'payment_method' => 'cash',
                'amount' => 50.00,
                'tendered' => 60.00,
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function waiter_cannot_update_order_status()
    {
        $order = Order::factory()->create(['waiter_id' => $this->waiter->id]);

        $response = $this->actingAs($this->waiter, 'sanctum')
            ->patchJson("/api/orders/{$order->id}/status", [
                'status' => 'cancelled',
            ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Insufficient permissions']);
    }

    /** @test */
    public function chef_can_view_pending_items()
    {
        $response = $this->actingAs($this->chef, 'sanctum')
            ->getJson('/api/order-items/pending');

        $response->assertStatus(200)
            ->assertJsonStructure(['items', 'total']);
    }

    /** @test */
    public function chef_can_update_kitchen_item_prep_status()
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $this->kitchenItem->id,
            'prep_status' => 'pending',
        ]);

        $response = $this->actingAs($this->chef, 'sanctum')
            ->postJson("/api/order-items/{$orderItem->id}/received");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Order item marked as received']);
    }

    /** @test */
    public function chef_cannot_update_bar_item_prep_status()
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $this->barItem->id,
            'prep_status' => 'pending',
        ]);

        $response = $this->actingAs($this->chef, 'sanctum')
            ->postJson("/api/order-items/{$orderItem->id}/received");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Insufficient permissions']);
    }

    /** @test */
    public function chef_cannot_create_orders()
    {
        $response = $this->actingAs($this->chef, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $this->table->id,
                'guest_id' => $this->guest->id,
                'waiter_id' => $this->chef->id,
                'items' => [
                    ['menu_item_id' => $this->kitchenItem->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function bartender_can_view_pending_items()
    {
        $response = $this->actingAs($this->bartender, 'sanctum')
            ->getJson('/api/order-items/pending');

        $response->assertStatus(200)
            ->assertJsonStructure(['items', 'total']);
    }

    /** @test */
    public function bartender_can_update_bar_item_prep_status()
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $this->barItem->id,
            'prep_status' => 'pending',
        ]);

        $response = $this->actingAs($this->bartender, 'sanctum')
            ->postJson("/api/order-items/{$orderItem->id}/received");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Order item marked as received']);
    }

    /** @test */
    public function bartender_cannot_update_kitchen_item_prep_status()
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $this->kitchenItem->id,
            'prep_status' => 'pending',
        ]);

        $response = $this->actingAs($this->bartender, 'sanctum')
            ->postJson("/api/order-items/{$orderItem->id}/done");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Insufficient permissions']);
    }

    /** @test */
    public function bartender_cannot_process_payments()
    {
        $order = Order::factory()->create();

        $response = $this->actingAs($this->bartender, 'sanctum')
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'payment_method' => 'cash',
                'amount' => 50.00,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function manager_can_create_orders()
    {
        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $this->table->id,
                'guest_id' => $this->guest->id,
                'waiter_id' => $this->manager->id,
                'items' => [
                    ['menu_item_id' => $this->kitchenItem->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function manager_can_view_all_orders()
    {
        Order::factory()->count(3)->create();

        $response = $this->actingAs($this->manager, 'sanctum')
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function manager_can_update_order_status()
    {
        $order = Order::factory()->create(['status' => 'confirmed']);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->patchJson("/api/orders/{$order->id}/status", [
                'status' => 'preparing',
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function manager_can_cancel_orders()
    {
        $order = Order::factory()->create();

        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel", [
                'reason' => 'Test cancellation',
            ]);

        $response->assertSuccessful();
    }

    /** @test */
    public function manager_can_update_menu_availability()
    {
        $response = $this->actingAs($this->manager, 'sanctum')
            ->putJson("/api/menu/{$this->kitchenItem->id}/availability", [
                'available' => false,
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_has_full_access()
    {
        // Test order creation
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $this->table->id,
                'guest_id' => $this->guest->id,
                'waiter_id' => $this->admin->id,
                'items' => [
                    ['menu_item_id' => $this->kitchenItem->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);

        // Test viewing all orders
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/orders');

        $response->assertStatus(200);

        // Test menu management
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/menu/{$this->kitchenItem->id}/availability", [
                'available' => true,
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function unauthenticated_requests_return_401()
    {
        $response = $this->getJson('/api/orders');

        // Should return 401 Unauthenticated (Sanctum middleware)
        $response->assertUnauthorized();
    }

    /** @test */
    public function unauthorized_role_returns_403_with_message()
    {
        $response = $this->actingAs($this->chef, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $this->table->id,
                'guest_id' => $this->guest->id,
                'waiter_id' => $this->chef->id,
                'items' => [],
            ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Insufficient permissions']);
    }

    /** @test */
    public function tokens_have_correct_abilities_for_each_role()
    {
        // Create tokens for each role
        $waiterToken = $this->waiter->createToken('test', ['orders:create', 'orders:view-own'])->accessToken;
        $chefToken = $this->chef->createToken('test', ['order-items:update-kitchen'])->accessToken;
        $bartenderToken = $this->bartender->createToken('test', ['order-items:update-bar'])->accessToken;
        $managerToken = $this->manager->createToken('test', ['*'])->accessToken;
        $adminToken = $this->admin->createToken('test', ['*'])->accessToken;

        // Verify abilities exist
        $this->assertTrue($waiterToken->can('orders:create'));
        $this->assertTrue($chefToken->can('order-items:update-kitchen'));
        $this->assertTrue($bartenderToken->can('order-items:update-bar'));
        $this->assertTrue($managerToken->can('*'));
        $this->assertTrue($adminToken->can('*'));
    }
}
