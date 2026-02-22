<?php

namespace App\Services\WhatsApp;

use App\Jobs\ProcessAiMessage;
use App\Models\Guest;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MessageHandler
{
    protected WhatsAppService $whatsappService;

    protected FlowManager $flowManager;

    protected ConversationManager $conversationManager;

    public function __construct(
        WhatsAppService $whatsappService,
        FlowManager $flowManager,
        ConversationManager $conversationManager
    ) {
        $this->whatsappService = $whatsappService;
        $this->flowManager = $flowManager;
        $this->conversationManager = $conversationManager;
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

        Log::channel('whatsapp')->info('--- MessageHandler::handle() START ---', [
            'from' => $from,
            'type' => $type,
            'message_id' => $messageId,
            'timestamp' => $timestamp,
        ]);

        if (! $from) {
            Log::channel('whatsapp')->warning('Message received without sender — skipping');

            return;
        }

        // Mark message as read
        if ($messageId) {
            Log::channel('whatsapp')->info('Marking message as read', ['message_id' => $messageId]);
            try {
                $this->whatsappService->markAsRead($messageId);
                Log::channel('whatsapp')->info('Message marked as read OK');
            } catch (\Exception $e) {
                Log::channel('whatsapp')->warning('Failed to mark as read (non-fatal)', ['error' => $e->getMessage()]);
            }
        }

        // Get or create guest
        Log::channel('whatsapp')->info('Getting/creating guest', ['phone' => $from]);
        $guest = $this->getOrCreateGuest($from, $context);
        Log::channel('whatsapp')->info('Guest resolved', [
            'guest_id' => $guest->id,
            'guest_name' => $guest->name,
            'was_new' => $guest->wasRecentlyCreated,
        ]);

        // Link guest to session
        $this->conversationManager->linkGuest($from, $guest->id);

        // Get current conversation state
        $state = $this->conversationManager->getState($from);
        $sessionData = $this->conversationManager->getSessionData($from);
        Log::channel('whatsapp')->info('Session state', [
            'state' => $state,
            'session_data_keys' => array_keys($sessionData),
        ]);

        // Process message based on type
        $messageData = $this->extractMessageData($message, $type);
        Log::channel('whatsapp')->info('Extracted message data', $messageData);

        // ── Deduplication ──
        // WhatsApp retries webhooks if we don't respond fast enough.
        // Skip messages we've already seen (keyed by wamid, TTL 10 min).
        if ($messageId) {
            $dedupKey = "wa-msg-seen:{$messageId}";
            if (Cache::has($dedupKey)) {
                Log::channel('whatsapp')->info('Duplicate message skipped', [
                    'message_id' => $messageId,
                ]);

                return;
            }
            Cache::put($dedupKey, true, 600); // 10 minutes
        }

        // ── AI Agent (async via queue job) ──
        $aiEnabled = Setting::get('whatsapp_ai_enabled', false);
        if ($aiEnabled) {
            Log::channel('whatsapp')->info('AI Agent enabled — dispatching to queue', [
                'guest_id' => $guest->id,
                'phone' => $from,
            ]);

            // Dispatch to the "ai" queue. The job handles AI processing
            // and falls back to FlowManager if the AI fails.
            // PHP-FPM worker is freed immediately.
            ProcessAiMessage::dispatch($guest->id, $messageData, $state);

            Log::channel('whatsapp')->info('--- MessageHandler::handle() DONE (queued for AI) ---');

            return;
        }

        // ── FlowManager (synchronous, AI disabled) ──
        Log::channel('whatsapp')->info('Dispatching to FlowManager::processMessage()');
        $this->flowManager->processMessage($guest, $state, $messageData);

        $newState = $this->conversationManager->getState($from);
        Log::channel('whatsapp')->info('--- MessageHandler::handle() DONE ---', [
            'previous_state' => $state,
            'new_state' => $newState,
        ]);
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
                $data['text'] = $message['button']['text'] ?? '';
                $data['button_id'] = $message['button']['payload'] ?? $data['text'];
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
