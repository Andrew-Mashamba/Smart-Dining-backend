<?php

namespace App\Jobs;

use App\Models\Guest;
use App\Services\WhatsApp\AiAgentService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Process a WhatsApp message via the AI agent asynchronously.
 * Dispatched from MessageHandler; webhook returns 200 immediately.
 */
class ProcessAiMessage implements ShouldQueue
{
    use Queueable;

    public int $guestId;

    public array $messageData;

    public string $currentState;

    public int $tries = 2;

    public int $timeout = 150;

    public int $backoff = 5;

    public function __construct(int $guestId, array $messageData, string $currentState)
    {
        $this->guestId = $guestId;
        $this->messageData = $messageData;
        $this->currentState = $currentState;
        $this->onQueue('ai');
    }

    public function handle(AiAgentService $aiService, WhatsAppService $whatsAppService): void
    {
        $guest = Guest::find($this->guestId);

        if (! $guest) {
            Log::channel('whatsapp')->error('ProcessAiMessage: guest not found', ['guest_id' => $this->guestId]);
            return;
        }

        $whatsAppService->sendTypingIndicator($guest->phone_number);

        Log::channel('whatsapp')->info('ProcessAiMessage: starting AI processing', [
            'guest_id' => $guest->id,
            'phone' => $guest->phone_number,
        ]);

        try {
            $handled = $aiService->processMessage($guest, $this->messageData);
            if ($handled) {
                Log::channel('whatsapp')->info('ProcessAiMessage: AI handled successfully', ['guest_id' => $guest->id]);
                return;
            }
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('ProcessAiMessage: AI exception', [
                'guest_id' => $guest->id,
                'error' => $e->getMessage(),
            ]);
        }

        // AI did not handle — send generic reply
        try {
            $whatsAppService->sendTextMessage($guest->phone_number, 'Sorry, I couldn\'t process that. Please try again or contact support.');
        } catch (\Exception $e) {
            Log::channel('whatsapp')->warning('ProcessAiMessage: failed to send fallback message', ['error' => $e->getMessage()]);
        }
    }
}
