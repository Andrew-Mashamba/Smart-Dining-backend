<?php

namespace App\Services\WhatsApp;

use App\Events\ManagerRequested;
use App\Events\WaiterRequested;
use App\Models\Guest;
use App\Models\Table;
use Illuminate\Support\Facades\Log;

class NotificationBot
{
    protected WhatsAppService $whatsappService;

    protected ConversationManager $conversationManager;

    public function __construct(
        WhatsAppService $whatsappService,
        ConversationManager $conversationManager
    ) {
        $this->whatsappService = $whatsappService;
        $this->conversationManager = $conversationManager;
    }

    /**
     * Ring a waiter to the guest's table.
     */
    public function ringWaiter(Guest $guest): void
    {
        $session = $this->conversationManager->getSession($guest->phone_number);

        if (! $session->current_table_id) {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Please enter your table number so we can send a waiter (e.g., Table 1, T0001):"
            );
            $this->conversationManager->setState(
                $guest->phone_number,
                'NOTIFICATION_TABLE',
                ['notification_type' => 'waiter']
            );

            return;
        }

        $table = Table::find($session->current_table_id);

        if (! $table) {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "We couldn't find your table. Please enter your table number:"
            );
            $this->conversationManager->setState(
                $guest->phone_number,
                'NOTIFICATION_TABLE',
                ['notification_type' => 'waiter']
            );

            return;
        }

        event(new WaiterRequested($table, $guest));

        $this->whatsappService->sendTextMessage(
            $guest->phone_number,
            "Your waiter has been notified and will be with you shortly!"
        );

        $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');

        Log::info('Waiter requested via WhatsApp', [
            'table' => $table->name,
            'guest' => $guest->phone_number,
        ]);
    }

    /**
     * Request a manager to the guest's table.
     */
    public function requestManager(Guest $guest): void
    {
        $session = $this->conversationManager->getSession($guest->phone_number);

        if (! $session->current_table_id) {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Please enter your table number so we can send a manager (e.g., Table 1, T0001):"
            );
            $this->conversationManager->setState(
                $guest->phone_number,
                'NOTIFICATION_TABLE',
                ['notification_type' => 'manager']
            );

            return;
        }

        $table = Table::find($session->current_table_id);

        if (! $table) {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "We couldn't find your table. Please enter your table number:"
            );
            $this->conversationManager->setState(
                $guest->phone_number,
                'NOTIFICATION_TABLE',
                ['notification_type' => 'manager']
            );

            return;
        }

        event(new ManagerRequested($table, $guest));

        $this->whatsappService->sendTextMessage(
            $guest->phone_number,
            "A manager has been notified and will attend to you shortly."
        );

        $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');

        Log::info('Manager requested via WhatsApp', [
            'table' => $table->name,
            'guest' => $guest->phone_number,
        ]);
    }

    /**
     * Handle the NOTIFICATION_TABLE state (table number input).
     */
    public function handleState(Guest $guest, string $state, array $messageData): void
    {
        $text = $messageData['text'] ?? '';
        $table = $this->findTable($text);

        if (! $table) {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Sorry, I couldn't find that table. Please try again (e.g., Table 1, T0001)."
            );

            return;
        }

        $this->conversationManager->setCurrentTable($guest->phone_number, $table->id);

        $sessionData = $this->conversationManager->getSessionData($guest->phone_number);
        $notificationType = $sessionData['notification_type'] ?? 'waiter';

        if ($notificationType === 'manager') {
            $this->requestManager($guest);
        } else {
            $this->ringWaiter($guest);
        }
    }

    /**
     * Find a table by a text identifier.
     */
    protected function findTable(string $text): ?Table
    {
        $text = trim($text);

        // Try exact match first
        $table = Table::where('name', $text)->first();
        if ($table) {
            return $table;
        }

        // Try numeric match
        if (preg_match('/(\d+)/', $text, $matches)) {
            return Table::where('name', 'LIKE', "%{$matches[1]}%")->first();
        }

        return null;
    }
}
