<?php

namespace App\Services\WhatsApp;

use App\Models\Guest;
use Illuminate\Support\Facades\Log;

class MessageHandler
{
    protected WhatsAppService $whatsappService;

    protected FlowManager $flowManager;

    protected StateManager $stateManager;

    public function __construct(
        WhatsAppService $whatsappService,
        FlowManager $flowManager,
        StateManager $stateManager
    ) {
        $this->whatsappService = $whatsappService;
        $this->flowManager = $flowManager;
        $this->stateManager = $stateManager;
    }

    /**
     * Handle incoming WhatsApp message
     */
    public function handle(array $message, array $context): void
    {
        $messageId = $message['id'] ?? null;
        $from = $message['from'] ?? null;
        $timestamp = $message['timestamp'] ?? null;
        $type = $message['type'] ?? 'unknown';

        if (! $from) {
            Log::warning('WhatsApp message received without sender');

            return;
        }

        // Mark message as read
        if ($messageId) {
            $this->whatsappService->markAsRead($messageId);
        }

        Log::info('Processing WhatsApp message', [
            'from' => $from,
            'type' => $type,
            'message_id' => $messageId,
        ]);

        // Get or create guest
        $guest = $this->getOrCreateGuest($from, $context);

        // Get current conversation state
        $state = $this->stateManager->getState($guest);

        // Process message based on type
        $messageData = $this->extractMessageData($message, $type);

        // Handle the message through flow manager
        $this->flowManager->processMessage($guest, $state, $messageData);
    }

    /**
     * Get or create guest by phone number
     */
    protected function getOrCreateGuest(string $phoneNumber, array $context): Guest
    {
        $guest = Guest::firstOrCreate(
            ['phone_number' => $phoneNumber],
            [
                'name' => $context['contacts'][0]['profile']['name'] ?? null,
                'first_visit_at' => now(),
            ]
        );

        if (! $guest->wasRecentlyCreated) {
            $guest->update(['last_visit_at' => now()]);
        }

        return $guest;
    }

    /**
     * Extract message data based on type
     */
    protected function extractMessageData(array $message, string $type): array
    {
        $data = [
            'type' => $type,
            'message_id' => $message['id'] ?? null,
            'timestamp' => $message['timestamp'] ?? null,
        ];

        switch ($type) {
            case 'text':
                $data['text'] = $message['text']['body'] ?? '';
                break;

            case 'interactive':
                if (isset($message['interactive']['type'])) {
                    $interactiveType = $message['interactive']['type'];
                    $data['interactive_type'] = $interactiveType;

                    if ($interactiveType === 'button_reply') {
                        $data['button_id'] = $message['interactive']['button_reply']['id'] ?? null;
                        $data['button_title'] = $message['interactive']['button_reply']['title'] ?? null;
                    } elseif ($interactiveType === 'list_reply') {
                        $data['list_id'] = $message['interactive']['list_reply']['id'] ?? null;
                        $data['list_title'] = $message['interactive']['list_reply']['title'] ?? null;
                    }
                }
                break;

            case 'button':
                $data['button_text'] = $message['button']['text'] ?? '';
                $data['button_payload'] = $message['button']['payload'] ?? '';
                break;

            case 'image':
            case 'video':
            case 'document':
                $data['media_id'] = $message[$type]['id'] ?? null;
                $data['mime_type'] = $message[$type]['mime_type'] ?? null;
                break;

            case 'location':
                $data['latitude'] = $message['location']['latitude'] ?? null;
                $data['longitude'] = $message['location']['longitude'] ?? null;
                break;

            default:
                Log::info('Unsupported message type', ['type' => $type, 'message' => $message]);
        }

        return $data;
    }
}
