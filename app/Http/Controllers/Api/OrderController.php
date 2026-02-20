<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\Staff;
use App\Services\OrderManagement\OrderDistributionService;
use App\Services\OrderManagement\OrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected OrderService $orderService;

    protected OrderDistributionService $distributionService;

    public function __construct(OrderService $orderService, OrderDistributionService $distributionService)
    {
        $this->orderService = $orderService;
        $this->distributionService = $distributionService;
    }

    /**
     * Get all orders with filters
     *
     * Waiters can only view their own orders.
     * Chefs and bartenders can view orders relevant to their prep area.
     * Managers and admins can view all orders.
     */
    public function index(Request $request)
    {
        $staff = $request->user();
        $query = Order::with(['orderItems.menuItem', 'guest', 'table', 'waiter']);

        // Waiters can only see their own orders
        if ($staff->role === 'waiter') {
            $query->where('waiter_id', $staff->id);
        }

        // Chefs and bartenders see all orders (they'll filter by prep_area via order-items)
        // No additional filtering needed here

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('table_id')) {
            $query->where('table_id', $request->table_id);
        }

        if ($request->has('waiter_id')) {
            $query->where('waiter_id', $request->waiter_id);
        }

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($orders);
    }

    /**
     * Create a new order
     *
     * Public endpoint (no auth required). When authenticated, waiter is taken from the user;
     * otherwise from request waiter_id or the first waiter in the system.
     */
    public function store(StoreOrderRequest $request)
    {
        $validated = $request->validated();
        $staff = $request->user();

        // Ensure waiter_id is set: from auth user, request body, or default to first waiter
        if (! isset($validated['waiter_id'])) {
            $validated['waiter_id'] = $staff?->id ?? Staff::where('role', 'waiter')->value('id');
        }
        if (empty($validated['waiter_id'])) {
            return response()->json(['message' => 'No waiter available. Add a waiter staff member.'], 422);
        }

        $order = $this->orderService->createOrder($validated);

        $this->distributionService->distributeOrder($order);

        event(new OrderCreated($order));

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $this->orderService->getOrderSummary($order),
        ], 201);
    }

    /**
     * Get a specific order
     *
     * Waiters can only view their own orders.
     * Managers and admins can view all orders.
     */
    public function show(Request $request, $id)
    {
        $order = Order::with(['orderItems.menuItem', 'guest', 'table', 'waiter', 'payments'])->findOrFail($id);
        $staff = $request->user();

        // Waiters can only view their own orders
        if ($staff->role === 'waiter' && $order->waiter_id !== $staff->id) {
            return response()->json([
                'message' => 'Insufficient permissions',
            ], 403);
        }

        return response()->json($this->orderService->getOrderSummary($order));
    }

    /**
     * Update order status
     */
    public function updateStatus(UpdateOrderStatusRequest $request, $id)
    {
        $validated = $request->validated();

        $order = Order::findOrFail($id);
        $previousStatus = $order->status;

        $this->orderService->updateOrderStatus($order, $validated['status']);

        event(new OrderStatusChanged($order, $previousStatus));

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $this->orderService->getOrderSummary($order),
        ]);
    }

    /**
     * Add items to an existing order
     */
    public function addItems(Request $request, $id)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.special_instructions' => 'nullable|string',
        ]);

        $order = Order::findOrFail($id);

        if (! in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json([
                'message' => 'Cannot add items to an order that is already being prepared',
            ], 422);
        }

        $this->orderService->addItems($order, $request->items);

        return response()->json([
            'message' => 'Items added successfully',
            'order' => $this->orderService->getOrderSummary($order),
        ]);
    }

    /**
     * Mark order as served
     */
    public function markAsServed($id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'ready') {
            return response()->json([
                'message' => 'Order must be ready before marking as served',
            ], 422);
        }

        $previousStatus = $order->status;
        $this->orderService->updateOrderStatus($order, 'served');

        event(new OrderStatusChanged($order, $previousStatus));

        return response()->json([
            'message' => 'Order marked as served',
            'order' => $this->orderService->getOrderSummary($order),
        ]);
    }

    /**
     * Cancel an order
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        $order = Order::findOrFail($id);

        $this->orderService->cancelOrder($order, $request->reason);

        return response()->json([
            'message' => 'Order cancelled successfully',
        ]);
    }

    /**
     * Generate and download receipt PDF for an order
     */
    public function generateReceipt($orderId)
    {
        // Load order with all necessary relationships
        $order = Order::with([
            'orderItems.menuItem',
            'table',
            'waiter',
            'payments',
            'tip',
        ])->findOrFail($orderId);

        // Generate PDF from the receipt blade template
        $pdf = Pdf::loadView('receipts.order-receipt', compact('order'));

        // Set PDF options for thermal printer compatibility
        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait'); // 80mm width
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        // Return PDF download with filename based on order number
        return $pdf->download('receipt-'.$order->order_number.'.pdf');
    }
}
