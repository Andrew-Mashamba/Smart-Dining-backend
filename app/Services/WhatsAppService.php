<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;

class WhatsAppService
{
    protected $whatsapp;

    protected $fromPhoneNumberId;

    public function __construct()
    {
        $this->fromPhoneNumberId = config('services.whatsapp.phone_number_id');
        $this->whatsapp = new WhatsAppCloudApi([
            'from_phone_number_id' => $this->fromPhoneNumberId,
            'access_token' => config('services.whatsapp.api_token'),
        ]);
    }

    /**
     * Send a text message to a WhatsApp user
     */
    public function sendMessage(string $to, string $message): void
    {
        try {
            $this->whatsapp->sendTextMessage($to, $message);
            Log::info("WhatsApp message sent to {$to}");
        } catch (\Exception $e) {
            Log::error("Failed to send WhatsApp message to {$to}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Format and send the menu to a WhatsApp user
     */
    public function sendMenu(string $to): void
    {
        $categories = MenuCategory::with(['menuItems' => function ($query) {
            $query->where('status', 'available')->orderBy('name');
        }])->where('status', 'active')->get();

        $menuText = "🍽️ *SEACLIFF DINING MENU* 🍽️\n\n";

        foreach ($categories as $category) {
            $menuText .= "*{$category->name}*\n";
            if ($category->description) {
                $menuText .= "_{$category->description}_\n";
            }
            $menuText .= "\n";

            foreach ($category->menuItems as $item) {
                $menuText .= "• {$item->name} - BWP ".number_format($item->price, 2)."\n";
                if ($item->description) {
                    $menuText .= "  _{$item->description}_\n";
                }
            }
            $menuText .= "\n";
        }

        $menuText .= "📝 To order, type:\n";
        $menuText .= "*order [item name] x [quantity]*\n\n";
        $menuText .= "Example: order Pizza x 2, Burger x 1\n\n";
        $menuText .= 'Type *help* for more commands.';

        $this->sendMessage($to, $menuText);
    }

    /**
     * Send help message to a WhatsApp user
     */
    public function sendHelpMessage(string $to): void
    {
        $helpText = "ℹ️ *AVAILABLE COMMANDS* ℹ️\n\n";
        $helpText .= "*menu* - View our full menu\n";
        $helpText .= "*order* - Place an order\n";
        $helpText .= "  Example: order Pizza x 2, Burger x 1\n\n";
        $helpText .= "*status* - Check your recent order status\n";
        $helpText .= "*help* - Show this help message\n\n";
        $helpText .= 'Need assistance? Just send us a message!';

        $this->sendMessage($to, $helpText);
    }

    /**
     * Parse order text and create an order
     * Expected format: "order item1 x quantity1, item2 x quantity2"
     */
    public function processOrder(string $phoneNumber, string $orderText): void
    {
        try {
            DB::beginTransaction();

            // Get or create guest
            $guest = Guest::firstOrCreate(
                ['phone_number' => $phoneNumber],
                ['name' => 'WhatsApp Guest '.substr($phoneNumber, -4)]
            );

            // Parse order items from text
            $orderItems = $this->parseOrderItems($orderText);

            if (empty($orderItems)) {
                $this->sendMessage($phoneNumber, "❌ Sorry, I couldn't understand your order. Please use the format:\n\n*order Pizza x 2, Burger x 1*\n\nType *menu* to see available items.");
                DB::rollBack();

                return;
            }

            // Create order
            $order = Order::create([
                'guest_id' => $guest->id,
                'table_id' => null,
                'waiter_id' => null,
                'order_source' => 'whatsapp',
                'status' => 'pending',
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
            ]);

            // Create order items
            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $item['menu_item']->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['menu_item']->price,
                    'prep_status' => 'pending',
                ]);
            }

            // Calculate totals
            $order->calculateTotals();
            $order->refresh();

            // Send confirmation
            $this->sendOrderConfirmation($phoneNumber, $order);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing WhatsApp order: '.$e->getMessage());
            $this->sendMessage($phoneNumber, '❌ Sorry, there was an error processing your order. Please try again or contact us directly.');
        }
    }

    /**
     * Parse order items from text
     * Format: "item1 x quantity1, item2 x quantity2"
     */
    protected function parseOrderItems(string $orderText): array
    {
        $items = [];

        // Remove "order" prefix if present
        $orderText = preg_replace('/^order\s+/i', '', trim($orderText));

        // Split by comma
        $parts = explode(',', $orderText);

        foreach ($parts as $part) {
            $part = trim($part);

            // Match pattern: "item name x quantity"
            if (preg_match('/(.+?)\s*x\s*(\d+)/i', $part, $matches)) {
                $itemName = trim($matches[1]);
                $quantity = (int) $matches[2];

                // Find menu item (case-insensitive partial match)
                $menuItem = MenuItem::where('status', 'available')
                    ->where('name', 'LIKE', "%{$itemName}%")
                    ->first();

                if ($menuItem && $quantity > 0) {
                    $items[] = [
                        'menu_item' => $menuItem,
                        'quantity' => $quantity,
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * Send order confirmation message
     */
    public function sendOrderConfirmation(string $phoneNumber, Order $order): void
    {
        $confirmationText = "✅ *ORDER CONFIRMED* ✅\n\n";
        $confirmationText .= "Order Number: *{$order->order_number}*\n\n";
        $confirmationText .= "*Items:*\n";

        foreach ($order->orderItems as $item) {
            $confirmationText .= "• {$item->menuItem->name} x {$item->quantity} - BWP ".number_format($item->subtotal, 2)."\n";
        }

        $confirmationText .= "\n*Subtotal:* BWP ".number_format($order->subtotal, 2)."\n";
        $confirmationText .= '*Tax (18%):* BWP '.number_format($order->tax, 2)."\n";
        $confirmationText .= '*Total:* BWP '.number_format($order->total, 2)."\n\n";

        // Calculate estimated time based on prep times
        $estimatedTime = $this->calculateEstimatedTime($order);
        $confirmationText .= "⏱️ Estimated Time: {$estimatedTime} minutes\n\n";
        $confirmationText .= "We'll notify you when your order is ready for pickup!\n";
        $confirmationText .= 'Thank you for ordering! 🙏';

        $this->sendMessage($phoneNumber, $confirmationText);
    }

    /**
     * Calculate estimated preparation time
     */
    protected function calculateEstimatedTime(Order $order): int
    {
        $maxPrepTime = 0;

        foreach ($order->orderItems as $item) {
            if ($item->menuItem->prep_time_minutes > $maxPrepTime) {
                $maxPrepTime = $item->menuItem->prep_time_minutes;
            }
        }

        // Add 10 minutes buffer
        return $maxPrepTime + 10;
    }

    /**
     * Send order status update notification
     */
    public function sendOrderStatusUpdate(Order $order, string $newStatus): void
    {
        if (! $order->guest || ! $order->guest->phone_number) {
            return;
        }

        $phoneNumber = $order->guest->phone_number;
        $statusMessages = [
            'preparing' => "👨‍🍳 *ORDER IN PREPARATION* 👨‍🍳\n\nYour order *{$order->order_number}* is now being prepared by our kitchen team!\n\nWe'll notify you when it's ready.",
            'ready' => "✅ *ORDER READY FOR PICKUP* ✅\n\nYour order *{$order->order_number}* is ready!\n\nPlease come to the counter to collect your order.\n\nTotal: BWP ".number_format($order->total, 2),
            'completed' => "🎉 *ORDER COMPLETED* 🎉\n\nThank you for your order *{$order->order_number}*!\n\nWe hope you enjoyed your meal. Looking forward to serving you again! 😊",
            'cancelled' => "❌ *ORDER CANCELLED* ❌\n\nYour order *{$order->order_number}* has been cancelled.\n\nIf you have any questions, please contact us.",
        ];

        if (isset($statusMessages[$newStatus])) {
            try {
                $this->sendMessage($phoneNumber, $statusMessages[$newStatus]);
            } catch (\Exception $e) {
                Log::error("Failed to send status update for order {$order->id}: ".$e->getMessage());
            }
        }
    }

    /**
     * Check recent order status for a phone number
     */
    public function sendRecentOrderStatus(string $phoneNumber): void
    {
        $guest = Guest::where('phone_number', $phoneNumber)->first();

        if (! $guest) {
            $this->sendMessage($phoneNumber, "You don't have any orders yet. Type *menu* to see our offerings!");

            return;
        }

        $recentOrder = Order::where('guest_id', $guest->id)
            ->where('order_source', 'whatsapp')
            ->latest()
            ->first();

        if (! $recentOrder) {
            $this->sendMessage($phoneNumber, "You don't have any orders yet. Type *menu* to see our offerings!");

            return;
        }

        $statusText = "📋 *ORDER STATUS* 📋\n\n";
        $statusText .= "Order Number: *{$recentOrder->order_number}*\n";
        $statusText .= 'Status: *'.ucfirst($recentOrder->status)."*\n";
        $statusText .= 'Total: BWP '.number_format($recentOrder->total, 2)."\n\n";

        $statusEmojis = [
            'pending' => '⏳',
            'preparing' => '👨‍🍳',
            'ready' => '✅',
            'completed' => '🎉',
            'cancelled' => '❌',
        ];

        $emoji = $statusEmojis[$recentOrder->status] ?? '📦';

        if ($recentOrder->status === 'pending') {
            $statusText .= "{$emoji} Your order is confirmed and waiting to be prepared.";
        } elseif ($recentOrder->status === 'preparing') {
            $statusText .= "{$emoji} Your order is being prepared by our kitchen!";
        } elseif ($recentOrder->status === 'ready') {
            $statusText .= "{$emoji} Your order is ready for pickup!";
        } elseif ($recentOrder->status === 'completed') {
            $statusText .= "{$emoji} Your order has been completed. Thank you!";
        } elseif ($recentOrder->status === 'cancelled') {
            $statusText .= "{$emoji} Your order has been cancelled.";
        }

        $this->sendMessage($phoneNumber, $statusText);
    }
}
