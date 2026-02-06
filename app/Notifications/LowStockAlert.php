<?php

namespace App\Notifications;

use App\Models\MenuItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public MenuItem $menuItem
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'menu_item_id' => $this->menuItem->id,
            'menu_item_name' => $this->menuItem->name,
            'current_stock' => $this->menuItem->stock_quantity,
            'low_stock_threshold' => $this->menuItem->low_stock_threshold,
            'unit' => $this->menuItem->unit,
            'message' => "Low stock alert: {$this->menuItem->name} is running low ({$this->menuItem->stock_quantity} {$this->menuItem->unit} remaining)",
        ];
    }
}
