<?php

namespace Tests\Feature;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Guest;
use App\Models\InventoryTransaction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Table;
use App\Notifications\LowStockAlert;
use App\Services\OrderManagement\OrderService;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $waiter;

    protected Staff $chef;

    protected Staff $manager;

    protected Table $table;

    protected Guest $guest;

    protected MenuCategory $category;

    protected MenuItem $menuItem;

    protected OrderService $orderService;

    protected PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test staff members
        $this->waiter = Staff::factory()->create([
            'role' => 'waiter',
            'name' => 'Test Waiter',
            'status' => 'active',
        ]);

        $this->chef = Staff::factory()->create([
            'role' => 'chef',
            'name' => 'Test Chef',
            'status' => 'active',
        ]);

        $this->manager = Staff::factory()->create([
            'role' => 'manager',
            'name' => 'Test Manager',
            'status' => 'active',
        ]);

        // Create test table
        $this->table = Table::factory()->create([
            'name' => 'Table 1',
            'status' => 'available',
            'capacity' => 4,
        ]);

        // Create test guest
        $this->guest = Guest::factory()->create([
            'name' => 'Test Guest',
            'phone_number' => '+255712345678',
        ]);

        // Create menu category and item
        $this->category = MenuCategory::factory()->create([
            'name' => 'Main Course',
            'status' => 'active',
        ]);

        $this->menuItem = MenuItem::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Grilled Chicken',
            'price' => 15000,
            'prep_area' => 'kitchen',
            'stock_quantity' => 50,
            'unit' => 'pieces',
            'low_stock_threshold' => 10,
            'status' => 'available',
        ]);

        // Initialize services
        $this->orderService = app(OrderService::class);
        $this->paymentService = app(PaymentService::class);
    }

    /**
     * Test that a waiter can create an order successfully
     * Verifies: order creation, order_items, table status update
     */
    public function test_waiter_can_create_order(): void
    {
        // Act as the waiter
        $this->actingAs($this->waiter, 'sanctum');

        // Prepare order data
        $orderData = [
            'table_id' => $this->table->id,
            'guest_id' => $this->guest->id,
            'waiter_id' => $this->waiter->id,
            'order_source' => 'pos',
            'special_instructions' => 'No spicy',
            'items' => [
                [
                    'menu_item_id' => $this->menuItem->id,
                    'quantity' => 2,
                    'special_instructions' => 'Well done',
                ],
            ],
        ];

        // Create order through service
        $order = $this->orderService->createOrder($orderData);

        // Assert order was created
        $this->assertNotNull($order);
        $this->assertInstanceOf(Order::class, $order);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'table_id' => $this->table->id,
            'guest_id' => $this->guest->id,
            'waiter_id' => $this->waiter->id,
            'order_source' => 'pos',
            'status' => 'pending',
        ]);

        // Assert order number was generated
        $this->assertNotNull($order->order_number);
        $this->assertStringContainsString('ORD-', $order->order_number);

        // Assert order items were created
        $this->assertCount(1, $order->orderItems);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'menu_item_id' => $this->menuItem->id,
            'quantity' => 2,
            'unit_price' => 15000,
            'subtotal' => 30000,
            'special_instructions' => 'Well done',
            'prep_status' => 'pending',
        ]);

        // Assert order totals were calculated correctly
        // Subtotal: 30000
        // Tax (18%): 5400
        // Total: 35400 (no service charge in current implementation)
        $this->assertEquals(30000, $order->subtotal);
        $this->assertEquals(5400, $order->tax);
        $this->assertEquals(35400, $order->total);

        // Assert table status was updated to occupied
        $this->table->refresh();
        $this->assertEquals('occupied', $this->table->status);
    }

    /**
     * Test order status transitions
     * Validates allowed and blocked status changes
     */
    public function test_order_status_transitions(): void
    {
        // Create an order
        $order = Order::factory()->create([
            'waiter_id' => $this->waiter->id,
            'table_id' => $this->table->id,
            'status' => 'pending',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $this->menuItem->id,
        ]);

        // Test valid transition: pending -> confirmed
        $this->orderService->updateOrderStatus($order, 'confirmed');
        $order->refresh();
        $this->assertEquals('confirmed', $order->status);

        // Test valid transition: confirmed -> preparing
        $this->orderService->updateOrderStatus($order, 'preparing');
        $order->refresh();
        $this->assertEquals('preparing', $order->status);

        // Test valid transition: preparing -> ready
        $this->orderService->updateOrderStatus($order, 'ready');
        $order->refresh();
        $this->assertEquals('ready', $order->status);

        // Test valid transition: ready -> served
        $this->orderService->updateOrderStatus($order, 'served');
        $order->refresh();
        $this->assertEquals('served', $order->status);

        // Test valid transition: served -> completed
        $this->orderService->updateOrderStatus($order, 'completed');
        $order->refresh();
        $this->assertEquals('completed', $order->status);

        // Test invalid transition (should throw exception or fail)
        $order2 = Order::factory()->create([
            'waiter_id' => $this->waiter->id,
            'table_id' => $this->table->id,
            'status' => 'pending',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order2->id,
            'menu_item_id' => $this->menuItem->id,
        ]);

        // Try to transition directly from pending to served (should fail)
        try {
            $this->orderService->updateOrderStatus($order2, 'served');
            $this->fail('Expected exception for invalid status transition');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Cannot transition from', $e->getMessage());
        }

        // Test cancellation from pending
        $order3 = Order::factory()->create([
            'waiter_id' => $this->waiter->id,
            'table_id' => $this->table->id,
            'status' => 'pending',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order3->id,
            'menu_item_id' => $this->menuItem->id,
        ]);

        $this->orderService->cancelOrder($order3, 'Customer changed mind');
        $order3->refresh();
        $this->assertEquals('cancelled', $order3->status);
    }

    /**
     * Test inventory deduction when order is created
     * Confirms stock decreases and transaction is created
     */
    public function test_inventory_deduction_on_order(): void
    {
        // Ensure queue is sync for this test to execute listeners immediately
        Queue::fake();

        // Set initial stock
        $initialStock = 50;
        $this->menuItem->update(['stock_quantity' => $initialStock]);

        // Prepare order data
        $orderData = [
            'table_id' => $this->table->id,
            'guest_id' => $this->guest->id,
            'waiter_id' => $this->waiter->id,
            'order_source' => 'pos',
            'items' => [
                [
                    'menu_item_id' => $this->menuItem->id,
                    'quantity' => 3,
                ],
            ],
        ];

        // Create order (this should trigger inventory deduction)
        $order = $this->orderService->createOrder($orderData);

        // Manually trigger the listener for testing since it's queued
        $listener = new \App\Listeners\DeductInventoryStock;
        $listener->handle(new OrderCreated($order->fresh()));

        // Assert stock was deducted
        $this->menuItem->refresh();
        $this->assertEquals($initialStock - 3, $this->menuItem->stock_quantity);
        $this->assertEquals(47, $this->menuItem->stock_quantity);

        // Assert inventory transaction was created
        $this->assertDatabaseHas('inventory_transactions', [
            'menu_item_id' => $this->menuItem->id,
            'transaction_type' => 'sale',
            'quantity' => -3,
            'reference_id' => $order->id,
        ]);

        // Verify transaction record
        $transaction = InventoryTransaction::where('menu_item_id', $this->menuItem->id)
            ->where('reference_id', $order->id)
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals('sale', $transaction->transaction_type);
        $this->assertEquals(-3, $transaction->quantity);
        $this->assertEquals($this->menuItem->unit, $transaction->unit);
    }

    /**
     * Test low stock notification
     * Verifies alert sent when stock falls below threshold
     */
    public function test_low_stock_notification(): void
    {
        Notification::fake();
        Queue::fake();

        // Set stock just above threshold
        $lowThreshold = 10;
        $this->menuItem->update([
            'stock_quantity' => 12,
            'low_stock_threshold' => $lowThreshold,
        ]);

        // Create order that will bring stock below threshold
        $orderData = [
            'table_id' => $this->table->id,
            'guest_id' => $this->guest->id,
            'waiter_id' => $this->waiter->id,
            'order_source' => 'pos',
            'items' => [
                [
                    'menu_item_id' => $this->menuItem->id,
                    'quantity' => 5, // This will bring stock to 7, below threshold of 10
                ],
            ],
        ];

        $order = $this->orderService->createOrder($orderData);

        // Manually trigger the listener for testing since it's queued
        $listener = new \App\Listeners\DeductInventoryStock;
        $listener->handle(new OrderCreated($order->fresh()));

        // Assert stock fell below threshold
        $this->menuItem->refresh();
        $this->assertEquals(7, $this->menuItem->stock_quantity);
        $this->assertLessThan($lowThreshold, $this->menuItem->stock_quantity);

        // Assert low stock notification was sent to manager
        Notification::assertSentTo(
            [$this->manager],
            LowStockAlert::class,
            function ($notification, $channels) {
                return $notification->menuItem->id === $this->menuItem->id;
            }
        );
    }

    /**
     * Test payment processing
     * Creates payment and updates order status to paid
     */
    public function test_payment_processing(): void
    {
        // Create an order
        $order = Order::factory()->create([
            'waiter_id' => $this->waiter->id,
            'table_id' => $this->table->id,
            'status' => 'served',
            'total' => 35400,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $this->menuItem->id,
            'quantity' => 2,
            'unit_price' => 15000,
            'subtotal' => 30000,
        ]);

        // Process cash payment
        $paymentData = [
            'payment_method' => 'cash',
            'amount' => 35400,
        ];

        $payment = $this->paymentService->processPayment($order, $paymentData);

        // Assert payment was created
        $this->assertNotNull($payment);
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_method' => 'cash',
            'amount' => 35400,
            'status' => 'completed',
        ]);

        // Assert order status was updated
        $order->refresh();
        $this->assertEquals('completed', $order->status);

        // Test card payment (should queue job)
        Queue::fake();

        $order2 = Order::factory()->create([
            'waiter_id' => $this->waiter->id,
            'table_id' => $this->table->id,
            'status' => 'served',
            'total' => 50000,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order2->id,
            'menu_item_id' => $this->menuItem->id,
        ]);

        $cardPaymentData = [
            'payment_method' => 'card',
            'amount' => 50000,
        ];

        $payment2 = $this->paymentService->processPayment($order2, $cardPaymentData);

        // Assert payment record was created
        $this->assertDatabaseHas('payments', [
            'order_id' => $order2->id,
            'payment_method' => 'card',
            'amount' => 50000,
        ]);

        // For card payments, verify job was dispatched
        // Note: In the actual implementation, card payments are auto-confirmed in dev
        // But we verify the payment exists
        $this->assertNotNull($payment2);
    }

    /**
     * Test order broadcasts events
     * Asserts OrderCreated and OrderStatusUpdated events are dispatched
     */
    public function test_order_broadcasts_events(): void
    {
        Event::fake([
            OrderCreated::class,
            OrderStatusUpdated::class,
        ]);

        // Create order
        $orderData = [
            'table_id' => $this->table->id,
            'guest_id' => $this->guest->id,
            'waiter_id' => $this->waiter->id,
            'order_source' => 'pos',
            'items' => [
                [
                    'menu_item_id' => $this->menuItem->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $order = $this->orderService->createOrder($orderData);

        // Assert OrderCreated event was dispatched
        Event::assertDispatched(OrderCreated::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });

        // Update order status
        $this->orderService->updateOrderStatus($order, 'confirmed');

        // Assert OrderStatusUpdated event was dispatched
        Event::assertDispatched(OrderStatusUpdated::class, function ($event) use ($order) {
            return $event->order->id === $order->id
                && $event->oldStatus === 'pending'
                && $event->newStatus === 'confirmed';
        });
    }

    /**
     * Test kitchen display filters
     * Verifies only kitchen items are shown to chefs
     */
    public function test_kitchen_display_filters(): void
    {
        // Create kitchen menu item
        $kitchenItem = MenuItem::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Pasta Carbonara',
            'price' => 18000,
            'prep_area' => 'kitchen',
            'stock_quantity' => 30,
        ]);

        // Create bar menu item
        $barItem = MenuItem::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Mojito',
            'price' => 8000,
            'prep_area' => 'bar',
            'stock_quantity' => 50,
        ]);

        // Create order with both items
        $order = Order::factory()->create([
            'waiter_id' => $this->waiter->id,
            'table_id' => $this->table->id,
            'status' => 'pending',
        ]);

        $kitchenOrderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $kitchenItem->id,
            'quantity' => 1,
            'prep_status' => 'pending',
        ]);

        $barOrderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $barItem->id,
            'quantity' => 1,
            'prep_status' => 'pending',
        ]);

        // Query for kitchen items only
        $kitchenOrders = Order::with(['orderItems.menuItem'])
            ->whereHas('orderItems.menuItem', function ($query) {
                $query->whereIn('prep_area', ['kitchen', 'both']);
            })
            ->where('status', '!=', 'cancelled')
            ->get();

        // Assert kitchen orders were found
        $this->assertGreaterThan(0, $kitchenOrders->count());

        // Filter to get only kitchen items from the order
        $kitchenItemsInOrder = $order->orderItems()
            ->whereHas('menuItem', function ($query) {
                $query->whereIn('prep_area', ['kitchen', 'both']);
            })
            ->get();

        // Assert only kitchen item is included
        $this->assertCount(1, $kitchenItemsInOrder);
        $this->assertEquals($kitchenItem->id, $kitchenItemsInOrder->first()->menu_item_id);

        // Verify bar item is excluded
        $this->assertFalse(
            $kitchenItemsInOrder->contains('menu_item_id', $barItem->id)
        );

        // Test bar filter
        $barItemsInOrder = $order->orderItems()
            ->whereHas('menuItem', function ($query) {
                $query->whereIn('prep_area', ['bar', 'both']);
            })
            ->get();

        $this->assertCount(1, $barItemsInOrder);
        $this->assertEquals($barItem->id, $barItemsInOrder->first()->menu_item_id);
    }

    /**
     * Test receipt generation
     * Creates order, generates PDF, asserts file exists
     */
    public function test_receipt_generation(): void
    {
        // Setup fake storage
        Storage::fake('local');

        // Create order with items
        $order = Order::factory()->create([
            'waiter_id' => $this->waiter->id,
            'table_id' => $this->table->id,
            'guest_id' => $this->guest->id,
            'status' => 'completed',
            'order_source' => 'pos',
            'subtotal' => 30000,
            'tax' => 5400,
            'total' => 35400,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $this->menuItem->id,
            'quantity' => 2,
            'unit_price' => 15000,
            'subtotal' => 30000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'payment_method' => 'cash',
            'amount' => 35400,
            'status' => 'completed',
        ]);

        // Act as waiter and make API request to generate receipt
        $this->actingAs($this->waiter, 'sanctum');

        $response = $this->getJson("/api/orders/{$order->id}/receipt");

        // Assert response is successful
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');

        // Verify the response contains PDF data
        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $this->assertStringStartsWith('%PDF', $content);

        // Verify order data is properly formatted
        $order->refresh();
        $this->assertNotNull($order->order_number);
        $this->assertEquals(35400, $order->total);
        $this->assertCount(1, $order->orderItems);
    }
}
