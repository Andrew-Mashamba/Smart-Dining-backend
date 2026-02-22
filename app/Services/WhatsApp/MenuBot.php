<?php

namespace App\Services\WhatsApp;

use App\Events\WhatsAppOrderCreated;
use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Table;
use App\Services\GuestSession\SessionService;
use App\Services\Menu\MenuService;
use App\Services\OrderManagement\OrderDistributionService;
use App\Services\OrderManagement\OrderService;
use Illuminate\Support\Facades\Log;

class MenuBot
{
    protected WhatsAppService $whatsappService;

    protected ConversationManager $conversationManager;

    protected MenuService $menuService;

    protected SessionService $sessionService;

    protected OrderService $orderService;

    protected OrderDistributionService $distributionService;

    public function __construct(
        WhatsAppService $whatsappService,
        ConversationManager $conversationManager,
        MenuService $menuService,
        SessionService $sessionService,
        OrderService $orderService,
        OrderDistributionService $distributionService
    ) {
        $this->whatsappService = $whatsappService;
        $this->conversationManager = $conversationManager;
        $this->menuService = $menuService;
        $this->sessionService = $sessionService;
        $this->orderService = $orderService;
        $this->distributionService = $distributionService;
    }

    /**
     * Start the menu/ordering flow.
     */
    public function start(Guest $guest): void
    {
        $session = $this->conversationManager->getSession($guest->phone_number);

        if ($session->current_table_id) {
            $this->sendMenuCategories($guest);
        } else {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Before ordering, please enter your table number (e.g., Table 1, T0001) or scan the QR code at your table."
            );
            $this->conversationManager->setState($guest->phone_number, 'ORDER_TABLE');
        }
    }

    /**
     * Route to appropriate handler based on state.
     */
    public function handleState(Guest $guest, string $state, array $messageData): void
    {
        match ($state) {
            'ORDER_TABLE' => $this->handleTableSelection($guest, $messageData),
            'ORDER_CATEGORY', 'MENU_BROWSING' => $this->handleCategorySelection($guest, $messageData),
            'ORDER_ITEMS', 'ORDERING' => $this->handleItemSelection($guest, $messageData),
            'ORDER_QUANTITY' => $this->handleQuantitySelection($guest, $messageData),
            'ORDER_SPECIAL' => $this->handleSpecialInstructions($guest, $messageData),
            'ORDER_CART' => $this->handleCartAction($guest, $messageData),
            'ORDER_CONFIRM' => $this->handleOrderConfirm($guest, $messageData),
            default => $this->start($guest),
        };
    }

    /**
     * Handle table number input.
     */
    protected function handleTableSelection(Guest $guest, array $messageData): void
    {
        $text = $messageData['text'] ?? '';

        if (preg_match('/TABLE[_\s]?(\w+)/i', $text, $matches)) {
            $tableCode = $matches[1];
        } elseif (preg_match('/(\d+)/', $text, $matches)) {
            $tableCode = $matches[1];
        } else {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "I couldn't recognize that table number. Please try again (e.g., Table 1, T0001)."
            );

            return;
        }

        $table = Table::where('name', 'LIKE', "%{$tableCode}%")->first();

        if (! $table) {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Sorry, I couldn't find that table. Please check the number and try again."
            );

            return;
        }

        $this->conversationManager->setCurrentTable($guest->phone_number, $table->id);

        // Create guest session if needed
        $activeSession = $this->sessionService->getActiveSession($guest);
        if (! $activeSession) {
            $this->sessionService->startSession($guest, $table);
        }

        $this->whatsappService->sendTextMessage(
            $guest->phone_number,
            "You're at {$table->name}. Let me show you our menu!"
        );

        $this->sendMenuCategories($guest);
    }

    /**
     * Send menu categories as an interactive list.
     */
    public function sendMenuCategories(Guest $guest): void
    {
        $menuByCategory = $this->menuService->getMenuByCategory();

        if (empty($menuByCategory)) {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Sorry, our menu is currently unavailable. Please ask your waiter for assistance."
            );

            return;
        }

        // WhatsApp limits: max 10 sections, max 10 rows per section
        $sections = [];
        $totalRows = 0;

        foreach ($menuByCategory as $category) {
            if (count($sections) >= 10) {
                break;
            }

            $rows = [];
            foreach ($category['items'] as $item) {
                if (count($rows) >= 10) {
                    break;
                }

                $rows[] = [
                    'id' => 'item_'.$item['id'],
                    'title' => substr($item['name'], 0, 24),
                    'description' => 'TZS '.number_format($item['price'], 0),
                ];
                $totalRows++;
            }

            if (! empty($rows)) {
                $sections[] = [
                    'title' => substr(ucfirst($category['category']), 0, 24),
                    'rows' => $rows,
                ];
            }
        }

        $categoryCount = count($sections);
        $this->whatsappService->sendListMessage(
            $guest->phone_number,
            "Here's our menu ({$categoryCount} categories, {$totalRows} items)! Tap below to browse and add items to your order.",
            'View Menu',
            $sections
        );

        $this->conversationManager->setState($guest->phone_number, 'ORDER_ITEMS');
    }

    /**
     * Handle category selection from list.
     */
    protected function handleCategorySelection(Guest $guest, array $messageData): void
    {
        $listId = $messageData['list_id'] ?? null;
        $buttonId = $messageData['button_id'] ?? null;
        $text = strtolower($messageData['text'] ?? '');

        if ($buttonId === 'view_menu' || $text === 'menu') {
            $this->sendMenuCategories($guest);
        } elseif ($listId && str_starts_with($listId, 'item_')) {
            $itemId = (int) str_replace('item_', '', $listId);
            $this->addItemToCart($guest, $itemId);
        } else {
            $this->sendMenuCategories($guest);
        }
    }

    /**
     * Handle item selection from list.
     */
    protected function handleItemSelection(Guest $guest, array $messageData): void
    {
        $listId = $messageData['list_id'] ?? null;
        $buttonId = $messageData['button_id'] ?? null;

        if ($listId && str_starts_with($listId, 'item_')) {
            $itemId = (int) str_replace('item_', '', $listId);
            $this->addItemToCart($guest, $itemId);
        } elseif ($buttonId === 'add_more') {
            $this->sendMenuCategories($guest);
        } elseif ($buttonId === 'place_order') {
            $this->showCartAndConfirm($guest);
        } elseif ($buttonId === 'view_cart') {
            $this->showCart($guest);
        } else {
            $this->sendMenuCategories($guest);
        }
    }

    /**
     * Start adding an item — ask for quantity.
     */
    protected function addItemToCart(Guest $guest, int $itemId): void
    {
        $menuItem = MenuItem::find($itemId);

        if (! $menuItem || $menuItem->status !== 'available') {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Sorry, that item is currently unavailable."
            );

            return;
        }

        // Store pending item in session
        $this->conversationManager->updateSessionData($guest->phone_number, 'pending_item_id', $itemId);

        $this->whatsappService->sendButtonMessage(
            $guest->phone_number,
            "*{$menuItem->name}*\nTZS ".number_format($menuItem->price, 0)."\n\nHow many would you like?",
            [
                ['id' => 'qty_1', 'title' => '1'],
                ['id' => 'qty_2', 'title' => '2'],
                ['id' => 'qty_3', 'title' => '3'],
            ]
        );

        $this->conversationManager->setState($guest->phone_number, 'ORDER_QUANTITY');
    }

    /**
     * Handle quantity selection.
     */
    protected function handleQuantitySelection(Guest $guest, array $messageData): void
    {
        $buttonId = $messageData['button_id'] ?? null;
        $text = trim($messageData['text'] ?? '');

        // Parse quantity from button ID or text
        $quantity = null;
        if ($buttonId && preg_match('/qty_(\d+)/', $buttonId, $m)) {
            $quantity = (int) $m[1];
        } elseif (is_numeric($text) && (int) $text > 0 && (int) $text <= 20) {
            $quantity = (int) $text;
        }

        if (! $quantity) {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Please enter a quantity (1-20) or tap a button."
            );

            return;
        }

        $this->conversationManager->updateSessionData($guest->phone_number, 'pending_quantity', $quantity);

        $sessionData = $this->conversationManager->getSessionData($guest->phone_number);
        $itemId = $sessionData['pending_item_id'] ?? null;
        $menuItem = $itemId ? MenuItem::find($itemId) : null;

        $itemName = $menuItem ? $menuItem->name : 'item';

        $this->whatsappService->sendButtonMessage(
            $guest->phone_number,
            "{$quantity}x {$itemName}\n\nAny special instructions? (e.g., no onions, extra spicy, well done)",
            [
                ['id' => 'no_special', 'title' => 'No, Add to Cart'],
                ['id' => 'add_instructions', 'title' => 'Yes, Add Note'],
            ]
        );

        $this->conversationManager->setState($guest->phone_number, 'ORDER_SPECIAL');
    }

    /**
     * Handle special instructions input.
     */
    protected function handleSpecialInstructions(Guest $guest, array $messageData): void
    {
        $buttonId = $messageData['button_id'] ?? null;
        $text = trim($messageData['text'] ?? '');

        $sessionData = $this->conversationManager->getSessionData($guest->phone_number);
        $itemId = $sessionData['pending_item_id'] ?? null;
        $quantity = $sessionData['pending_quantity'] ?? 1;

        if (! $itemId) {
            $this->sendMenuCategories($guest);

            return;
        }

        $menuItem = MenuItem::find($itemId);
        if (! $menuItem) {
            $this->sendMenuCategories($guest);

            return;
        }

        $specialInstructions = null;
        if ($buttonId === 'no_special') {
            $specialInstructions = null;
        } elseif ($buttonId === 'add_instructions') {
            // Prompt for text input
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Please type your special instructions:"
            );

            return;
        } else {
            // User typed their instructions directly
            $specialInstructions = $text;
        }

        // Add to cart
        $this->addToCartWithDetails($guest, $itemId, $quantity, $specialInstructions);
    }

    /**
     * Add item to cart with quantity and special instructions.
     */
    protected function addToCartWithDetails(Guest $guest, int $itemId, int $quantity, ?string $specialInstructions): void
    {
        $menuItem = MenuItem::find($itemId);
        if (! $menuItem) {
            return;
        }

        $sessionData = $this->conversationManager->getSessionData($guest->phone_number);
        $cart = $sessionData['cart'] ?? [];
        $cartInstructions = $sessionData['cart_instructions'] ?? [];

        // Cart stores quantity per item; if the same item is added again, sum quantities
        $cart[$itemId] = ($cart[$itemId] ?? 0) + $quantity;

        // Store special instructions (append if item already has some)
        if ($specialInstructions) {
            $existing = $cartInstructions[$itemId] ?? '';
            $cartInstructions[$itemId] = $existing
                ? $existing.'; '.$specialInstructions
                : $specialInstructions;
        }

        $this->conversationManager->updateSessionData($guest->phone_number, 'cart', $cart);
        $this->conversationManager->updateSessionData($guest->phone_number, 'cart_instructions', $cartInstructions);

        // Clear pending item data
        $this->conversationManager->updateSessionData($guest->phone_number, 'pending_item_id', null);
        $this->conversationManager->updateSessionData($guest->phone_number, 'pending_quantity', null);

        $cartCount = array_sum($cart);
        $noteText = $specialInstructions ? "\nNote: {$specialInstructions}" : '';

        $this->whatsappService->sendButtonMessage(
            $guest->phone_number,
            "Added {$quantity}x {$menuItem->name} (TZS ".number_format($menuItem->price * $quantity, 0).") to your order!{$noteText}\n\nCart: {$cartCount} item(s)",
            [
                ['id' => 'add_more', 'title' => 'Add More Items'],
                ['id' => 'place_order', 'title' => 'Place Order'],
                ['id' => 'view_cart', 'title' => 'View Cart'],
            ]
        );

        $this->conversationManager->setState($guest->phone_number, 'ORDER_ITEMS');
    }

    /**
     * Show the current cart contents.
     */
    public function showCart(Guest $guest): void
    {
        $sessionData = $this->conversationManager->getSessionData($guest->phone_number);
        $cart = $sessionData['cart'] ?? [];

        if (empty($cart)) {
            $this->whatsappService->sendButtonMessage(
                $guest->phone_number,
                "Your cart is empty. Let's browse the menu!",
                [
                    ['id' => 'add_more', 'title' => 'View Menu'],
                ]
            );

            return;
        }

        $cartInstructions = $sessionData['cart_instructions'] ?? [];
        $cartText = "Your Cart:\n\n";
        $total = 0;

        foreach ($cart as $itemId => $quantity) {
            $item = MenuItem::find($itemId);
            if ($item) {
                $subtotal = $item->price * $quantity;
                $total += $subtotal;
                $cartText .= "{$item->name} x{$quantity} — TZS ".number_format($subtotal, 0)."\n";
                if (! empty($cartInstructions[$itemId])) {
                    $cartText .= "  _Note: {$cartInstructions[$itemId]}_\n";
                }
            }
        }

        $taxRate = Setting::get('tax_rate', 18) / 100;
        $tax = $total * $taxRate;
        $grandTotal = $total + $tax;

        $cartText .= "\nSubtotal: TZS ".number_format($total, 0);
        $cartText .= "\nTax (18%): TZS ".number_format($tax, 0);
        $cartText .= "\nTotal: TZS ".number_format($grandTotal, 0);

        $this->whatsappService->sendButtonMessage(
            $guest->phone_number,
            $cartText,
            [
                ['id' => 'place_order', 'title' => 'Place Order'],
                ['id' => 'add_more', 'title' => 'Add More'],
                ['id' => 'clear_cart', 'title' => 'Clear Cart'],
            ]
        );

        $this->conversationManager->setState($guest->phone_number, 'ORDER_CART');
    }

    /**
     * Handle cart actions (clear, confirm, etc.)
     */
    protected function handleCartAction(Guest $guest, array $messageData): void
    {
        $buttonId = $messageData['button_id'] ?? null;

        match ($buttonId) {
            'place_order' => $this->showCartAndConfirm($guest),
            'add_more' => $this->sendMenuCategories($guest),
            'clear_cart' => $this->clearCart($guest),
            'confirm_order' => $this->confirmOrder($guest),
            'cancel_order' => $this->clearCart($guest),
            default => $this->showCart($guest),
        };
    }

    /**
     * Show cart summary and ask for confirmation.
     */
    protected function showCartAndConfirm(Guest $guest): void
    {
        $sessionData = $this->conversationManager->getSessionData($guest->phone_number);
        $cart = $sessionData['cart'] ?? [];

        if (empty($cart)) {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Your cart is empty. Let's add some items first!"
            );
            $this->sendMenuCategories($guest);

            return;
        }

        $cartInstructions = $sessionData['cart_instructions'] ?? [];
        $cartText = "Order Summary:\n\n";
        $total = 0;

        foreach ($cart as $itemId => $quantity) {
            $item = MenuItem::find($itemId);
            if ($item) {
                $subtotal = $item->price * $quantity;
                $total += $subtotal;
                $cartText .= "{$item->name} x{$quantity} — TZS ".number_format($subtotal, 0)."\n";
                if (! empty($cartInstructions[$itemId])) {
                    $cartText .= "  _Note: {$cartInstructions[$itemId]}_\n";
                }
            }
        }

        $taxRate = Setting::get('tax_rate', 18) / 100;
        $tax = $total * $taxRate;
        $grandTotal = $total + $tax;

        $cartText .= "\nSubtotal: TZS ".number_format($total, 0);
        $cartText .= "\nTax (18%): TZS ".number_format($tax, 0);
        $cartText .= "\nTotal: TZS ".number_format($grandTotal, 0);
        $cartText .= "\n\nConfirm your order?";

        $this->whatsappService->sendButtonMessage(
            $guest->phone_number,
            $cartText,
            [
                ['id' => 'confirm_order', 'title' => 'Confirm Order'],
                ['id' => 'add_more', 'title' => 'Add More'],
                ['id' => 'cancel_order', 'title' => 'Cancel'],
            ]
        );

        $this->conversationManager->setState($guest->phone_number, 'ORDER_CART');
    }

    /**
     * Clear the cart.
     */
    protected function clearCart(Guest $guest): void
    {
        $this->conversationManager->updateSessionData($guest->phone_number, 'cart', []);
        $this->conversationManager->updateSessionData($guest->phone_number, 'cart_instructions', []);

        $this->whatsappService->sendTextMessage(
            $guest->phone_number,
            "Cart cleared! Type 'menu' to browse our menu again."
        );

        $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');
    }

    /**
     * Confirm and create the order.
     */
    protected function confirmOrder(Guest $guest): void
    {
        $session = $this->conversationManager->getSession($guest->phone_number);
        $sessionData = $this->conversationManager->getSessionData($guest->phone_number);
        $cart = $sessionData['cart'] ?? [];

        if (empty($cart)) {
            $this->whatsappService->sendTextMessage($guest->phone_number, "Your cart is empty.");

            return;
        }

        try {
            $cartInstructions = $sessionData['cart_instructions'] ?? [];
            $items = [];
            foreach ($cart as $itemId => $quantity) {
                $menuItem = MenuItem::find($itemId);
                if ($menuItem) {
                    $items[] = [
                        'menu_item_id' => $menuItem->id,
                        'quantity' => $quantity,
                        'unit_price' => $menuItem->price,
                        'special_instructions' => $cartInstructions[$itemId] ?? null,
                    ];
                }
            }

            $orderData = [
                'table_id' => $session->current_table_id,
                'guest_id' => $guest->id,
                'order_source' => 'whatsapp',
                'items' => $items,
            ];

            $order = $this->orderService->createOrder($orderData);
            $this->distributionService->distributeOrder($order);

            // Dispatch WhatsApp-specific order event
            event(new WhatsAppOrderCreated($order, $guest));

            // Update session
            $this->conversationManager->setCurrentOrder($guest->phone_number, $order->id);
            $this->conversationManager->updateSessionData($guest->phone_number, 'cart', []);
            $this->conversationManager->updateSessionData($guest->phone_number, 'cart_instructions', []);

            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Order placed! #{$order->order_number}\n\nYour food is being prepared. We'll notify you when it's ready!\n\nType 'menu' for main menu or 'status' to check your order."
            );

            $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');

            Log::info('WhatsApp order created', [
                'order_id' => $order->id,
                'guest' => $guest->phone_number,
                'items_count' => count($items),
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp order creation failed', [
                'guest' => $guest->phone_number,
                'error' => $e->getMessage(),
            ]);

            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Sorry, we couldn't place your order. Please try again or ask a waiter for help."
            );
        }
    }

    /**
     * Show the guest's current order status.
     */
    public function showCurrentOrder(Guest $guest, array $messageData): void
    {
        $session = $this->conversationManager->getSession($guest->phone_number);

        $order = null;
        if ($session->current_order_id) {
            $order = Order::with('items.menuItem')->find($session->current_order_id);
        }

        if (! $order) {
            $order = Order::where('guest_id', $guest->id)
                ->whereNotIn('status', ['paid', 'cancelled', 'completed'])
                ->latest()
                ->with('items.menuItem')
                ->first();
        }

        if (! $order) {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "You don't have any active orders. Type 'menu' to browse and order!"
            );
            $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');

            return;
        }

        $statusEmoji = match ($order->status) {
            'pending' => '🕐',
            'preparing' => '👨‍🍳',
            'ready' => '✅',
            'delivered', 'served' => '🍽️',
            default => '📋',
        };

        $text = "Order #{$order->order_number}\nStatus: {$statusEmoji} ".ucfirst($order->status)."\n\nItems:\n";

        foreach ($order->items as $item) {
            $prepEmoji = match ($item->prep_status) {
                'pending' => '⏳',
                'received' => '📥',
                'preparing' => '🔥',
                'ready' => '✅',
                default => '•',
            };
            $text .= "{$prepEmoji} {$item->menuItem->name} x{$item->quantity}\n";
        }

        $text .= "\nTotal: TZS ".number_format($order->total, 0);

        $this->whatsappService->sendTextMessage($guest->phone_number, $text);
        $this->conversationManager->setState($guest->phone_number, 'MAIN_MENU');
    }

    /**
     * Handle legacy ORDER_PLACED state.
     */
    public function handleOrderPlaced(Guest $guest, array $messageData): void
    {
        $this->showCurrentOrder($guest, $messageData);
    }

    /**
     * Handle legacy DINING state.
     */
    public function handleDining(Guest $guest, array $messageData): void
    {
        $text = strtolower($messageData['text'] ?? '');
        $buttonId = $messageData['button_id'] ?? null;

        if (str_contains($text, 'bill') || str_contains($text, 'pay') || $buttonId === 'request_bill') {
            $this->conversationManager->setState($guest->phone_number, 'PAYMENT_METHOD');
        } elseif ($buttonId === 'order_more' || str_contains($text, 'order')) {
            $this->sendMenuCategories($guest);
        } else {
            $this->whatsappService->sendButtonMessage(
                $guest->phone_number,
                "What would you like to do?",
                [
                    ['id' => 'request_bill', 'title' => 'Request Bill'],
                    ['id' => 'order_more', 'title' => 'Order More'],
                ]
            );
        }
    }
}
