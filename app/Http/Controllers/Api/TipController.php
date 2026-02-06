<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Staff;
use App\Services\Payment\TipService;
use Illuminate\Http\Request;

class TipController extends Controller
{
    protected TipService $tipService;

    public function __construct(TipService $tipService)
    {
        $this->tipService = $tipService;
    }

    /**
     * Process a tip
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0',
            'tip_method' => 'required|in:cash,card,mobile_money',
            'payment_id' => 'nullable|exists:payments,id',
        ]);

        $order = Order::findOrFail($validated['order_id']);
        $waiter = Staff::findOrFail($order->waiter_id);

        $tip = $this->tipService->processTip(
            $order,
            $validated['amount'],
            $waiter,
            $validated['tip_method'],
            isset($validated['payment_id']) ? \App\Models\Payment::find($validated['payment_id']) : null
        );

        return response()->json([
            'message' => 'Tip processed successfully',
            'tip' => $tip,
        ], 201);
    }

    /**
     * Get suggested tip amounts for an order
     */
    public function suggestions($orderId)
    {
        $order = Order::findOrFail($orderId);

        $suggestions = $this->tipService->suggestTipAmounts($order);

        return response()->json($suggestions);
    }
}
