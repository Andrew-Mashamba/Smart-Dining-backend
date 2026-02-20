# WhatsApp Chatbot Implementation

I want to build a WhatsApp chatbot that will allow customers and guests to do reservations, create orders, view waiter and table information, request waiter (Ring A waiter), Request a manager and do a payment, using Lipa Namba, Pay by link, QR code or choose to pay Via Card or Cash.

---

## Architecture Overview

```
┌──────────────────┐     ┌─────────────────────┐     ┌──────────────────┐
│   WhatsApp       │────▶│   Laravel Backend   │────▶│   Android POS    │
│   Cloud API      │◀────│   (Webhook Handler) │◀────│   (FCM Push)     │
└──────────────────┘     └─────────────────────┘     └──────────────────┘
         │                        │
         │                        ▼
         │               ┌─────────────────────┐
         │               │   Payment Gateways  │
         │               │  • M-Pesa (Lipa Na) │
         │               │  • Stripe (Card)    │
         │               │  • Pesapal          │
         └──────────────▶│  • QR / Pay Links   │
                         └─────────────────────┘
```

---

## 1. WhatsApp Cloud API Setup

Your Laravel app already has the package. You need:

```env
# .env
WHATSAPP_API_TOKEN=your_meta_access_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_BUSINESS_ACCOUNT_ID=your_business_id
WHATSAPP_VERIFY_TOKEN=your_webhook_verify_token
```

**Meta Developer Setup:**
1. Create Meta Business App at developers.facebook.com
2. Add WhatsApp product
3. Configure webhook URL: `https://yourdomain.com/api/webhooks/whatsapp`
4. Subscribe to `messages` webhook field

---

## 2. Chatbot Conversation Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    MAIN MENU                                     │
│  "Welcome to SeaCliff! What would you like to do?"              │
├─────────────────────────────────────────────────────────────────┤
│  1️⃣ Make a Reservation                                          │
│  2️⃣ View Menu & Order                                           │
│  3️⃣ My Current Order                                            │
│  4️⃣ Request Waiter 🔔                                           │
│  5️⃣ Request Manager                                             │
│  6️⃣ Pay My Bill 💳                                              │
│  7️⃣ Table Info                                                  │
└─────────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────┐
│  1️⃣ RESERVATION FLOW                                            │
│  ────────────────────                                           │
│  → How many guests?                                             │
│  → Date? (show date picker)                                     │
│  → Time? (show available slots)                                 │
│  → Indoor/Outdoor/Bar?                                          │
│  → Your name & phone?                                           │
│  → ✅ Confirmed! Reference: RES-20260220-001                    │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  2️⃣ ORDER FLOW                                                  │
│  ────────────────────                                           │
│  → Are you at a table? (Scan QR / Enter table number)           │
│  → Show categories: 🍕 Appetizers | 🍝 Mains | 🍰 Desserts      │
│  → Show items with prices                                       │
│  → Add to cart (quantity, special instructions)                 │
│  → Review cart → Confirm order                                  │
│  → ✅ Order #ORD-20260220-00123 placed!                         │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  6️⃣ PAYMENT FLOW                                                │
│  ────────────────────                                           │
│  → Enter order number or table number                           │
│  → Show bill summary                                            │
│  → Choose payment method:                                       │
│      💚 Lipa Na M-Pesa (STK Push)                               │
│      🔗 Pay by Link                                             │
│      📱 Scan QR Code                                            │
│      💳 Card (Stripe link)                                      │
│      💵 Cash (notify waiter)                                    │
│  → Process payment → ✅ Receipt                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. Implementation Structure

### Directory Structure for WhatsApp Module

```
laravel-app/
├── app/
│   ├── Http/Controllers/WhatsApp/
│   │   ├── WebhookController.php      # Existing - enhance
│   │   ├── MessageHandler.php         # New - route messages
│   │   └── QRCodeController.php       # Existing
│   │
│   ├── Services/WhatsApp/
│   │   ├── WhatsAppService.php        # Send messages, templates
│   │   ├── ConversationManager.php    # Track conversation state
│   │   ├── MenuBot.php                # Handle menu/ordering
│   │   ├── ReservationBot.php         # Handle reservations
│   │   ├── PaymentBot.php             # Handle payments
│   │   └── NotificationBot.php        # Ring waiter/manager
│   │
│   ├── Models/
│   │   ├── WhatsAppSession.php        # Track user sessions
│   │   ├── Reservation.php            # New model
│   │   └── ...
│   │
│   └── Events/
│       ├── WaiterRequested.php        # Broadcast to POS
│       ├── ManagerRequested.php
│       └── WhatsAppOrderCreated.php
```

---

## 4. Key Components to Build

### A. Conversation State Manager

```php
// app/Services/WhatsApp/ConversationManager.php

class ConversationManager
{
    // States
    const STATE_MAIN_MENU = 'main_menu';
    const STATE_RESERVATION_GUESTS = 'reservation.guests';
    const STATE_RESERVATION_DATE = 'reservation.date';
    const STATE_RESERVATION_TIME = 'reservation.time';
    const STATE_ORDER_TABLE = 'order.table';
    const STATE_ORDER_CATEGORY = 'order.category';
    const STATE_ORDER_ITEMS = 'order.items';
    const STATE_ORDER_CART = 'order.cart';
    const STATE_PAYMENT_METHOD = 'payment.method';
    const STATE_PAYMENT_MPESA = 'payment.mpesa';

    public function getSession(string $phoneNumber): WhatsAppSession;
    public function setState(string $phoneNumber, string $state, array $data = []);
    public function getState(string $phoneNumber): string;
    public function getSessionData(string $phoneNumber): array;
    public function clearSession(string $phoneNumber): void;
}
```

### B. WhatsApp Session Model (New)

```php
// Migration
Schema::create('whatsapp_sessions', function (Blueprint $table) {
    $table->id();
    $table->string('phone_number')->unique();
    $table->string('state')->default('main_menu');
    $table->json('data')->nullable();  // Cart, reservation draft, etc.
    $table->foreignId('guest_id')->nullable()->constrained();
    $table->foreignId('current_order_id')->nullable();
    $table->foreignId('current_table_id')->nullable();
    $table->timestamp('last_activity_at');
    $table->timestamps();
});
```

### C. Reservation Model (New)

```php
// Migration
Schema::create('reservations', function (Blueprint $table) {
    $table->id();
    $table->string('reference_number')->unique();
    $table->foreignId('guest_id')->constrained();
    $table->foreignId('table_id')->nullable()->constrained();
    $table->date('reservation_date');
    $table->time('reservation_time');
    $table->integer('party_size');
    $table->string('location')->default('indoor'); // indoor, outdoor, bar
    $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed']);
    $table->text('special_requests')->nullable();
    $table->string('source')->default('whatsapp'); // whatsapp, phone, walk-in
    $table->timestamps();
});
```

---

## 5. Payment Integrations

### A. M-Pesa Lipa Na (STK Push)

```php
// app/Services/Payment/MpesaService.php

class MpesaService
{
    public function initiateSTKPush(string $phone, float $amount, string $reference): array
    {
        // Safaricom Daraja API
        $payload = [
            'BusinessShortCode' => config('mpesa.shortcode'),
            'Password' => $this->generatePassword(),
            'Timestamp' => now()->format('YmdHis'),
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone,  // Customer phone
            'PartyB' => config('mpesa.shortcode'),
            'PhoneNumber' => $phone,
            'CallBackURL' => route('webhooks.mpesa'),
            'AccountReference' => $reference,
            'TransactionDesc' => 'SeaCliff Order Payment'
        ];

        return Http::withToken($this->getAccessToken())
            ->post('https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest', $payload)
            ->json();
    }
}
```

### B. Pay by Link

```php
// Generate payment link
public function generatePaymentLink(Order $order): string
{
    $payment = Payment::create([
        'order_id' => $order->id,
        'amount' => $order->total,
        'method' => 'link',
        'status' => 'pending',
        'token' => Str::random(32)
    ]);

    return url("/pay/{$payment->token}");
}
```

### C. QR Code Payment

```php
// Generate QR with payment link
public function generatePaymentQR(Order $order): string
{
    $paymentUrl = $this->generatePaymentLink($order);

    return QrCode::format('png')
        ->size(300)
        ->generate($paymentUrl);
}
```

---

## 6. Message Handler (Core Router)

```php
// app/Http/Controllers/WhatsApp/MessageHandler.php

class MessageHandler
{
    public function handle(array $message): void
    {
        $phone = $message['from'];
        $text = $message['text']['body'] ?? '';
        $type = $message['type'];

        $session = $this->conversationManager->getSession($phone);
        $state = $session->state;

        // Handle interactive button/list responses
        if ($type === 'interactive') {
            $selection = $message['interactive']['button_reply']['id']
                      ?? $message['interactive']['list_reply']['id'];
            $this->handleInteractiveResponse($phone, $selection, $session);
            return;
        }

        // Route based on current state
        match($state) {
            'main_menu' => $this->handleMainMenu($phone, $text),
            'reservation.guests' => $this->reservationBot->handleGuests($phone, $text),
            'reservation.date' => $this->reservationBot->handleDate($phone, $text),
            'order.table' => $this->menuBot->handleTableSelection($phone, $text),
            'order.category' => $this->menuBot->handleCategorySelection($phone, $text),
            'payment.method' => $this->paymentBot->handleMethodSelection($phone, $text),
            'payment.mpesa' => $this->paymentBot->handleMpesaPhone($phone, $text),
            default => $this->sendMainMenu($phone)
        };
    }

    private function handleMainMenu(string $phone, string $text): void
    {
        match(strtolower(trim($text))) {
            '1', 'reservation', 'reserve' => $this->reservationBot->start($phone),
            '2', 'menu', 'order' => $this->menuBot->start($phone),
            '3', 'my order', 'status' => $this->orderBot->showCurrentOrder($phone),
            '4', 'waiter', 'ring' => $this->notificationBot->ringWaiter($phone),
            '5', 'manager' => $this->notificationBot->requestManager($phone),
            '6', 'pay', 'bill', 'payment' => $this->paymentBot->start($phone),
            '7', 'table', 'info' => $this->infoBot->showTableInfo($phone),
            default => $this->sendMainMenu($phone)
        };
    }
}
```

---

## 7. WhatsApp Message Templates

### Interactive List (Menu Categories)

```php
$this->whatsapp->sendInteractiveList($phone, [
    'header' => 'Our Menu 🍽️',
    'body' => 'Select a category to browse:',
    'button' => 'View Categories',
    'sections' => [
        [
            'title' => 'Food',
            'rows' => [
                ['id' => 'cat_appetizers', 'title' => '🥗 Appetizers'],
                ['id' => 'cat_mains', 'title' => '🍝 Main Courses'],
                ['id' => 'cat_desserts', 'title' => '🍰 Desserts'],
            ]
        ],
        [
            'title' => 'Drinks',
            'rows' => [
                ['id' => 'cat_drinks', 'title' => '🍹 Beverages'],
                ['id' => 'cat_alcohol', 'title' => '🍷 Wines & Spirits'],
            ]
        ]
    ]
]);
```

### Interactive Buttons (Payment Methods)

```php
$this->whatsapp->sendInteractiveButtons($phone, [
    'body' => "Your bill: KES {$order->total}\n\nHow would you like to pay?",
    'buttons' => [
        ['id' => 'pay_mpesa', 'title' => '💚 M-Pesa'],
        ['id' => 'pay_link', 'title' => '🔗 Pay Link'],
        ['id' => 'pay_cash', 'title' => '💵 Cash'],
    ]
]);
```

---

## 8. Ring Waiter / Manager Flow

```php
// app/Services/WhatsApp/NotificationBot.php

class NotificationBot
{
    public function ringWaiter(string $phone): void
    {
        $session = $this->sessionManager->getSession($phone);
        $table = $session->current_table_id
            ? Table::find($session->current_table_id)
            : null;

        if (!$table) {
            $this->whatsapp->sendText($phone,
                "Please tell me your table number (e.g., T0001, OT003):");
            $this->sessionManager->setState($phone, 'notification.table');
            return;
        }

        // Broadcast to Android POS via FCM
        event(new WaiterRequested($table, $session->guest));

        // Also notify assigned waiter directly
        $this->fcm->sendToTopic("table_{$table->id}", [
            'title' => '🔔 Guest Request',
            'body' => "Table {$table->name} is requesting assistance",
            'data' => [
                'type' => 'waiter_request',
                'table_id' => $table->id,
                'guest_phone' => $phone
            ]
        ]);

        $this->whatsapp->sendText($phone,
            "✅ Your waiter has been notified and will be with you shortly!");
    }

    public function requestManager(string $phone): void
    {
        // Similar flow, but notify manager role
        event(new ManagerRequested($table, $session->guest, $reason));

        $this->whatsapp->sendText($phone,
            "✅ A manager has been notified and will attend to you shortly.");
    }
}
```

---

## 9. Implementation Steps

| Phase                  | Tasks                                                 |
|------------------------|-------------------------------------------------------|
| **Phase 1: Foundation**    | WhatsApp webhook setup, session management, main menu |
| **Phase 2: Menu & Orders** | Browse menu, cart, create orders via WhatsApp         |
| **Phase 3: Reservations**  | New reservation system, date/time selection           |
| **Phase 4: Notifications** | Ring waiter, request manager, FCM integration         |
| **Phase 5: Payments**      | M-Pesa STK, Pay links, QR codes, Stripe               |
| **Phase 6: Polish**        | Error handling, conversation recovery, testing        |

---

## 10. API Endpoints to Add

```php
// routes/api.php - New endpoints

// Reservations
Route::prefix('reservations')->group(function () {
    Route::get('/', [ReservationController::class, 'index']);
    Route::post('/', [ReservationController::class, 'store']);
    Route::get('/{id}', [ReservationController::class, 'show']);
    Route::patch('/{id}/status', [ReservationController::class, 'updateStatus']);
    Route::get('/available-slots', [ReservationController::class, 'availableSlots']);
});

// WhatsApp specific
Route::prefix('whatsapp')->group(function () {
    Route::post('/send-menu', [WhatsAppController::class, 'sendMenu']);
    Route::post('/send-bill', [WhatsAppController::class, 'sendBill']);
    Route::post('/send-receipt', [WhatsAppController::class, 'sendReceipt']);
});

// Payment webhooks
Route::post('/webhooks/mpesa', [MpesaWebhookController::class, 'handle']);
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);

// Payment links
Route::get('/pay/{token}', [PaymentLinkController::class, 'show']);
Route::post('/pay/{token}', [PaymentLinkController::class, 'process']);
```

---

## Next Steps

Ready to implement:

1. **Phase 1**: Set up the WhatsApp conversation manager and session handling
2. **The Reservation system**: New model, migration, and API
3. **M-Pesa integration**: Lipa Na M-Pesa STK push
4. **The complete WhatsApp bot**: All conversation flows
