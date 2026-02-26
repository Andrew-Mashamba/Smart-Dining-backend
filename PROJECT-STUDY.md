# Smart Dining (SeaCliff POS) — Full Project Study

**Last updated:** February 2025  
**Workspace:** `/Volumes/DATA/PROJECTS/HOSPITALITYSYSTEM/laravel-app`

---

## 1. What This Project Is

**Smart Dining (SeaCliff POS)** is a **Laravel 11** backend for a restaurant management and point-of-sale system. It serves:

- **Android POS app** (waiters) — via REST API + Sanctum
- **Web portals** — Kitchen Display (KDS), Bar Display, Manager Dashboard
- **Guest ordering** — QR-code table ordering and **WhatsApp** ordering (with AI/conversation flows)
- **Payments** — Cash, card (Stripe), mobile (M-Pesa), payment links (tokenised URLs for WhatsApp)
- **Proposals** — PDF proposals (e.g. NBC Bank POS, Cape Classique) via DomPDF

**Stack:** Laravel 11, PHP 8.2+, MySQL 8.0, Livewire 3.x, Alpine.js, Tailwind, Laravel Reverb (WebSockets), Sanctum, Jetstream (auth), DomPDF, WhatsApp Cloud API, Stripe, Firebase (FCM), Telescope, Sentry.

---

## 2. High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Clients: Android POS | Web (Kitchen/Bar/Manager) | Guest QR | WhatsApp │
└─────────────────────────────────────────────────────────────────────────┘
                    │                    │                    │
                    ▼                    ▼                    ▼
            routes/api.php         routes/web.php      webhooks (WhatsApp, Stripe)
                    │                    │                    │
                    └────────────────────┼────────────────────┘
                                         ▼
                    ┌────────────────────────────────────────┐
                    │  Controllers (Api/, Web/, WhatsApp/)    │
                    │  Livewire components (Dashboard, etc.)│
                    └────────────────────────────────────────┘
                                         │
                    ┌────────────────────┼────────────────────┐
                    ▼                    ▼                    ▼
              Services/           Models (Eloquent)      Events / Listeners
         (Order, Payment, WhatsApp, etc.)                      │
                                         │                    ▼
                                         │              Reverb (WebSockets)
                                         ▼
                    MySQL | Redis (cache/queue) | Storage
```

- **API** (`/api/*`): Used by Android POS; auth via `auth:sanctum`; role-based middleware `api.role:waiter,chef,bartender,manager,admin`.
- **Web** (`/`): Session auth (`auth:web`), role middleware `role:admin,manager,...`. Livewire for dashboard, orders, menu, tables, staff, reports, inventory, settings, proposals.
- **Webhooks**: `/webhooks/whatsapp` (verify + handle), `/webhooks/stripe` (payment events). Public.
- **Guest**: `/guest/order` (GuestOrder Livewire), `/pay/{token}` (payment link — public).

---

## 3. Directory Structure (Key Areas)

```
app/
├── Console/Commands/          # e.g. DailySalesSummary, GenerateNbcBankProposalPdf
├── Events/                   # OrderStatusChanged, PaymentReceived, etc.
├── Exceptions/               # OrderWorkflowException, PaymentException, InventoryException
├── Http/
│   ├── Controllers/
│   │   ├── Api/              # Auth, Order, OrderItem, Menu, Table, Payment, Tip, Guest,
│   │   │                     # Reservation, DeviceToken, StaffNotification, Sync, Stripe, Mpesa, WhatsApp API, QRCode
│   │   ├── Web/              # Auth, Manager, Kitchen, Bar, Proposal, StripePaymentWeb
│   │   └── WhatsApp/         # WebhookController, QRCodeController
│   ├── Middleware/            # Role checks, etc.
│   ├── Requests/             # Validation (Login, UpdateOrder, UpdateOrderStatus)
│   └── Resources/            # API transformers (Order, Table, Guest, etc.)
├── Jobs/                     # Background jobs
├── Livewire/                 # Dashboard, OrdersList, CreateOrder, OrderDetails, ProcessPayment,
│                             # KitchenDisplay, BarDisplay, MenuManagement, TableManagement,
│                             # StaffManagement, InventoryManagement, SalesReports, StaffReports,
│                             # GuestManagement, PromotionsManagement, FeedbackManagement,
│                             # SettingsManagement, Users
├── Models/                    # User, Staff, Guest, Table, MenuItem, MenuCategory, Order, OrderItem,
│                             # Payment, Tip, GuestSession, Reservation, WhatsAppSession, GuestConversation,
│                             # InventoryTransaction, OrderStatusLog, AuditLog, ErrorLog, Setting,
│                             # DeviceToken, Feedback, Promotion, Proposal
├── Observers/                 # OrderObserver
├── Services/
│   ├── Menu/                 # MenuService
│   ├── OrderManagement/      # OrderService, OrderDistributionService
│   ├── Payment/              # PaymentService, TipService, MpesaService, StripePaymentService
│   ├── WhatsApp/             # WhatsAppService, FlowManager, ConversationManager, MessageHandler,
│   │                         # MenuBot, ReservationBot, NotificationBot, PaymentBot,
│   │                         # AiAgentService, GuestMemoryService, QRCodeService, StateManager
│   ├── GuestSession/         # SessionService
│   ├── Notification/         # (if any)
│   ├── OrderWorkflowService.php
│   ├── FcmService.php
│   └── QRCodeService.php (legacy/duplicate path)
├── Listeners/                # SendOrderNotification, etc.
└── Providers/                # App, Auth, Event, Fortify, Jetstream, Telescope
```

---

## 4. Database (Core Tables)

| Table | Purpose |
|-------|---------|
| **users** | Laravel/Jetstream auth (admin/manager web login; may link to staff) |
| **staff** | Restaurant staff: name, email, role (waiter, chef, bartender, manager, admin), phone_number, status, pin (for POS quick login) |
| **guests** | Customers: phone_number (WhatsApp ID), name, loyalty_points, preferences (JSON), first_visit_at, last_visit_at |
| **tables** | Tables: name (T0001, OT001, BT01), location, capacity, status (available, occupied, reserved), qr_code |
| **menu_categories** | Categories: name, description, display_order, status |
| **menu_items** | Menu: category_id, name, description, price, prep_area (kitchen/bar/both), prep_time_minutes, status (available/unavailable), stock_quantity, unit, low_stock_threshold |
| **orders** | Orders: order_number (ORD-YYYYMMDD-####), table_id, guest_id, waiter_id, order_source (pos/whatsapp/web), order_type (dine_in/takeaway/delivery), status (pending→preparing→ready→delivered→paid|cancelled), subtotal, tax, total, special_instructions, delivery_address, delivery_phone, estimated_ready_at |
| **order_items** | Line items: order_id, menu_item_id, quantity, unit_price, subtotal, special_instructions, prep_status (pending→received→preparing→ready) |
| **payments** | Payments: order_id, payment_method (cash/card/mobile/gateway), amount, status (pending/completed/failed/refunded), transaction_id, token (for payment links), completed_at |
| **tips** | Tips: order_id, waiter_id, amount, tip_method (cash/card) |
| **reservations** | Reservations: reference_number, guest_id, table_id, reservation_date, reservation_time, party_size, location, status, special_requests, source (e.g. whatsapp) |
| **guest_sessions** | QR table sessions (guest at table) |
| **whatsapp_sessions** | WhatsApp conversation state: phone_number, state, data (JSON), guest_id, current_order_id, current_table_id, last_activity_at |
| **guest_conversations** | Chat history: guest_id, role (user/assistant), content, message_type (text/order/reservation/feedback), metadata |
| **inventory_transactions** | Stock: menu_item_id, transaction_type (restock/sale/adjustment/waste), quantity, unit, notes, created_by |
| **order_status_logs** | Order status change history |
| **audit_logs** | Audit trail |
| **notifications** | In-app staff notifications |
| **device_tokens** | FCM tokens for push (waiter notifications) |
| **settings** | Key-value app settings (business_name, tax_rate, opening_hours, etc.) |
| **error_logs** | Error tracking |
| **feedback** | Guest feedback |
| **promotions** | Promotions |
| **proposals** | Stored proposals (e.g. for PDF download) |

---

## 5. Order Flow

1. **Create** — POS, WhatsApp, or Web. `order_source`: `pos` | `whatsapp` | `web`. `order_type`: `dine_in` | `takeaway` | `delivery`. Status starts as `pending`.
2. **Prep** — Kitchen/Bar see items (by `prep_area`). Staff mark items: `received` → `preparing` → `ready`. Order status can move to `preparing` / `ready` when all items are ready.
3. **Serve** — Waiter marks order `delivered` (or equivalent).
4. **Pay** — Payment created (cash/card/mobile/gateway). On success, order can move to `paid`. Tips can be recorded.
5. **Cancel** — Managers/admins can cancel; order status `cancelled`.

Order number format: `ORD-YYYYMMDD-####` (generated in `Order::boot()` after create).

---

## 6. WhatsApp Integration

- **Entry:** Meta WhatsApp Cloud API webhook at `GET/POST /webhooks/whatsapp`. Incoming messages are handled by `WhatsApp\WebhookController`.
- **State:** Per-phone state stored in `whatsapp_sessions` (and/or in-memory); `FlowManager` routes by state (e.g. MAIN_MENU, ordering, reservation, payment).
- **Bots/Services:** `FlowManager`, `ConversationManager`, `MenuBot`, `ReservationBot`, `NotificationBot`, `PaymentBot`, `AiAgentService`, `GuestMemoryService`, `QRCodeService`. `MessageHandler` processes raw webhook payloads.
- **Guests:** Identified by phone; create/find `Guest` and attach to session. Conversation history in `guest_conversations`.
- **Orders:** WhatsApp orders create `Order` with `order_source = 'whatsapp'`. Cart/order built in flow; order persisted via API or internal service calls.
- **Payment link:** When guest chooses to pay, a payment record with a unique `token` is created; guest gets URL `/pay/{token}`. Page uses Stripe (card) or other methods; webhook/confirm updates payment and notifies guest (e.g. via WhatsApp).

---

## 7. Payments

- **PaymentService** — Core: create payment, confirm, refund; bill generation; tip suggestions.
- **StripePaymentService** — Stripe PaymentIntent for card. Used by API (`payments/stripe/create-intent`, `confirm`) and by payment link page (`PaymentLinkController`).
- **StripeWebhookController** — Handles Stripe events (e.g. payment_intent.succeeded) for idempotent status updates.
- **MpesaService** — M-Pesa integration; `MpesaWebhookController` for callbacks.
- **Payment link:** `Payment` has `token` and optional `token_expires_at`. `GET /pay/{token}` shows payment page; `POST` processes payment (Stripe card, etc.). Used heavily for WhatsApp “pay by link” flow.

---

## 8. API Summary (Key Endpoints)

- **Auth:** `POST auth/login`, `POST auth/login-pin`, `GET auth/staff-list`; protected: `POST auth/logout`, `GET auth/me`, `POST auth/set-pin`, `POST staff/{id}/pin` (manager).
- **Menu:** `GET menu`, `GET menu/items`, `GET menu/categories`, `GET menu/popular`, `GET menu/search`, `GET menu/{id}`; manager: `PUT menu/{id}/availability`, `GET menu/stats`.
- **Orders:** `POST orders` (create, public); protected: `GET orders`, `GET orders/{id}`, `GET orders/{id}/receipt`, `GET orders/{orderId}/bill`, `POST orders/{id}/items`, `POST orders/{id}/serve`, `PATCH orders/{id}/status`, `POST orders/{id}/cancel`.
- **Order items (KDS/Bar):** `GET order-items/pending`, `POST order-items/{id}/received`, `POST order-items/{id}/done`.
- **Tables:** `GET tables`, `GET tables/{id}`, `PATCH tables/{id}/status`.
- **Payments:** `POST payments`, `POST payments/{id}/confirm`, `POST payments/stripe/create-intent`, `POST payments/stripe/confirm`, `GET payments`, `GET payments/{id}`.
- **Tips:** `POST tips`, `GET orders/{orderId}/tip-suggestions`.
- **Guests:** `GET guests/phone/{phone}`, `POST guests`.
- **Reservations:** `GET/POST reservations`, `GET available-slots`, `GET/PATCH reservations/{id}`.
- **Device tokens:** `POST/DELETE device-tokens` (FCM).
- **Notifications:** `GET notifications`, `GET notifications/unread-count`, `POST notifications/{id}/read`, `POST notifications/read-all`.
- **QR:** `GET/POST qr-codes/tables/{tableId}`, `POST qr-codes/generate-all`.

---

## 9. Web / Livewire Routes (Authenticated)

- **Dashboard** — `/dashboard` (Dashboard).
- **Orders** — `/orders` (OrdersList), `/orders/create` (CreateOrder), `/orders/{order}` (OrderDetails), `/orders/{order}/payment` (ProcessPayment).
- **Kitchen / Bar** — `/kitchen` (KitchenDisplay), `/bar` (BarDisplay). Also legacy `/kitchen/display`, `/bar/display` with Kitchen/BarController.
- **Manager** — `/manager/dashboard`, `/manager/orders/{orderId}/receipt`.
- **Menu, Tables, Staff, Guests** — `/menu`, `/tables`, `/staff`, `/guests` (Livewire: MenuManagement, TableManagement, StaffManagement, GuestManagement).
- **Reports** — `/reports`, `/reports/sales`, `/reports/staff`, `/reports/inventory`.
- **Inventory** — `/inventory` (InventoryManagement).
- **Promotions, Feedback, Settings** — `/promotions`, `/feedback`, `/settings`.
- **Users** — `/users` (admin/manager).
- **Proposals** — `/proposals` (index, create, store, show, edit, update, download PDF).
- **Help** — `/help`, `/help/{filename}`, `/help/{filename}/pdf`.
- **Stripe (web)** — `/payments/stripe/{order}`, `/payments/stripe/success`.

---

## 10. Real-Time & Notifications

- **Laravel Reverb** — WebSockets for live updates (e.g. kitchen/bar/manager).
- **Events** — e.g. OrderStatusChanged, PaymentReceived; listeners (SendOrderNotification) and Reverb channels.
- **FCM** — `FcmService` + `DeviceToken` for push to waiters (order ready, tip received). API: `device-tokens`, `notifications`.

---

## 11. Proposals & PDFs

- **ProposalController** (Web) — CRUD proposals; download PDF.
- **Proposal model** — Stored in DB; PDF generated (e.g. DomPDF).
- **NBC Bank POS proposal** — Blade view `resources/views/pdf/proposal-nbc-bank-pos.blade.php`; command `php artisan proposal:nbc-bank-pdf` → `pdf/My-NBC-Proposal.pdf`.
- **Cape Classique / ZIMA** — Branding (blue #1e3a8a, yellow #ca8a04) used in proposal views.

---

## 12. Configuration & Conventions

- **Currency:** TZS. **Tax:** 18% (configurable via Settings).
- **Table naming:** Indoor T0001…, Outdoor OT001…, Bar BT01….
- **Prep areas:** `kitchen` | `bar` | `both` — drive which display (KDS vs Bar) sees the item.
- **API response shape:** `{ success: bool, data: mixed, message?: string }`.
- **Auth:** Web = Jetstream/Fortify (users); API = Sanctum (staff tokens). Role checks via middleware.

---

## 13. Testing & Dev

- **PHPUnit** — `tests/Feature/`, `tests/Unit/`; e.g. AuthenticationTest, WhatsApp ChatbotFlowTest, CreateApiTokenTest, ErrorHandlingTest.
- **Test routes** — In non-production: `/test-errors/*` (404, 500, validation, 403, custom exceptions), `/test-errors/logs` (error log JSON). `/test-broadcast` for Reverb.
- **Telescope** — Installed for debugging (requests, jobs, etc.).
- **Pail** — Log tailing. **Sentry** — Error tracking.

---

## 14. File Locations Quick Reference

| What | Where |
|------|--------|
| API routes | `routes/api.php` |
| Web routes | `routes/web.php` |
| Order model & flow | `app/Models/Order.php`, `app/Services/OrderManagement/OrderService.php` |
| WhatsApp webhook | `app/Http/Controllers/WhatsApp/WebhookController.php` |
| WhatsApp flow | `app/Services/WhatsApp/FlowManager.php` + MenuBot, PaymentBot, ReservationBot |
| Payment link | `app/Http/Controllers/PaymentLinkController.php` |
| Stripe | `app/Services/Payment/StripePaymentService.php`, `StripeWebhookController`, `StripePaymentController` (API) |
| KDS / Bar | `app/Livewire/KitchenDisplay.php`, `app/Livewire/BarDisplay.php`; views `resources/views/kitchen/`, `resources/views/livewire/` |
| Manager dashboard | `app/Http/Controllers/Web/ManagerController.php`, Livewire Dashboard |
| Proposals & NBC PDF | `app/Http/Controllers/Web/ProposalController.php`, `resources/views/pdf/proposal-nbc-bank-pos.blade.php`, `app/Console/Commands/GenerateNbcBankProposalPdf.php` |
| Migrations | `database/migrations/` |
| Seeders | `database/seeders/` |

---

This document gives a single reference for the whole project: purpose, architecture, database, order and payment flows, WhatsApp, API and web routes, real-time, proposals, and where to find key code.
