<?php

namespace App\Services\WhatsApp;

use App\Models\Guest;
use App\Models\Setting;
use App\Models\Table;
use Illuminate\Support\Facades\Log;

class FlowManager
{
    protected WhatsAppService $whatsappService;

    protected ConversationManager $conversationManager;

    protected MenuBot $menuBot;

    protected ReservationBot $reservationBot;

    protected NotificationBot $notificationBot;

    protected PaymentBot $paymentBot;

    public function __construct(
        WhatsAppService $whatsappService,
        ConversationManager $conversationManager,
        MenuBot $menuBot,
        ReservationBot $reservationBot,
        NotificationBot $notificationBot,
        PaymentBot $paymentBot
    ) {
        $this->whatsappService = $whatsappService;
        $this->conversationManager = $conversationManager;
        $this->menuBot = $menuBot;
        $this->reservationBot = $reservationBot;
        $this->notificationBot = $notificationBot;
        $this->paymentBot = $paymentBot;
    }

    /**
     * Process incoming message based on current state.
     */
    public function processMessage(Guest $guest, string $state, array $messageData): void
    {
        Log::info('Processing message in flow', [
            'guest' => $guest->phone_number,
            'state' => $state,
            'message_type' => $messageData['type'],
        ]);

        try {
            $text = strtolower(trim($messageData['text'] ?? ''));

            // Global reset keywords — always return to main menu
            if (in_array($text, ['menu', 'home', 'start', '0'])) {
                $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');
                $this->sendMainMenu($guest);

                return;
            }

            // Global cancel — always returns to main menu
            if ($text === 'cancel' && $state !== 'MAIN_MENU') {
                $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');
                $this->sendMainMenu($guest);

                return;
            }

            // Step-back navigation — go to the previous logical step
            if ($text === 'back' && $state !== 'MAIN_MENU') {
                $previousState = $this->getPreviousState($state);
                $this->conversationManager->setState($guest->phone_number, $previousState);

                if ($previousState === 'MAIN_MENU') {
                    $this->sendMainMenu($guest);
                } else {
                    // Re-enter the previous state's entry point
                    $this->processMessage($guest, $previousState, ['type' => 'text', 'text' => '']);
                }

                return;
            }

            // Route based on current state
            match ($state) {
                'MAIN_MENU', 'NEW' => $this->handleMainMenu($guest, $messageData),

                // Reservation states
                'RESERVATION_GUESTS', 'RESERVATION_DATE', 'RESERVATION_TIME',
                'RESERVATION_LOCATION', 'RESERVATION_NAME', 'RESERVATION_CONFIRM'
                    => $this->reservationBot->handleState($guest, $state, $messageData),

                // Order states
                'ORDER_TABLE', 'ORDER_CATEGORY', 'ORDER_ITEMS', 'ORDER_QUANTITY',
                'ORDER_SPECIAL', 'ORDER_CART', 'ORDER_CONFIRM'
                    => $this->menuBot->handleState($guest, $state, $messageData),

                // Legacy order states (backward compat)
                'MENU_BROWSING' => $this->menuBot->handleState($guest, 'ORDER_CATEGORY', $messageData),
                'ORDERING' => $this->menuBot->handleState($guest, 'ORDER_ITEMS', $messageData),

                // Current order viewing
                'CURRENT_ORDER' => $this->menuBot->showCurrentOrder($guest, $messageData),

                // Notification states
                'NOTIFICATION_TABLE' => $this->notificationBot->handleState($guest, $state, $messageData),

                // Payment states
                'PAYMENT_METHOD', 'PAYMENT_MPESA', 'PAYMENT_PROCESSING'
                    => $this->paymentBot->handleState($guest, $state, $messageData),

                // Legacy states
                'ORDER_PLACED' => $this->menuBot->handleOrderPlaced($guest, $messageData),
                'DINING' => $this->menuBot->handleDining($guest, $messageData),

                default => $this->sendMainMenu($guest),
            };
        } catch (\Exception $e) {
            Log::error('Flow processing error', [
                'guest' => $guest->phone_number,
                'state' => $state,
                'error' => $e->getMessage(),
            ]);

            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Sorry, something went wrong. Let's start over."
            );

            $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');
            $this->sendMainMenu($guest);
        }
    }

    /**
     * Handle main menu selections.
     */
    protected function handleMainMenu(Guest $guest, array $messageData): void
    {
        $buttonId = $messageData['button_id'] ?? null;
        $listId = $messageData['list_id'] ?? null;
        $text = strtolower(trim($messageData['text'] ?? ''));

        $selection = $listId ?? $buttonId ?? $text;

        match ($selection) {
            '1', 'reservation', 'reserve', 'book', 'main_reservation'
                => $this->reservationBot->start($guest),
            '2', 'order', 'food', 'main_menu'
                => $this->menuBot->start($guest),
            '3', 'my order', 'status', 'current order', 'main_order'
                => $this->menuBot->showCurrentOrder($guest, $messageData),
            '4', 'waiter', 'ring', 'main_waiter'
                => $this->notificationBot->ringWaiter($guest),
            '5', 'manager', 'main_manager'
                => $this->notificationBot->requestManager($guest),
            '6', 'pay', 'bill', 'payment', 'main_pay'
                => $this->paymentBot->start($guest),
            '7', 'table', 'info', 'table info', 'main_table'
                => $this->showTableInfo($guest),
            'hi', 'hello', 'hey'
                => $this->sendMainMenu($guest),
            default => $this->sendMainMenu($guest),
        };
    }

    /**
     * Send the 7-option main menu.
     */
    public function sendMainMenu(Guest $guest): void
    {
        $name = $guest->name ?? 'there';

        $this->whatsappService->sendListMessage(
            $guest->phone_number,
            "Welcome to " . Setting::get('business_name', config('app.name', 'Smart Dining')) . ", {$name}! What would you like to do?",
            'Main Menu',
            [
                [
                    'title' => 'Services',
                    'rows' => [
                        ['id' => 'main_reservation', 'title' => 'Make a Reservation', 'description' => 'Reserve a table for dining'],
                        ['id' => 'main_menu', 'title' => 'View Menu & Order', 'description' => 'Browse menu and place an order'],
                        ['id' => 'main_order', 'title' => 'My Current Order', 'description' => 'Check your order status'],
                        ['id' => 'main_pay', 'title' => 'Pay My Bill', 'description' => 'View and pay your bill'],
                    ],
                ],
                [
                    'title' => 'Assistance',
                    'rows' => [
                        ['id' => 'main_waiter', 'title' => 'Request Waiter', 'description' => 'Call a waiter to your table'],
                        ['id' => 'main_manager', 'title' => 'Request Manager', 'description' => 'Speak with a manager'],
                        ['id' => 'main_table', 'title' => 'Table Info', 'description' => 'View your table details'],
                    ],
                ],
            ]
        );
    }

    /**
     * Get the previous state for step-back navigation.
     */
    protected function getPreviousState(string $currentState): string
    {
        $backMap = [
            // Reservation flow
            'RESERVATION_GUESTS' => 'MAIN_MENU',
            'RESERVATION_DATE' => 'RESERVATION_GUESTS',
            'RESERVATION_TIME' => 'RESERVATION_DATE',
            'RESERVATION_LOCATION' => 'RESERVATION_TIME',
            'RESERVATION_NAME' => 'RESERVATION_LOCATION',
            'RESERVATION_CONFIRM' => 'RESERVATION_LOCATION',

            // Order flow
            'ORDER_TABLE' => 'MAIN_MENU',
            'ORDER_CATEGORY' => 'MAIN_MENU',
            'ORDER_ITEMS' => 'MAIN_MENU',
            'ORDER_QUANTITY' => 'ORDER_ITEMS',
            'ORDER_SPECIAL' => 'ORDER_QUANTITY',
            'ORDER_CART' => 'ORDER_ITEMS',
            'ORDER_CONFIRM' => 'ORDER_CART',

            // Payment flow
            'PAYMENT_METHOD' => 'MAIN_MENU',
            'PAYMENT_MPESA' => 'PAYMENT_METHOD',
            'PAYMENT_PROCESSING' => 'PAYMENT_METHOD',

            // Notification
            'NOTIFICATION_TABLE' => 'MAIN_MENU',
        ];

        return $backMap[$currentState] ?? 'MAIN_MENU';
    }

    /**
     * Show table info for the current session.
     */
    protected function showTableInfo(Guest $guest): void
    {
        $session = $this->conversationManager->getSession($guest->phone_number);

        if ($session->current_table_id) {
            $table = Table::find($session->current_table_id);
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Your Table: {$table->name}\nLocation: ".ucfirst($table->location)."\nCapacity: {$table->capacity} seats\nStatus: ".ucfirst($table->status)
            );
        } else {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "You're not currently assigned to a table.\n\nPlease scan the QR code on your table or enter your table number (e.g., Table 1)."
            );
            $this->conversationManager->setState($guest->phone_number, 'ORDER_TABLE');
        }
    }
}
