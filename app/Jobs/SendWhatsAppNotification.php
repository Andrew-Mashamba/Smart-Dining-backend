<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWhatsAppNotification implements ShouldQueue
{
    use Queueable;

    public string $phoneNumber;

    public string $message;

    public array $context;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phoneNumber, string $message, array $context = [])
    {
        $this->phoneNumber = $phoneNumber;
        $this->message = $message;
        $this->context = $context;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // In production, integrate with WhatsApp Business API
            // For now, log the notification
            \Log::info('WhatsApp notification sent', [
                'phone' => $this->phoneNumber,
                'message' => $this->message,
                'context' => $this->context,
            ]);

            // Example integration (to be implemented):
            // $whatsappService = app(WhatsAppService::class);
            // $whatsappService->sendMessage($this->phoneNumber, $this->message);

        } catch (\Exception $e) {
            \Log::error('WhatsApp notification failed', [
                'phone' => $this->phoneNumber,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 10;
}
