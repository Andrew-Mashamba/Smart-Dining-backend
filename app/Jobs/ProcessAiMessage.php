<?php

namespace App\Jobs;

use App\Models\Guest;
use App\Services\WhatsApp\AiAgentService;
use App\Services\WhatsApp\FlowManager;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Process a WhatsApp message via the AI agent asynchronously.
 *
 * This job is dispatched from MessageHandler so that the webhook
 * returns 200 immediately (within milliseconds) while the AI
 * processing happens in the background via the queue worker.
 *
 * If the AI agent fails, falls back to FlowManager.
 */
class ProcessAiMessage implements ShouldQueue
{
    use Queueable;

    public int $guestId;

    public array $messageData;

    public string $currentState;

    /**
     * Number of times to attempt the job.
     * AI calls can be flaky — allow 1 retry.
     */
    public int $tries = 2;

    /**
     * Seconds before the job times out.
     * Must exceed the sidecar timeout (120s) + overhead.
     */
    public int $timeout = 150;

    /**
     * Seconds to wait before retrying.
     */
    public int $backoff = 5;

    public function __construct(int $guestId, array $messageData, string $currentState)
    {
        $this->guestId = $guestId;
        $this->messageData = $messageData;
        $this->currentState = $currentState;

        // Use a dedicated queue for AI processing so it doesn't
        // block notification/payment jobs on the default queue.
        $this->onQueue('ai');
    }

    public function handle(AiAgentService $aiService, FlowManager $flowManager, WhatsAppService $whatsAppService): void
    {
        $guest = Guest::find($this->guestId);

        if (! $guest) {
            Log::channel('whatsapp')->error('ProcessAiMessage: guest not found', [
                'guest_id' => $this->guestId,
            ]);

            return;
        }

        // Send typing indicator immediately so guest sees "typing..."
        // while waiting for the AI response.
        $incomingMessageId = $this->messageData['message_id'] ?? null;
        if ($incomingMessageId) {
            $whatsAppService->sendTypingIndicator($incomingMessageId);
        }

        Log::channel('whatsapp')->info('ProcessAiMessage: starting AI processing', [
            'guest_id' => $guest->id,
            'phone' => $guest->phone_number,
        ]);

        try {
            $handled = $aiService->processMessage($guest, $this->messageData);

            if ($handled) {
                Log::channel('whatsapp')->info('ProcessAiMessage: AI handled successfully', [
                    'guest_id' => $guest->id,
                ]);

                return;
            }
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('ProcessAiMessage: AI exception', [
                'guest_id' => $guest->id,
                'error' => $e->getMessage(),
            ]);
        }

        // AI failed — fall back to FlowManager
        Log::channel('whatsapp')->warning('ProcessAiMessage: falling back to FlowManager', [
            'guest_id' => $guest->id,
            'state' => $this->currentState,
        ]);

        try {
            $flowManager->processMessage($guest, $this->currentState, $this->messageData);
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('ProcessAiMessage: FlowManager fallback also failed', [
                'guest_id' => $guest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
