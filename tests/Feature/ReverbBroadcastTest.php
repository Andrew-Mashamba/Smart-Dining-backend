<?php

namespace Tests\Feature;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\Staff;
use App\Models\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReverbBroadcastTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test OrderCreated event is dispatched when order is created.
     */
    public function test_order_created_event_is_dispatched(): void
    {
        Event::fake([OrderCreated::class]);

        // Create necessary relationships
        $waiter = Staff::factory()->create(['role' => 'waiter', 'status' => 'active']);
        $table = Table::create([
            'name' => 'Table 1',
            'location' => 'Main Dining',
            'capacity' => 4,
            'status' => 'available',
        ]);

        // Create an order
        $order = Order::create([
            'order_number' => 'ORD-'.now()->format('YmdHis').'-'.$waiter->id,
            'waiter_id' => $waiter->id,
            'table_id' => $table->id,
            'order_source' => 'pos',
            'status' => 'pending',
            'subtotal' => 0.00,
            'tax' => 0.00,
            'total' => 0.00,
        ]);

        // Dispatch the event manually for testing
        event(new OrderCreated($order));

        // Assert the event was dispatched
        Event::assertDispatched(OrderCreated::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }

    /**
     * Test OrderCreated event broadcasts on correct channels.
     */
    public function test_order_created_broadcasts_on_correct_channels(): void
    {
        $waiter = Staff::factory()->create(['role' => 'waiter', 'status' => 'active']);
        $table = Table::create([
            'name' => 'Table 1',
            'location' => 'Main Dining',
            'capacity' => 4,
            'status' => 'available',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-'.now()->format('YmdHis').'-'.$waiter->id.'-1',
            'waiter_id' => $waiter->id,
            'table_id' => $table->id,
            'order_source' => 'pos',
            'status' => 'pending',
            'subtotal' => 0.00,
            'tax' => 0.00,
            'total' => 0.00,
        ]);

        $event = new OrderCreated($order);
        $channels = $event->broadcastOn();

        $this->assertCount(4, $channels);
        $this->assertEquals('orders', $channels[0]->name);
        $this->assertEquals('kitchen', $channels[1]->name);
        $this->assertEquals('bar', $channels[2]->name);
        $this->assertEquals("waiter.{$waiter->id}", $channels[3]->name);
    }

    /**
     * Test OrderStatusUpdated event is dispatched.
     */
    public function test_order_status_updated_event_is_dispatched(): void
    {
        Event::fake([OrderStatusUpdated::class]);

        $waiter = Staff::factory()->create(['role' => 'waiter', 'status' => 'active']);
        $table = Table::create([
            'name' => 'Table 1',
            'location' => 'Main Dining',
            'capacity' => 4,
            'status' => 'available',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-'.now()->format('YmdHis').'-'.$waiter->id.'-2',
            'waiter_id' => $waiter->id,
            'table_id' => $table->id,
            'order_source' => 'pos',
            'status' => 'pending',
            'subtotal' => 0.00,
            'tax' => 0.00,
            'total' => 0.00,
        ]);

        // Dispatch the event manually
        event(new OrderStatusUpdated($order, 'pending', 'preparing'));

        // Assert the event was dispatched
        Event::assertDispatched(OrderStatusUpdated::class, function ($event) use ($order) {
            return $event->order->id === $order->id
                && $event->oldStatus === 'pending'
                && $event->newStatus === 'preparing';
        });
    }

    /**
     * Test OrderStatusUpdated event broadcasts on correct channels.
     */
    public function test_order_status_updated_broadcasts_on_correct_channels(): void
    {
        $waiter = Staff::factory()->create(['role' => 'waiter', 'status' => 'active']);
        $table = Table::create([
            'name' => 'Table 1',
            'location' => 'Main Dining',
            'capacity' => 4,
            'status' => 'available',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-'.now()->format('YmdHis').'-'.$waiter->id.'-3',
            'waiter_id' => $waiter->id,
            'table_id' => $table->id,
            'order_source' => 'pos',
            'status' => 'pending',
            'subtotal' => 0.00,
            'tax' => 0.00,
            'total' => 0.00,
        ]);

        $event = new OrderStatusUpdated($order, 'pending', 'preparing');
        $channels = $event->broadcastOn();

        $this->assertCount(4, $channels);
        $this->assertEquals('orders', $channels[0]->name);
        $this->assertEquals('kitchen', $channels[1]->name);
        $this->assertEquals('bar', $channels[2]->name);
        $this->assertEquals("waiter.{$waiter->id}", $channels[3]->name);
    }
}
