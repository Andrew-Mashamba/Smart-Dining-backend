<?php

namespace App\Services\WhatsApp;

use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\Table;
use App\Services\GuestSession\SessionService;
use App\Services\Menu\MenuService;
use App\Services\OrderManagement\OrderService;
use Illuminate\Support\Facades\Log;

class FlowManager
{
    protected WhatsAppService $whatsappService;

    protected StateManager $stateManager;

    protected MenuService $menuService;

    protected SessionService $sessionService;

    protected OrderService $orderService;

    public function __construct(
        WhatsAppService $whatsappService,
        StateManager $stateManager,
        MenuService $menuService,
        SessionService $sessionService,
        OrderService $orderService
    ) {
        $this->whatsappService = $whatsappService;
        $this->stateManager = $stateManager;
        $this->menuService = $menuService;
        $this->sessionService = $sessionService;
        $this->orderService = $orderService;
    }

    /**
     * Process incoming message based on current state
     */
    public function processMessage(Guest $guest, string $state, array $messageData): void
    {
        Log::info('Processing message in flow', [
            'guest' => $guest->phone_number,
            'state' => $state,
            'message_type' => $messageData['type'],
        ]);

        try {
            match ($state) {
                'NEW' => $this->handleNewGuest($guest, $messageData),
                'MENU_BROWSING' => $this->handleMenuBrowsing($guest, $messageData),
                'ORDERING' => $this->handleOrdering($guest, $messageData),
                'ORDER_PLACED' => $this->handleOrderPlaced($guest, $messageData),
                'DINING' => $this->handleDining($guest, $messageData),
                'BILLING' => $this->handleBilling($guest, $messageData),
                default => $this->handleUnknownState($guest, $messageData),
            };
        } catch (\Exception $e) {
            Log::error('Flow processing error', [
                'guest' => $guest->phone_number,
                'state' => $state,
                'error' => $e->getMessage(),
            ]);

            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                'Sorry, something went wrong. Please try again or contact our staff.'
            );
        }
    }

    /**
     * Handle new guest (first interaction)
     */
    protected function handleNewGuest(Guest $guest, array $messageData): void
    {
        // Check if message contains table code
        $text = $messageData['text'] ?? '';

        if (preg_match('/TABLE[_\s]?(\d+)/i', $text, $matches)) {
            $tableNumber = $matches[1];
            $table = Table::where('name', 'LIKE', "%{$tableNumber}%")->first();

            if ($table) {
                // Create session
                $session = $this->sessionService->startSession($guest, $table);

                $this->stateManager->updateContext($guest, 'session_id', $session->id);
                $this->stateManager->updateContext($guest, 'table_id', $table->id);

                // Send welcome message
                $welcomeMessage = $guest->wasRecentlyCreated
                    ? "Welcome to Sea Cliff! 🌊\n\nYou're seated at {$table->name}. Let's get started!"
                    : "Welcome back {$guest->name}! 🎉\n\nYou're at {$table->name}. Ready to order?";

                $this->whatsappService->sendButtonMessage(
                    $guest->phone_number,
                    $welcomeMessage,
                    [
                        ['id' => 'view_menu', 'title' => 'View Menu'],
                        ['id' => 'call_waiter', 'title' => 'Call Waiter'],
                    ]
                );

                $this->stateManager->setState($guest, 'MENU_BROWSING');

                return;
            }
        }

        // Invalid table code
        $this->whatsappService->sendTextMessage(
            $guest->phone_number,
            "Welcome to Sea Cliff! 🌊\n\nPlease scan the QR code on your table to get started."
        );
    }

    /**
     * Handle menu browsing state
     */
    protected function handleMenuBrowsing(Guest $guest, array $messageData): void
    {
        $buttonId = $messageData['button_id'] ?? null;
        $listId = $messageData['list_id'] ?? null;
        $text = strtolower($messageData['text'] ?? '');

        if ($buttonId === 'view_menu' || $text === 'menu') {
            $this->sendMenuCategories($guest);
        } elseif ($buttonId === 'call_waiter') {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                'A waiter will be with you shortly! 👨‍🍳'
            );
        } elseif ($listId) {
            // Handle category selection
            $this->sendCategoryItems($guest, $listId);
        } else {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                'Please select an option from the menu.'
            );
        }
    }

    /**
     * Send menu categories as interactive list
     */
    protected function sendMenuCategories(Guest $guest): void
    {
        $menuByCategory = $this->menuService->getMenuByCategory();

        $sections = [];
        foreach ($menuByCategory as $category) {
            $rows = [];
            foreach ($category['items'] as $item) {
                $rows[] = [
                    'id' => 'item_'.$item['id'],
                    'title' => $item['name'],
                    'description' => 'TZS '.number_format($item['price'], 0),
                ];

                if (count($rows) >= 10) {
                    break;
                } // WhatsApp limit per section
            }

            if (! empty($rows)) {
                $sections[] = [
                    'title' => ucfirst($category['category']),
                    'rows' => $rows,
                ];
            }
        }

        $this->whatsappService->sendListMessage(
            $guest->phone_number,
            "Here's our menu today! 📋\n\nTap below to browse by category.",
            'View Menu',
            $sections
        );

        $this->stateManager->setState($guest, 'ORDERING');
    }

    /**
     * Handle ordering state
     */
    protected function handleOrdering(Guest $guest, array $messageData): void
    {
        $listId = $messageData['list_id'] ?? null;

        if ($listId && strpos($listId, 'item_') === 0) {
            $itemId = str_replace('item_', '', $listId);
            $this->addItemToCart($guest, $itemId);
        }
    }

    /**
     * Add item to guest's cart
     */
    protected function addItemToCart(Guest $guest, int $itemId): void
    {
        $context = $this->stateManager->getContext($guest);
        $cart = $context['cart'] ?? [];

        $cart[$itemId] = ($cart[$itemId] ?? 0) + 1;

        $this->stateManager->updateContext($guest, 'cart', $cart);

        $menuItem = MenuItem::find($itemId);

        $this->whatsappService->sendButtonMessage(
            $guest->phone_number,
            "Added {$menuItem->name} to your order! 🛒\n\nWhat would you like to do next?",
            [
                ['id' => 'add_more', 'title' => 'Add More Items'],
                ['id' => 'place_order', 'title' => 'Place Order'],
                ['id' => 'view_cart', 'title' => 'View Cart'],
            ]
        );
    }

    /**
     * Handle order placed state
     */
    protected function handleOrderPlaced(Guest $guest, array $messageData): void
    {
        $this->whatsappService->sendTextMessage(
            $guest->phone_number,
            "Your order is being prepared! 👨‍🍳\n\nWe'll notify you when it's ready."
        );
    }

    /**
     * Handle dining state
     */
    protected function handleDining(Guest $guest, array $messageData): void
    {
        $text = strtolower($messageData['text'] ?? '');

        if (str_contains($text, 'bill') || str_contains($text, 'pay')) {
            $this->stateManager->setState($guest, 'BILLING');
            $this->sendBill($guest);
        } else {
            $this->whatsappService->sendButtonMessage(
                $guest->phone_number,
                'Enjoying your meal? 😊',
                [
                    ['id' => 'request_bill', 'title' => 'Request Bill'],
                    ['id' => 'order_more', 'title' => 'Order More'],
                ]
            );
        }
    }

    /**
     * Handle billing state
     */
    protected function handleBilling(Guest $guest, array $messageData): void
    {
        $this->whatsappService->sendTextMessage(
            $guest->phone_number,
            'Your bill is ready! Your waiter will assist you with payment.'
        );
    }

    /**
     * Send bill to guest
     */
    protected function sendBill(Guest $guest): void
    {
        // Get active session and orders
        $session = $this->sessionService->getActiveSession($guest);

        if ($session) {
            $summary = $this->sessionService->getSessionSummary($session);

            $billText = "📄 Your Bill\n\n";
            foreach ($summary['orders'] as $order) {
                $billText .= "Order #{$order['order_id']}: TZS ".number_format($order['total_amount'], 0)."\n";
            }
            $billText .= "\nTotal: TZS ".number_format($summary['financial_summary']['total_spent'], 0);

            $this->whatsappService->sendTextMessage($guest->phone_number, $billText);
        }
    }

    /**
     * Handle unknown state
     */
    protected function handleUnknownState(Guest $guest, array $messageData): void
    {
        $this->whatsappService->sendTextMessage(
            $guest->phone_number,
            "I'm not sure what you mean. Type 'help' for assistance."
        );
    }

    /**
     * Send category items
     */
    protected function sendCategoryItems(Guest $guest, string $categoryId): void
    {
        // Implementation for sending items in a specific category
        $this->sendMenuCategories($guest);
    }
}
