<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\MessageHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected MessageHandler $messageHandler;

    public function __construct(MessageHandler $messageHandler)
    {
        $this->messageHandler = $messageHandler;
    }

    /**
     * Verify webhook for WhatsApp Business API
     *
     * @return \Illuminate\Http\Response
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('whatsapp.webhook_secret');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('WhatsApp webhook verified successfully');

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp webhook verification failed', [
            'mode' => $mode,
            'token_match' => $token === $verifyToken,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming WhatsApp messages
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        try {
            $data = $request->all();

            Log::info('WhatsApp webhook received', ['data' => $data]);

            // Verify webhook signature if needed
            // $this->verifySignature($request);

            // Check if this is a message event
            if (isset($data['entry']) && is_array($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    if (isset($entry['changes']) && is_array($entry['changes'])) {
                        foreach ($entry['changes'] as $change) {
                            if (isset($change['value']['messages']) && is_array($change['value']['messages'])) {
                                foreach ($change['value']['messages'] as $message) {
                                    $this->messageHandler->handle($message, $change['value']);
                                }
                            }

                            // Handle status updates
                            if (isset($change['value']['statuses'])) {
                                Log::info('Message status update', ['statuses' => $change['value']['statuses']]);
                            }
                        }
                    }
                }
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Verify webhook signature (optional security layer)
     */
    protected function verifySignature(Request $request): void
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            throw new \Exception('Missing signature');
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), config('whatsapp.webhook_secret'));

        if (! hash_equals($expectedSignature, $signature)) {
            throw new \Exception('Invalid signature');
        }
    }
}
