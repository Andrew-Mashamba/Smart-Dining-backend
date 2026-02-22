<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AiStaffNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $notificationType,
        public string $message,
        public array $context = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->notificationType,
            'message' => $this->message,
            'source' => 'ai_chatbot',
            'guest_id' => $this->context['guest_id'] ?? null,
            'guest_name' => $this->context['guest_name'] ?? null,
            'guest_phone' => $this->context['guest_phone'] ?? null,
            'order_id' => $this->context['order_id'] ?? null,
            'order_number' => $this->context['order_number'] ?? null,
            'table_id' => $this->context['table_id'] ?? null,
            'table_name' => $this->context['table_name'] ?? null,
            'timestamp' => now()->toISOString(),
        ];
    }
}
