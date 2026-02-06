<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Staff;
use App\Models\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiOrdersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a waiter can create an order via API
     */
    public function test_waiter_can_create_order_via_api(): void
    {
        // Create necessary test data
        $waiter = Staff::factory()->create([
            'role' => 'waiter',
            'status' => 'active',
        ]);

        $table = Table::factory()->create([
            'status' => 'available',
        ]);

        $guest = Guest::factory()->create();

        $category = MenuCategory::factory()->create();
        $menuItem = MenuItem::factory()->create([
            'category_id' => $category->id,
            'price' => 15.00,
            'status' => 'available',
            'stock_quantity' => 100,
        ]);

        // Authenticate as waiter using Sanctum
        $response = $this->actingAs($waiter, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $table->id,
                'guest_id' => $guest->id,
                'waiter_id' => $waiter->id,
                'order_source' => 'pos',
                'items' => [
                    [
                        'menu_item_id' => $menuItem->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        // Assert order was created successfully
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'order',
        ]);

        // Verify order exists in database
        $this->assertDatabaseHas('orders', [
            'table_id' => $table->id,
            'guest_id' => $guest->id,
            'waiter_id' => $waiter->id,
        ]);
    }

    /**
     * Test that a chef can view kitchen orders via API
     */
    public function test_chef_can_view_kitchen_orders(): void
    {
        // Create a chef
        $chef = Staff::factory()->create([
            'role' => 'chef',
            'status' => 'active',
        ]);

        // Create a waiter who owns the order
        $waiter = Staff::factory()->create([
            'role' => 'waiter',
            'status' => 'active',
        ]);

        // Create kitchen menu items
        $category = MenuCategory::factory()->create();
        $kitchenItem = MenuItem::factory()->create([
            'category_id' => $category->id,
            'prep_area' => 'kitchen',
            'status' => 'available',
        ]);

        // Create an order with kitchen items
        $table = Table::factory()->create();
        $guest = Guest::factory()->create();
        $order = Order::factory()->create([
            'table_id' => $table->id,
            'guest_id' => $guest->id,
            'waiter_id' => $waiter->id,
            'status' => 'pending',
        ]);

        // Authenticate as chef and view orders
        $response = $this->actingAs($chef, 'sanctum')
            ->getJson('/api/orders');

        // Assert chef can view orders
        $response->assertStatus(200);

        // Verify response contains order data
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'order_number',
                    'status',
                    'table_id',
                ],
            ],
        ]);

        // Verify the created order is in the results
        $orderIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($order->id, $orderIds);
    }

    /**
     * Test that a chef cannot create orders via API
     */
    public function test_chef_cannot_create_orders(): void
    {
        // Create a chef
        $chef = Staff::factory()->create([
            'role' => 'chef',
            'status' => 'active',
        ]);

        $waiter = Staff::factory()->create([
            'role' => 'waiter',
            'status' => 'active',
        ]);

        $table = Table::factory()->create();
        $guest = Guest::factory()->create();
        $category = MenuCategory::factory()->create();
        $menuItem = MenuItem::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 100,
        ]);

        // Attempt to create order as chef (should fail due to middleware)
        $response = $this->actingAs($chef, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $table->id,
                'guest_id' => $guest->id,
                'waiter_id' => $waiter->id,
                'order_source' => 'pos',
                'items' => [
                    [
                        'menu_item_id' => $menuItem->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        // Assert forbidden response
        $response->assertStatus(403);

        // Verify no order was created
        $this->assertDatabaseMissing('orders', [
            'table_id' => $table->id,
            'guest_id' => $guest->id,
        ]);
    }

    /**
     * Test that unauthenticated requests fail
     */
    public function test_unauthenticated_request_fails(): void
    {
        // Create required data to ensure database is in a valid state
        $table = Table::factory()->create();
        $guest = Guest::factory()->create();

        // Test accessing menu endpoint (doesn't require auth) returns 200
        $response = $this->getJson('/api/menu');
        $response->assertStatus(200);

        // Test accessing protected tables endpoint without auth returns 401
        $response = $this->getJson('/api/tables');
        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);

        // Test creating order without authentication returns 401
        $response = $this->postJson('/api/orders', [
            'table_id' => $table->id,
            'guest_id' => $guest->id,
            'items' => [],
        ]);
        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    /**
     * Test API validation with invalid data
     */
    public function test_api_validation(): void
    {
        $waiter = Staff::factory()->create([
            'role' => 'waiter',
            'status' => 'active',
        ]);

        // Test 1: Missing required fields
        $response = $this->actingAs($waiter, 'sanctum')
            ->postJson('/api/orders', []);

        // Assert 422 validation error
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors',
        ]);

        // Test 2: Invalid table_id (non-existent)
        $response = $this->actingAs($waiter, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => 99999,
                'guest_id' => 1,
                'waiter_id' => $waiter->id,
                'items' => [
                    [
                        'menu_item_id' => 1,
                        'quantity' => 1,
                    ],
                ],
            ]);

        // Assert validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['table_id']);

        // Test 3: Invalid items (negative quantity)
        $table = Table::factory()->create();
        $guest = Guest::factory()->create();
        $category = MenuCategory::factory()->create();
        $menuItem = MenuItem::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 100,
        ]);

        $response = $this->actingAs($waiter, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $table->id,
                'guest_id' => $guest->id,
                'waiter_id' => $waiter->id,
                'items' => [
                    [
                        'menu_item_id' => $menuItem->id,
                        'quantity' => -5,
                    ],
                ],
            ]);

        // Assert validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.quantity']);
    }

    /**
     * Test API rate limiting
     */
    public function test_api_rate_limiting(): void
    {
        $staff = Staff::factory()->create([
            'role' => 'waiter',
            'status' => 'active',
        ]);

        // Make 61 requests to exceed the 60 requests per minute limit
        // Using a simple endpoint that doesn't modify data
        $responses = [];
        for ($i = 0; $i < 61; $i++) {
            $response = $this->actingAs($staff, 'sanctum')
                ->getJson('/api/tables');
            $responses[] = $response->status();
        }

        // The 61st request should be rate limited (429 Too Many Requests)
        $this->assertEquals(429, end($responses),
            'Expected 61st request to be rate limited with 429 status code');

        // Verify that at least one request was throttled
        $this->assertContains(429, $responses,
            'Expected at least one 429 status code in responses');
    }

    /**
     * Test that waiter can only view their own orders
     */
    public function test_waiter_can_only_view_own_orders(): void
    {
        // Create two waiters
        $waiter1 = Staff::factory()->create([
            'role' => 'waiter',
            'status' => 'active',
        ]);

        $waiter2 = Staff::factory()->create([
            'role' => 'waiter',
            'status' => 'active',
        ]);

        // Create orders for each waiter
        $table = Table::factory()->create();
        $guest = Guest::factory()->create();

        $order1 = Order::factory()->create([
            'waiter_id' => $waiter1->id,
            'table_id' => $table->id,
            'guest_id' => $guest->id,
        ]);

        $order2 = Order::factory()->create([
            'waiter_id' => $waiter2->id,
            'table_id' => $table->id,
            'guest_id' => $guest->id,
        ]);

        // Waiter1 should only see their own order
        $response = $this->actingAs($waiter1, 'sanctum')
            ->getJson('/api/orders');

        $response->assertStatus(200);
        $orderIds = collect($response->json('data'))->pluck('id')->toArray();

        // Waiter1 should see order1 but not order2
        $this->assertContains($order1->id, $orderIds);
        $this->assertNotContains($order2->id, $orderIds);

        // Waiter1 should not be able to view waiter2's order details
        $response = $this->actingAs($waiter1, 'sanctum')
            ->getJson('/api/orders/'.$order2->id);

        $response->assertStatus(403);
    }

    /**
     * Test that manager can view all orders
     */
    public function test_manager_can_view_all_orders(): void
    {
        $manager = Staff::factory()->create([
            'role' => 'manager',
            'status' => 'active',
        ]);

        $waiter1 = Staff::factory()->create(['role' => 'waiter']);
        $waiter2 = Staff::factory()->create(['role' => 'waiter']);

        $table = Table::factory()->create();
        $guest = Guest::factory()->create();

        // Create orders for different waiters
        $order1 = Order::factory()->create([
            'waiter_id' => $waiter1->id,
            'table_id' => $table->id,
            'guest_id' => $guest->id,
        ]);

        $order2 = Order::factory()->create([
            'waiter_id' => $waiter2->id,
            'table_id' => $table->id,
            'guest_id' => $guest->id,
        ]);

        // Manager should see all orders
        $response = $this->actingAs($manager, 'sanctum')
            ->getJson('/api/orders');

        $response->assertStatus(200);
        $orderIds = collect($response->json('data'))->pluck('id')->toArray();

        // Manager should see both orders
        $this->assertContains($order1->id, $orderIds);
        $this->assertContains($order2->id, $orderIds);
    }

    /**
     * Test that bartender cannot create orders
     */
    public function test_bartender_cannot_create_orders(): void
    {
        $bartender = Staff::factory()->create([
            'role' => 'bartender',
            'status' => 'active',
        ]);

        $waiter = Staff::factory()->create([
            'role' => 'waiter',
            'status' => 'active',
        ]);

        $table = Table::factory()->create();
        $guest = Guest::factory()->create();
        $category = MenuCategory::factory()->create();
        $menuItem = MenuItem::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 100,
        ]);

        // Attempt to create order as bartender (should fail due to middleware)
        $response = $this->actingAs($bartender, 'sanctum')
            ->postJson('/api/orders', [
                'table_id' => $table->id,
                'guest_id' => $guest->id,
                'waiter_id' => $waiter->id,
                'order_source' => 'pos',
                'items' => [
                    [
                        'menu_item_id' => $menuItem->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        // Assert forbidden response
        $response->assertStatus(403);
    }
}
