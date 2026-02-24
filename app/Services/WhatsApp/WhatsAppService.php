<?php

namespace App\Services\WhatsApp;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiUrl;

    protected string $accessToken;

    protected string $phoneNumberId;

    public function __construct()
    {
        $this->apiUrl = Setting::get('whatsapp_api_url', config('whatsapp.api_url'));
        $this->accessToken = Setting::get('whatsapp_access_token', config('whatsapp.access_token')) ?? '';
        $this->phoneNumberId = Setting::get('whatsapp_phone_number_id', config('whatsapp.phone_number_id')) ?? '';
    }

    /**
     * Send a text message
     */
    public function sendTextMessage(string $to, string $message): array
    {
        $url = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        $response = Http::withToken($this->accessToken)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message,
                ],
            ]);

        Log::info('WhatsApp text message sent', [
            'to' => $to,
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        return $response->json();
    }

    /**
     * Send an interactive message with buttons
     */
    public function sendButtonMessage(string $to, string $bodyText, array $buttons): array
    {
        $url = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        $formattedButtons = array_map(function ($button) {
            return [
                'type' => 'reply',
                'reply' => [
                    'id' => $button['id'],
                    'title' => $button['title'],
                ],
            ];
        }, $buttons);

        $response = Http::withToken($this->accessToken)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'button',
                    'body' => [
                        'text' => $bodyText,
                    ],
                    'action' => [
                        'buttons' => $formattedButtons,
                    ],
                ],
            ]);

        Log::info('WhatsApp button message sent', [
            'to' => $to,
            'status' => $response->status(),
        ]);

        return $response->json();
    }

    /**
     * Send an interactive list message
     */
    public function sendListMessage(string $to, string $bodyText, string $buttonText, array $sections): array
    {
        $url = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        $response = Http::withToken($this->accessToken)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'list',
                    'body' => [
                        'text' => $bodyText,
                    ],
                    'action' => [
                        'button' => $buttonText,
                        'sections' => $sections,
                    ],
                ],
            ]);

        Log::info('WhatsApp list message sent', [
            'to' => $to,
            'status' => $response->status(),
        ]);

        return $response->json();
    }

    /**
     * Send a template message
     */
    public function sendTemplateMessage(string $to, string $templateName, array $parameters = []): array
    {
        $url = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        $components = [];
        if (! empty($parameters)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(function ($param) {
                    return ['type' => 'text', 'text' => $param];
                }, $parameters),
            ];
        }

        $response = Http::withToken($this->accessToken)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => 'en',
                    ],
                    'components' => $components,
                ],
            ]);

        Log::info('WhatsApp template message sent', [
            'to' => $to,
            'template' => $templateName,
            'status' => $response->status(),
        ]);

        return $response->json();
    }

    /**
     * Send an image message
     */
    public function sendImageMessage(string $to, string $imageUrl, string $caption = ''): array
    {
        $url = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        $imageData = [
            'link' => $imageUrl,
        ];

        if ($caption) {
            $imageData['caption'] = $caption;
        }

        $response = Http::withToken($this->accessToken)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'image',
                'image' => $imageData,
            ]);

        Log::info('WhatsApp image message sent', [
            'to' => $to,
            'status' => $response->status(),
        ]);

        return $response->json();
    }

    /**
     * Send a document message (PDF, etc.)
     */
    public function sendDocumentMessage(string $to, string $documentUrl, string $caption = '', string $filename = ''): array
    {
        $url = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        $docData = ['link' => $documentUrl];
        if ($caption) {
            $docData['caption'] = $caption;
        }
        if ($filename) {
            $docData['filename'] = $filename;
        }

        $response = Http::withToken($this->accessToken)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'document',
                'document' => $docData,
            ]);

        Log::info('WhatsApp document message sent', [
            'to' => $to,
            'status' => $response->status(),
        ]);

        return $response->json();
    }

    /**
     * Mark a message as read
     */
    public function markAsRead(string $messageId): array
    {
        $url = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        $response = Http::withToken($this->accessToken)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'status' => 'read',
                'message_id' => $messageId,
            ]);

        return $response->json();
    }

    /**
     * Send typing indicator to show "typing..." in the guest's chat.
     * Lasts up to 25 seconds or until a reply is sent, whichever
     * comes first. Call before starting AI processing so the guest
     * sees immediate feedback while waiting.
     *
     * Requires the incoming message ID (wamid) — the API marks
     * that message as read and displays the typing bubble.
     */
    public function sendTypingIndicator(string $messageId): void
    {
        $url = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        try {
            Http::withToken($this->accessToken)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'status' => 'read',
                    'message_id' => $messageId,
                    'typing_indicator' => [
                        'type' => 'text',
                    ],
                ]);
        } catch (\Exception $e) {
            // Non-fatal — don't break the flow if typing indicator fails
            Log::debug('Typing indicator failed', ['message_id' => $messageId, 'error' => $e->getMessage()]);
        }
    }
}
