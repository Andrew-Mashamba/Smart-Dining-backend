<?php

namespace Tests\Feature;

use App\Events\OrderStatusUpdated;
use App\Exceptions\OrderWorkflowException;
use App\Models\AuditLog;
use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Table;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderWorkflowService $service;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderWorkflowService;
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_allows_valid_transition_from_pending_to_preparing()
    {
        Event::fake();

        $order = $this->createOrder('pending');

        $updatedOrder = $this->service->updateStatus($order->id, 'preparing');

        $this->assertEquals('preparing', $updatedOrder->status);
        Event::assertDispatched(OrderStatusUpdated::class);
    }

    /** @test */
    public function it_allows_valid_transition_from_preparing_to_ready_when_all_items_ready()
    {
        Event::fake();

        $order = $this->createOrder('preparing');
        $this->createOrderItems($order, 3, 'ready');

        $updatedOrder = $this->service->updateStatus($order->id, 'ready');

        $this->assertEquals('ready', $updatedOrder->status);
        Event::assertDispatched(OrderStatusUpdated::class);
    }

    /** @test */
    public function it_allows_valid_transition_from_ready_to_delivered()
    {
        Event::fake();

        $order = $this->createOrder('ready');

        $updatedOrder = $this->service->updateStatus($order->id, 'delivered');

        $this->assertEquals('delivered', $updatedOrder->status);
        Event::assertDispatched(OrderStatusUpdated::class);
    }

    /** @test */
    public function it_allows_valid_transition_from_delivered_to_paid_when_payment_complete()
    {
        Event::fake();

        $order = $this->createOrder('delivered', 100.00);
        $this->createPayment($order, 100.00);

        $updatedOrder = $this->service->updateStatus($order->id, 'paid');

        $this->assertEquals('paid', $updatedOrder->status);
        Event::assertDispatched(OrderStatusUpdated::class);
    }

    /** @test */
    public function it_prevents_invalid_transition_from_delivered_to_preparing()
    {
        $order = $this->createOrder('delivered');

        $this->expectException(OrderWorkflowException::class);
        $this->expectExceptionMessage("Invalid status transition from 'delivered' to 'preparing'");

        $this->service->updateStatus($order->id, 'preparing');
    }

    /** @test */
    public function it_prevents_transition_to_ready_when_items_not_ready()
    {
        $order = $this->createOrder('preparing');
        $this->createOrderItems($order, 2, 'preparing');
        $this->createOrderItems($order, 1, 'ready');

        $this->expectException(OrderWorkflowException::class);
        $this->expectExceptionMessage("cannot be marked as ready because not all items have prep_status='ready'");

        $this->service->updateStatus($order->id, 'ready');
    }

    /** @test */
    public function it_prevents_transition_to_paid_when_payment_insufficient()
    {
        $order = $this->createOrder('delivered', 100.00);
        $this->createPayment($order, 50.00);

        $this->expectException(OrderWorkflowException::class);
        $this->expectExceptionMessage('cannot be marked as paid');

        $this->service->updateStatus($order->id, 'paid');
    }

    /** @test */
    public function it_updates_table_status_to_available_when_order_paid_and_no_active_orders()
    {
        $table = Table::factory()->create(['status' => 'occupied']);
        $order = $this->createOrder('delivered', 100.00, $table);
        $this->createPayment($order, 100.00);

        $this->service->updateStatus($order->id, 'paid');

        $this->assertEquals('available', $table->fresh()->status);
    }

    /** @test */
    public function it_keeps_table_occupied_when_other_active_orders_exist()
    {
        $table = Table::factory()->create(['status' => 'occupied']);
        $order1 = $this->createOrder('delivered', 100.00, $table);
        $order2 = $this->createOrder('preparing', 50.00, $table);
        $this->createPayment($order1, 100.00);

        $this->service->updateStatus($order1->id, 'paid');

        $this->assertEquals('occupied', $table->fresh()->status);
    }

    /** @test */
    public function it_creates_audit_log_on_status_change()
    {
        $order = $this->createOrder('pending');

        $this->service->updateStatus($order->id, 'preparing');

        // Check general audit log
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
            'event' => 'status_changed',
            'user_id' => $this->user->id,
        ]);

        $auditLog = AuditLog::where('auditable_id', $order->id)->first();
        $this->assertEquals(['status' => 'pending'], $auditLog->old_values);
        $this->assertEquals(['status' => 'preparing'], $auditLog->new_values);

        // Check order status log
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'old_status' => 'pending',
            'new_status' => 'preparing',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_broadcasts_event_on_status_change()
    {
        Event::fake();

        $order = $this->createOrder('pending');

        $this->service->updateStatus($order->id, 'preparing');

        Event::assertDispatched(OrderStatusUpdated::class, function ($event) use ($order) {
            return $event->order->id === $order->id
                && $event->oldStatus === 'pending'
                && $event->newStatus === 'preparing';
        });
    }

    /** @test */
    public function it_throws_exception_when_order_not_found()
    {
        $this->expectException(OrderWorkflowException::class);
        $this->expectExceptionMessage('Order #999 not found');

        $this->service->updateStatus(999, 'preparing');
    }

    /** @test */
    public function it_returns_valid_transitions_for_status()
    {
        $transitions = $this->service->getValidTransitions('pending');
        $this->assertEquals(['preparing'], $transitions);

        $transitions = $this->service->getValidTransitions('preparing');
        $this->assertEquals(['ready'], $transitions);

        $transitions = $this->service->getValidTransitions('ready');
        $this->assertEquals(['delivered'], $transitions);

        $transitions = $this->service->getValidTransitions('delivered');
        $this->assertEquals(['paid'], $transitions);
    }

    /** @test */
    public function it_checks_if_transition_is_valid()
    {
        $this->assertTrue($this->service->isValidTransition('pending', 'preparing'));
        $this->assertFalse($this->service->isValidTransition('pending', 'delivered'));
        $this->assertFalse($this->service->isValidTransition('delivered', 'preparing'));
    }

    /**
     * Helper method to create an order.
     */
    protected function createOrder(string $status, float $total = 50.00, ?Table $table = null): Order
    {
        if (! $table) {
            $table = Table::factory()->create();
        }

        $guest = Guest::factory()->create();
        $waiter = Staff::factory()->create(['role' => 'waiter']);

        return Order::factory()->create([
            'table_id' => $table->id,
            'guest_id' => $guest->id,
            'waiter_id' => $waiter->id,
            'status' => $status,
            'subtotal' => $total / 1.18,
            'tax' => ($total / 1.18) * 0.18,
            'total' => $total,
        ]);
    }

    /**
     * Helper method to create order items.
     */
    protected function createOrderItems(Order $order, int $count, string $prepStatus): void
    {
        $menuItem = MenuItem::factory()->create();

        for ($i = 0; $i < $count; $i++) {
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
                'prep_status' => $prepStatus,
            ]);
        }
    }

    /**
     * Helper method to create a payment.
     */
    protected function createPayment(Order $order, float $amount): Payment
    {
        return Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => $amount,
            'status' => 'completed',
        ]);
    }
}
