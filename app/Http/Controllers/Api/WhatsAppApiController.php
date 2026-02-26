<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\Order;
use App\Models\Setting;
use App\Services\Menu\MenuService;
use App\Services\Payment\PaymentService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppApiController extends Controller
{
    protected WhatsAppService $whatsappService;

    protected PaymentService $paymentService;

    protected MenuService $menuService;

    public function __construct(
        WhatsAppService $whatsappService,
        PaymentService $paymentService,
        MenuService $menuService
    ) {
        $this->whatsappService = $whatsappService;
        $this->paymentService = $paymentService;
        $this->menuService = $menuService;
    }

    /**
     * Send menu to a guest via WhatsApp.
     */
    public function sendMenu(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => 'required|string',
            'category_id' => 'nullable|integer|exists:menu_categories,id',
        ]);

        $phone = $request->input('phone_number');
        $menuByCategory = $this->menuService->getMenuByCategory();

        if (empty($menuByCategory)) {
            return response()->json([
                'success' => false,
                'message' => 'No menu items available.',
            ], 404);
        }

        // Resolve category name from ID for filtering
        $filterCategoryName = null;
        if ($request->category_id) {
            $filterCategoryName = MenuCategory::where('id', $request->category_id)->value('name');
        }

        // Build menu text
        $menuText = Setting::get('business_name', config('app.name', 'Smart Dining')) . " Menu\n\n";

        foreach ($menuByCategory as $category) {
            if ($filterCategoryName && $category['category'] !== $filterCategoryName) {
                continue;
            }

            $menuText .= "*{$category['category']}*\n";
            foreach ($category['items'] as $item) {
                $menuText .= "  {$item['name']} — TZS ".number_format($item['price'], 0)."\n";
            }
            $menuText .= "\n";
        }

        $menuText .= "Reply with 'order' to place an order!";

        $result = $this->whatsappService->sendTextMessage($phone, $menuText);

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Menu sent successfully.',
        ]);
    }

    /**
     * Send bill to a guest via WhatsApp.
     */
    public function sendBill(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        $order = Order::with(['items.menuItem', 'guest', 'table', 'waiter', 'payments'])->findOrFail($request->order_id);

        if (! $order->guest) {
            return response()->json([
                'success' => false,
                'message' => 'Order has no linked guest with a phone number.',
            ], 422);
        }

        $bill = $this->paymentService->generateBill($order);

        $billText = "Your Bill - Order #{$order->order_number}\n\n";
        foreach ($bill['items'] as $item) {
            $billText .= "{$item['name']} x{$item['quantity']} — TZS ".number_format($item['subtotal'], 0)."\n";
        }
        $billText .= "\nSubtotal: TZS ".number_format($bill['breakdown']['subtotal'], 0);
        $billText .= "\nTax (18%): TZS ".number_format($bill['breakdown']['tax'], 0);
        $billText .= "\nTotal: TZS ".number_format($bill['breakdown']['total'], 0);

        if ($bill['payment_info']['total_paid'] > 0) {
            $billText .= "\nPaid: TZS ".number_format($bill['payment_info']['total_paid'], 0);
            $billText .= "\nBalance Due: TZS ".number_format($bill['payment_info']['balance_due'], 0);
        }

        $billText .= "\n\nReply 'pay' to pay your bill.";

        $result = $this->whatsappService->sendTextMessage($order->guest->phone_number, $billText);

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Bill sent successfully.',
        ]);
    }

    /**
     * Send payment receipt to a guest via WhatsApp.
     */
    public function sendReceipt(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        $order = Order::with(['items.menuItem', 'guest', 'table', 'waiter', 'payments'])->findOrFail($request->order_id);

        if (! $order->guest) {
            return response()->json([
                'success' => false,
                'message' => 'Order has no linked guest with a phone number.',
            ], 422);
        }

        $completedPayments = $order->payments()->where('status', 'completed')->get();
        $totalPaid = $completedPayments->sum('amount');

        $receiptText = "Payment Receipt\n";
        $receiptText .= Setting::get('business_name', config('app.name', 'Smart Dining')) . "\n";
        $receiptText .= str_repeat('─', 30)."\n\n";
        $receiptText .= "Order: #{$order->order_number}\n";
        $receiptText .= "Date: {$order->created_at->format('d M Y, h:i A')}\n";

        if ($order->table) {
            $receiptText .= "Table: {$order->table->name}\n";
        }
        if ($order->waiter) {
            $receiptText .= "Server: {$order->waiter->name}\n";
        }

        $receiptText .= "\n".str_repeat('─', 30)."\n";

        foreach ($order->items as $item) {
            $receiptText .= "{$item->menuItem->name} x{$item->quantity}";
            $receiptText .= str_pad('TZS '.number_format($item->subtotal, 0), 15, ' ', STR_PAD_LEFT)."\n";
        }

        $receiptText .= str_repeat('─', 30)."\n";
        $receiptText .= "Subtotal: TZS ".number_format($order->subtotal, 0)."\n";
        $receiptText .= "Tax: TZS ".number_format($order->tax, 0)."\n";
        $receiptText .= "Total: TZS ".number_format($order->total, 0)."\n";
        $receiptText .= "Paid: TZS ".number_format($totalPaid, 0)."\n";

        foreach ($completedPayments as $payment) {
            $method = ucfirst(str_replace('_', ' ', $payment->payment_method));
            $receiptText .= "  {$method}: TZS ".number_format($payment->amount, 0)."\n";
        }

        $receiptText .= str_repeat('─', 30)."\n";
        $receiptText .= "Thank you for dining at " . Setting::get('business_name', config('app.name', 'Smart Dining')) . "!";

        $result = $this->whatsappService->sendTextMessage($order->guest->phone_number, $receiptText);

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Receipt sent successfully.',
        ]);
    }
}
