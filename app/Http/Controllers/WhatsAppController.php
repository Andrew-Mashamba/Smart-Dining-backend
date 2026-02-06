<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Handle WhatsApp webhook verification (GET request)
     * This is called by WhatsApp to verify the webhook endpoint
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('services.whatsapp.verify_token');

        // Check if mode and token are correct
        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('WhatsApp webhook verified successfully');

            return response($challenge, 200);
        }

        Log::warning('WhatsApp webhook verification failed', [
            'mode' => $mode,
            'token' => $token,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming WhatsApp messages (POST request)
     */
    public function webhook(Request $request)
    {
        try {
            $data = $request->all();
            Log::info('WhatsApp webhook received', ['data' => $data]);

            // Check if this is a message event
            if (! isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
                return response()->json(['status' => 'ok'], 200);
            }

            $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
            $phoneNumber = $message['from'];
            $messageType = $message['type'];

            // Only process text messages
            if ($messageType !== 'text') {
                Log::info('Non-text message received, ignoring', ['type' => $messageType]);

                return response()->json(['status' => 'ok'], 200);
            }

            $messageText = strtolower(trim($message['text']['body']));

            // Process the message based on content
            $this->handleMessage($phoneNumber, $messageText);

            return response()->json(['status' => 'ok'], 200);

        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp webhook: '.$e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
            ]);

            // Always return 200 to prevent WhatsApp from retrying
            return response()->json(['status' => 'error'], 200);
        }
    }

    /**
     * Handle different types of messages
     */
    protected function handleMessage(string $phoneNumber, string $messageText): void
    {
        try {
            // Check for specific commands
            if ($messageText === 'menu') {
                $this->whatsappService->sendMenu($phoneNumber);
            } elseif ($messageText === 'help') {
                $this->whatsappService->sendHelpMessage($phoneNumber);
            } elseif ($messageText === 'status') {
                $this->whatsappService->sendRecentOrderStatus($phoneNumber);
            } elseif (str_starts_with($messageText, 'order')) {
                $this->whatsappService->processOrder($phoneNumber, $messageText);
            } else {
                // Unknown command - send help message
                $this->whatsappService->sendHelpMessage($phoneNumber);
            }
        } catch (\Exception $e) {
            Log::error('Error handling WhatsApp message: '.$e->getMessage(), [
                'phone' => $phoneNumber,
                'message' => $messageText,
                'exception' => $e,
            ]);

            // Send error message to user
            $this->whatsappService->sendMessage(
                $phoneNumber,
                'Sorry, something went wrong. Please try again or type *help* for assistance.'
            );
        }
    }
}
