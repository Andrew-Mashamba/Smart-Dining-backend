<?php

namespace Tests\Feature;

use App\Events\OrderCreated;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Staff;
use App\Models\Table;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InventoryDeductionTest extends TestCase
{
    use RefreshDatabase;

    protected $manager;

    protected $waiter;

    protected $table;

    protected $category;

    protected $menuItem;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->manager = User::factory()->create([
            'role' => 'manager',
            'status' => 'active',
        ]);

        $this->waiter = Staff::factory()->create([
            'role' => 'waiter',
            'status' => 'active',
        ]);

        // Create table
        $this->table = Table::factory()->create([
            'name' => 'Table 1',
            'status' => 'available',
        ]);

        // Create category
        $this->category = MenuCategory::factory()->create([
            'name' => 'Main Course',
            'status' => 'active',
        ]);

        // Create menu item with stock
        $this->menuItem = MenuItem::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Grilled Chicken',
            'price' => 25.00,
            'stock_quantity' => 10,
            'unit' => 'pieces',
            'low_stock_threshold' => 3,
            'status' => 'available',
        ]);
    }

    /** @test */
    public function it_deducts_stock_when_order_is_created()
    {
        // Create an order with order items
        $order = Order::create([
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'order_source' => 'pos',
            'status' => 'pending',
            'subtotal' => 50.00,
            'tax' => 9.00,
            'total' => 59.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $this->menuItem->id,
            'quantity' => 2,
            'unit_price' => 25.00,
            'subtotal' => 50.00,
            'prep_status' => 'pending',
        ]);

        // Reload order with relationships
        $order = $order->fresh(['orderItems.menuItem', 'table']);

        // Trigger the event
        event(new OrderCreated($order));

        // Process queued jobs
        $this->artisan('queue:work --stop-when-empty');

        // Assert stock was deducted
        $this->menuItem->refresh();
        $this->assertEquals(8, $this->menuItem->stock_quantity);
    }

    /** @test */
    public function it_creates_inventory_transaction_on_order()
    {
        // Create an order with order items
        $order = Order::create([
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'order_source' => 'pos',
            'status' => 'pending',
            'subtotal' => 50.00,
            'tax' => 9.00,
            'total' => 59.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $this->menuItem->id,
            'quantity' => 2,
            'unit_price' => 25.00,
            'subtotal' => 50.00,
            'prep_status' => 'pending',
        ]);

        // Reload order with relationships
        $order = $order->fresh(['orderItems.menuItem', 'table']);

        // Trigger the event
        event(new OrderCreated($order));

        // Process queued jobs
        $this->artisan('queue:work --stop-when-empty');

        // Assert inventory transaction was created
        $this->assertDatabaseHas('inventory_transactions', [
            'menu_item_id' => $this->menuItem->id,
            'transaction_type' => 'sale',
            'quantity' => -2,
            'unit' => 'pieces',
            'reference_id' => $order->id,
            'created_by' => $this->waiter->id,
        ]);
    }

    /** @test */
    public function it_sends_low_stock_notification_when_stock_is_low()
    {
        Notification::fake();

        // Set stock to 4 (just above threshold)
        $this->menuItem->update(['stock_quantity' => 4]);

        // Create an order that will reduce stock below threshold
        $order = Order::create([
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'order_source' => 'pos',
            'status' => 'pending',
            'subtotal' => 50.00,
            'tax' => 9.00,
            'total' => 59.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $this->menuItem->id,
            'quantity' => 2,
            'unit_price' => 25.00,
            'subtotal' => 50.00,
            'prep_status' => 'pending',
        ]);

        // Reload order with relationships
        $order = $order->fresh(['orderItems.menuItem', 'table']);

        // Trigger the event
        event(new OrderCreated($order));

        // Process queued jobs
        $this->artisan('queue:work --stop-when-empty');

        // Assert notification was sent to manager
        Notification::assertSentTo(
            $this->manager,
            LowStockAlert::class,
            function ($notification) {
                return $notification->menuItem->id === $this->menuItem->id;
            }
        );
    }

    /** @test */
    public function it_does_not_send_notification_when_stock_is_above_threshold()
    {
        Notification::fake();

        // Set stock well above threshold
        $this->menuItem->update(['stock_quantity' => 10]);

        // Create an order that won't reduce stock below threshold
        $order = Order::create([
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'order_source' => 'pos',
            'status' => 'pending',
            'subtotal' => 25.00,
            'tax' => 4.50,
            'total' => 29.50,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $this->menuItem->id,
            'quantity' => 1,
            'unit_price' => 25.00,
            'subtotal' => 25.00,
            'prep_status' => 'pending',
        ]);

        // Reload order with relationships
        $order = $order->fresh(['orderItems.menuItem', 'table']);

        // Trigger the event
        event(new OrderCreated($order));

        // Process queued jobs
        $this->artisan('queue:work --stop-when-empty');

        // Assert notification was not sent
        Notification::assertNotSentTo($this->manager, LowStockAlert::class);
    }

    /** @test */
    public function it_prevents_order_creation_when_stock_is_insufficient()
    {
        // Set stock to 2
        $this->menuItem->update(['stock_quantity' => 2]);

        // Attempt to create order with quantity exceeding stock
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only 2 pieces available');

        $order = Order::create([
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'order_source' => 'pos',
            'status' => 'pending',
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
        ]);

        // This should throw an exception in the OrderService
        $orderService = app(\App\Services\OrderManagement\OrderService::class);
        $orderService->addItems($order, [
            [
                'menu_item_id' => $this->menuItem->id,
                'quantity' => 5, // Exceeds available stock
                'special_instructions' => null,
            ],
        ]);
    }

    /** @test */
    public function it_handles_multiple_items_in_order()
    {
        // Create another menu item
        $menuItem2 = MenuItem::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Fish and Chips',
            'price' => 20.00,
            'stock_quantity' => 15,
            'unit' => 'pieces',
            'low_stock_threshold' => 5,
            'status' => 'available',
        ]);

        // Create an order with multiple items
        $order = Order::create([
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'order_source' => 'pos',
            'status' => 'pending',
            'subtotal' => 70.00,
            'tax' => 12.60,
            'total' => 82.60,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $this->menuItem->id,
            'quantity' => 2,
            'unit_price' => 25.00,
            'subtotal' => 50.00,
            'prep_status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem2->id,
            'quantity' => 1,
            'unit_price' => 20.00,
            'subtotal' => 20.00,
            'prep_status' => 'pending',
        ]);

        // Reload order with relationships
        $order = $order->fresh(['orderItems.menuItem', 'table']);

        // Trigger the event
        event(new OrderCreated($order));

        // Process queued jobs
        $this->artisan('queue:work --stop-when-empty');

        // Assert both stocks were deducted
        $this->menuItem->refresh();
        $menuItem2->refresh();

        $this->assertEquals(8, $this->menuItem->stock_quantity);
        $this->assertEquals(14, $menuItem2->stock_quantity);

        // Assert both transactions were created
        $this->assertDatabaseHas('inventory_transactions', [
            'menu_item_id' => $this->menuItem->id,
            'transaction_type' => 'sale',
            'quantity' => -2,
        ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'menu_item_id' => $menuItem2->id,
            'transaction_type' => 'sale',
            'quantity' => -1,
        ]);
    }

    /** @test */
    public function notification_contains_correct_data()
    {
        Notification::fake();

        // Set stock to trigger notification
        $this->menuItem->update(['stock_quantity' => 3]);

        // Create an order
        $order = Order::create([
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'order_source' => 'pos',
            'status' => 'pending',
            'subtotal' => 25.00,
            'tax' => 4.50,
            'total' => 29.50,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $this->menuItem->id,
            'quantity' => 1,
            'unit_price' => 25.00,
            'subtotal' => 25.00,
            'prep_status' => 'pending',
        ]);

        // Reload order with relationships
        $order = $order->fresh(['orderItems.menuItem', 'table']);

        // Trigger the event
        event(new OrderCreated($order));

        // Process queued jobs
        $this->artisan('queue:work --stop-when-empty');

        // Assert notification contains correct data
        Notification::assertSentTo(
            $this->manager,
            LowStockAlert::class,
            function ($notification) {
                $data = $notification->toArray($this->manager);

                return $data['type'] === 'low_stock' &&
                       $data['menu_item_id'] === $this->menuItem->id &&
                       $data['menu_item_name'] === 'Grilled Chicken' &&
                       $data['current_stock'] === 2 &&
                       $data['low_stock_threshold'] === 3 &&
                       $data['unit'] === 'pieces';
            }
        );
    }
}
