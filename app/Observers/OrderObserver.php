<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Handle the Order "updated" event.
     * This will send WhatsApp notifications when order status changes
     */
    public function updated(Order $order): void
    {
        // Check if status was changed
        if ($order->wasChanged('status')) {
            // Only send notifications for WhatsApp orders
            if ($order->order_source === 'whatsapp') {
                $newStatus = $order->status;

                try {
                    $this->whatsappService->sendOrderStatusUpdate($order, $newStatus);
                    Log::info("WhatsApp status notification sent for order {$order->id}, status: {$newStatus}");
                } catch (\Exception $e) {
                    Log::error("Failed to send WhatsApp notification for order {$order->id}: ".$e->getMessage());
                }
            }
        }
    }
}
