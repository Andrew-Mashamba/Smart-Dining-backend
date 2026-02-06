<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiUrl;

    protected string $accessToken;

    protected string $phoneNumberId;

    public function __construct()
    {
        $this->apiUrl = config('whatsapp.api_url');
        $this->accessToken = config('whatsapp.access_token');
        $this->phoneNumberId = config('whatsapp.phone_number_id');
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
}
