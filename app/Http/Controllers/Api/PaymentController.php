<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Jobs\ProcessPayment;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get payments (optionally filtered by order_id)
     */
    public function index(Request $request)
    {
        $request->validate([
            'order_id' => 'nullable|exists:orders,id',
        ]);

        $query = Payment::query()->with('order');

        if ($request->has('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'payments' => \App\Http\Resources\PaymentResource::collection($payments),
            'total' => $payments->count(),
        ]);
    }

    /**
     * Process a payment
     */
    public function store(ProcessPaymentRequest $request)
    {
        $validated = $request->validated();

        $order = Order::findOrFail($validated['order_id']);

        $payment = $this->paymentService->processPayment($order, $validated);

        if (in_array($validated['payment_method'], ['mobile_money', 'card'])) {
            ProcessPayment::dispatch($payment);
        }

        return response()->json([
            'message' => 'Payment processed successfully',
            'payment' => $payment,
        ], 201);
    }

    /**
     * Get payment details
     */
    public function show($id)
    {
        $payment = Payment::with('order')->findOrFail($id);

        return response()->json($payment);
    }

    /**
     * Confirm a payment
     */
    public function confirm($id)
    {
        $payment = Payment::findOrFail($id);

        $this->paymentService->confirmPayment($payment);

        return response()->json([
            'message' => 'Payment confirmed successfully',
            'payment' => $payment->fresh(),
        ]);
    }

    /**
     * Get bill for an order
     */
    public function getBill($orderId)
    {
        $order = Order::findOrFail($orderId);

        $bill = $this->paymentService->generateBill($order);

        return response()->json($bill);
    }
}
