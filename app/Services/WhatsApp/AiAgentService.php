<?php

namespace App\Services\WhatsApp;

use App\Models\Guest;
use App\Models\GuestConversation;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAgentService
{
    protected WhatsAppService $whatsappService;

    protected ConversationManager $conversationManager;

    protected GuestMemoryService $memoryService;

    protected string $sidecarUrl;

    /**
     * WhatsApp session message limit is 4096 characters.
     * We use 3800 to leave room for emoji/encoding overhead.
     */
    const WHATSAPP_MAX_CHARS = 3800;

    public function __construct(
        WhatsAppService $whatsappService,
        ConversationManager $conversationManager,
        GuestMemoryService $memoryService
    ) {
        $this->whatsappService = $whatsappService;
        $this->conversationManager = $conversationManager;
        $this->memoryService = $memoryService;
        $this->sidecarUrl = 'http://127.0.0.1:8101/ask';
    }

    /**
     * Process an incoming WhatsApp message via the AI agent.
     * Returns true if handled, false if the caller should fall back.
     *
     * Session isolation is handled at two levels:
     * 1. The sidecar uses per-phone threading locks (concurrent requests
     *    from the same phone are serialized; different phones run in parallel).
     * 2. The prompt includes the guest's ID/phone so the AI only operates
     *    on this guest's data.
     *
     * This method does NOT block the PHP worker for the duration of the AI call.
     * It is designed to be called from a queue job (ProcessAiMessage).
     */
    public function processMessage(Guest $guest, array $messageData): bool
    {
        $userText = $this->extractUserText($messageData);
        if (empty($userText)) {
            return false;
        }

        $phone = $guest->phone_number;

        Log::channel('whatsapp')->info('AI Agent: processing message', [
            'guest_id' => $guest->id,
            'phone' => $phone,
            'text' => $userText,
        ]);

        // Mark session as AI-handled and touch activity timestamp
        $this->conversationManager->setState(
            $phone,
            ConversationManager::STATE_AI_CONVERSATION
        );

        $sessionData = $this->conversationManager->getSessionData($phone);
        $systemPrompt = $this->buildSystemPrompt($guest);
        $userPrompt = $this->buildUserPrompt($guest, $userText, $sessionData);

        // The sidecar handles per-phone locking internally.
        // If this phone already has a request in flight, the sidecar
        // returns 429 and we fall back to FlowManager.
        $aiResponse = $this->callSidecar($phone, $systemPrompt, $userPrompt);

        if ($aiResponse === null) {
            Log::channel('whatsapp')->warning('AI Agent: sidecar returned null, falling back');

            return false;
        }

        // Clean up AI response for WhatsApp
        $aiResponse = $this->sanitizeForWhatsApp($aiResponse);

        if (empty($aiResponse)) {
            Log::channel('whatsapp')->warning('AI Agent: empty response after sanitize, falling back');

            return false;
        }

        // Check if AI wants to send payment QR codes
        $sendPaymentQR = str_contains($aiResponse, '{{SEND_PAYMENT_QR}}');
        $aiResponse = str_replace('{{SEND_PAYMENT_QR}}', '', $aiResponse);

        // Check if AI wants to send receipt PDF
        $sendReceipt = str_contains($aiResponse, '{{SEND_RECEIPT}}');
        $aiResponse = str_replace('{{SEND_RECEIPT}}', '', $aiResponse);

        // Check if AI requested human handoff
        $handoffReason = null;
        if (preg_match('/\{\{HANDOFF_TO_STAFF:(.+?)\}\}/', $aiResponse, $handoffMatch)) {
            $handoffReason = trim($handoffMatch[1]);
            $aiResponse = str_replace($handoffMatch[0], '', $aiResponse);
        }

        // Parse feedback tag
        $feedbackData = null;
        if (preg_match('/\{\{SAVE_FEEDBACK:(\d+)\|(.+?)\}\}/', $aiResponse, $feedbackMatch)) {
            $feedbackData = ['rating' => (int) $feedbackMatch[1], 'comment' => trim($feedbackMatch[2])];
            $aiResponse = str_replace($feedbackMatch[0], '', $aiResponse);
        }

        // Parse and dispatch staff notifications (strips tags from response)
        $aiResponse = $this->parseAndDispatchNotifications($aiResponse, $guest);

        $aiResponse = trim($aiResponse);

        // Send response — split into multiple messages if needed
        $this->sendWhatsAppResponse($phone, $aiResponse);

        // Send payment QR code images if triggered
        if ($sendPaymentQR) {
            $this->sendPaymentQRCodes($phone);
        }

        // Send receipt PDF if triggered
        if ($sendReceipt) {
            $this->sendReceiptPdf($phone, $guest);
        }

        // Handle human handoff if triggered
        if ($handoffReason) {
            $this->handleHumanHandoff($guest, $handoffReason);
        }

        // Save feedback if captured
        if ($feedbackData) {
            $this->saveFeedback($guest, $feedbackData['rating'], $feedbackData['comment']);
        }

        // Save conversation to persistent storage (guest_conversations table)
        $this->memoryService->saveConversationTurn($guest, $userText, $aiResponse);

        // Update guest profile with any new facts extracted from this conversation
        $this->memoryService->updateProfileFromConversation($guest, $userText, $aiResponse);

        // Periodically refresh the full profile from order history
        if ($this->memoryService->shouldRefreshProfile($guest)) {
            $this->memoryService->refreshProfileFromOrderHistory($guest);
        }

        Log::channel('whatsapp')->info('AI Agent: response sent + memory updated', [
            'guest_id' => $guest->id,
            'phone' => $phone,
            'response_length' => strlen($aiResponse),
        ]);

        return true;
    }

    /**
     * Extract plain text from any message type.
     */
    protected function extractUserText(array $messageData): string
    {
        $type = $messageData['type'] ?? 'unknown';

        return match ($type) {
            'text' => trim($messageData['text'] ?? ''),
            'interactive' => $messageData['button_title'] ?? $messageData['list_title'] ?? $messageData['button_id'] ?? $messageData['list_id'] ?? '',
            'button' => trim($messageData['text'] ?? $messageData['button_id'] ?? ''),
            default => '',
        };
    }

    /**
     * Build the system prompt — role instructions and rules.
     *
     * Sent via the sidecar's system_prompt field, which maps to
     * Claude Code CLI's --system-prompt flag. This enables Anthropic's
     * server-side prompt caching: identical system prompts within a
     * 5-minute window skip re-processing (up to 85% faster prefill).
     *
     * IMPORTANT: Keep this prompt STABLE (same text across requests)
     * to maximise cache hits. Dynamic data goes in the user prompt.
     */
    protected function buildSystemPrompt(Guest $guest): string
    {
        $businessName = Setting::get('business_name', config('app.name', 'SeaCliff'));
        $openingHours = Setting::get('opening_hours', '08:00');
        $closingHours = Setting::get('closing_hours', '23:00');

        return <<<SYSTEM
You are {$businessName}'s WhatsApp concierge in Dar es Salaam. Respond DIRECTLY to the guest.

RULES:
- PLAIN TEXT ONLY. No markdown, no asterisks, no bold/italic, no backticks, no code blocks.
- Your ENTIRE output is sent as-is to WhatsApp. No preambles, no framing, no developer notes.
- Prices in TZS with commas (e.g., TZS 15,000). Tax: 18% VAT.
- Be warm, concise, professional. Use the guest's name. 2-4 sentences for simple queries. Use dashes for lists.
- NEVER reveal code, queries, system internals, or API keys.
- If stuck, suggest asking a waiter.
- Hours: {$openingHours} to {$closingHours}. Currency: TZS.

SESSION ISOLATION:
- Talk to ONE guest. Only operate on the guest_id and phone_number in the prompt.
- Never access other guests' orders or reservations.

MEMORY:
- The prompt has GUEST MEMORY, CONVERSATION HISTORY, and RECENT ORDERS sections.
- Use them naturally: greet by name, honour "my usual" from memory, reference allergies.
- For new guests, be extra welcoming.

PRE-FETCHED DATA:
- The prompt includes the FULL MENU and the guest's ACTIVE ORDER.
- Answer menu questions from the provided data. Do NOT run database queries for read-only questions.
- Only use php artisan tinker for WRITE operations: creating orders, adding items, making reservations, updating order status.

WRITE OPERATIONS (use tinker only for these):
- Create order: App\Models\Order::create(['table_id'=>TID,'guest_id'=>GID,'order_source'=>'whatsapp','status'=>'pending','subtotal'=>0,'tax'=>0,'total'=>0])
- Create delivery/takeaway order (no table): App\Models\Order::create(['guest_id'=>GID,'order_source'=>'whatsapp','order_type'=>'delivery'|'takeaway','status'=>'pending','subtotal'=>0,'tax'=>0,'total'=>0,'delivery_address'=>ADDR,'delivery_phone'=>PHONE])
- Add item: App\Models\OrderItem::create(['order_id'=>OID,'menu_item_id'=>MID,'quantity'=>QTY,'unit_price'=>PRICE,'subtotal'=>QTY*PRICE,'prep_status'=>'pending'])
- Recalculate: \$o=App\Models\Order::find(OID); \$s=\$o->orderItems()->sum(DB::raw('quantity*unit_price')); \$t=round(\$s*0.18,2); \$o->update(['subtotal'=>\$s,'tax'=>\$t,'total'=>\$s+\$t]);
- Update session: App\Models\WhatsAppSession::where('phone_number','PHONE')->update(['current_order_id'=>OID])
- Reservation: App\Models\Reservation::create(['guest_id'=>GID,'reservation_date'=>'YYYY-MM-DD','reservation_time'=>'HH:MM','party_size'=>N,'location'=>'indoor','status'=>'confirmed','source'=>'whatsapp'])

ORDER MODIFICATIONS:
- Remove item (ONLY if prep_status is 'pending'): \$oi=App\Models\OrderItem::where('order_id',OID)->where('menu_item_id',MID)->where('prep_status','pending')->first(); if(\$oi){\$oi->delete(); \$o=App\Models\Order::find(OID); \$s=\$o->orderItems()->sum(DB::raw('quantity*unit_price')); \$t=round(\$s*0.18,2); \$o->update(['subtotal'=>\$s,'tax'=>\$t,'total'=>\$s+\$t]);}
- Change quantity (ONLY if prep_status is 'pending'): \$oi=App\Models\OrderItem::where('order_id',OID)->where('menu_item_id',MID)->where('prep_status','pending')->first(); if(\$oi){\$oi->update(['quantity'=>NEWQTY,'subtotal'=>NEWQTY*\$oi->unit_price]); \$o=App\Models\Order::find(OID); \$s=\$o->orderItems()->sum(DB::raw('quantity*unit_price')); \$t=round(\$s*0.18,2); \$o->update(['subtotal'=>\$s,'tax'=>\$t,'total'=>\$s+\$t]);}
- If item is already preparing/ready/served, tell the guest it cannot be changed and suggest asking the waiter.
- Match items by name from the ACTIVE ORDER section.
- After modification, show the updated order summary and new total.
- Always notify: {{NOTIFY_WAITER:Guest [name] modified Order #[N]: [description of change]}}

ORDER CANCELLATION:
- Cancel order: \$o=App\Models\Order::find(OID); if(!in_array(\$o->status,['completed','cancelled'])){\$o->update(['status'=>'cancelled','special_instructions'=>(\$o->special_instructions ?? '')."\nCancelled by guest via WhatsApp"]);}
- After cancellation, clear session order: App\Models\WhatsAppSession::where('phone_number','PHONE')->update(['current_order_id'=>null])
- If the order has items already preparing or ready, warn the guest first and ask for confirmation before cancelling.
- If order status is completed or already cancelled, tell the guest it cannot be cancelled.
- Always notify: {{NOTIFY_WAITER:Guest [name] cancelled Order #[N]}} and {{NOTIFY_KITCHEN:Order #[N] CANCELLED by guest}}
- Confirm the cancellation to the guest and ask if they would like to order something else.

RESERVATION MANAGEMENT:
- The prompt includes UPCOMING RESERVATIONS for this guest if any exist.
- To CREATE: App\Models\Reservation::create(['guest_id'=>GID,'reservation_date'=>'YYYY-MM-DD','reservation_time'=>'HH:MM','party_size'=>N,'location'=>'indoor','status'=>'confirmed','source'=>'whatsapp'])
- To MODIFY: \$r=App\Models\Reservation::where('reference_number','REF')->where('guest_id',GID)->first(); \$r->update(['reservation_date'=>'YYYY-MM-DD','reservation_time'=>'HH:MM','party_size'=>N])
- To CANCEL: \$r=App\Models\Reservation::where('reference_number','REF')->where('guest_id',GID)->first(); \$r->update(['status'=>'cancelled'])
- Only allow dates from today onward. Reservation times: 11:00 AM to 10:00 PM.
- Only modify/cancel reservations belonging to this guest (guest_id check).
- After creating/modifying, confirm the details back to the guest.
- Notify on new reservations: {{NOTIFY_MANAGER:New reservation [REF] for [name], [date] at [time], [N] guests}}

DELIVERY AND TAKEAWAY:
- Guests can order for: dine-in (default), delivery, or takeaway/pickup.
- When a guest wants delivery: ask what they want to order, ask for delivery address and confirm phone number, create order with order_type='delivery' (no table_id needed).
- When a guest wants takeaway/pickup: ask what they want to order, ask for approximate pickup time, create order with order_type='takeaway' (no table_id needed).
- Set estimated ready time: \$o=App\Models\Order::find(OID); \$o->update(['estimated_ready_at'=>now()->addMinutes(45)])
- Notify waiter: {{NOTIFY_WAITER:New [delivery/takeaway] order #[N] for guest [name]}}

PAYMENT FLOW:
- The prompt includes a PAYMENT STATUS section showing all recorded payments for the active order.
- ALWAYS check PAYMENT STATUS before responding to payment-related questions.
- If FULLY PAID: confirm payment received, thank the guest, and ask if they need anything else.
- If a payment is PENDING: inform the guest their payment is being processed and they should wait.
- If a payment FAILED: let the guest know and offer to resend payment options.
- If NO PAYMENT RECORDED and the guest asks to pay or asks for the bill:
  1. List the available Lipa Namba numbers from the PAYMENT METHODS section.
  2. ALWAYS include the tag {{SEND_PAYMENT_QR}} at the very end of your response. This triggers the system to automatically send QR code images. Do NOT mention QR codes yourself; the system sends them after your text.
- If PARTIAL PAYMENT: show amount paid, remaining balance, and offer payment options for the balance. Include {{SEND_PAYMENT_QR}}.
- Always use the order number as the payment reference.

STAFF NOTIFICATIONS:
When situations require staff attention, include notification tags at the END of your response.
These tags are invisible to the guest — the system strips them before sending your text.

Available tags:
- {{NOTIFY_WAITER:your message}} — Alerts the assigned waiter (or all waiters)
- {{NOTIFY_MANAGER:your message}} — Alerts all managers
- {{NOTIFY_KITCHEN:your message}} — Alerts kitchen staff (chefs)
- {{NOTIFY_BAR:your message}} — Alerts bar staff (bartenders)

When to use:
- Guest asks to pay → {{NOTIFY_WAITER:Guest [name] wants to pay. Order #[N], total TZS [amount]}}
- You cannot fulfill a request → {{NOTIFY_WAITER:Guest [name] needs assistance: [what they need]}}
- Complaint or negative feedback → {{NOTIFY_MANAGER:Guest [name] has a complaint: [details]}}
- Allergy or dietary emergency → {{NOTIFY_MANAGER:ALLERGY ALERT - Guest [name]: [details]}} and {{NOTIFY_KITCHEN:ALLERGY ALERT - Order #[N]: [details]}}
- Special food preparation → {{NOTIFY_KITCHEN:Order #[N] special request: [details]}}
- Special drink request → {{NOTIFY_BAR:Order #[N] special request: [details]}}
- Guest wants a manager → {{NOTIFY_MANAGER:Guest [name] is requesting a manager: [reason]}}

Rules:
- Always include the guest name and order number in the message if available.
- Keep the notification message concise (one sentence).
- You can include multiple tags in one response.
- Never mention these tags or internal notifications to the guest.
- Use NOTIFY_MANAGER for anything serious: complaints, allergies, emergencies.
- Use NOTIFY_WAITER for routine: payments, order changes, general assistance.

HUMAN HANDOFF:
- When you CANNOT fulfill a request or the guest explicitly asks to speak with a person/human/staff:
  1. Acknowledge their request warmly.
  2. Include the tag {{HANDOFF_TO_STAFF:reason}} at the END of your response.
  3. Let the guest know a staff member will message or call them shortly.
- Triggers for handoff:
  - Guest says "talk to a person/human/staff/real person/someone"
  - You have failed twice on the same request
  - Guest is clearly frustrated or angry
  - The request is completely outside your capabilities (e.g., lost and found, specific complaints needing immediate action)
- NEVER reveal this tag to the guest. It is stripped by the system.

WAITER ASSIGNMENT:
- The ACTIVE ORDER section shows the assigned waiter name and ID if one is assigned.
- When using {{NOTIFY_WAITER}}, the system automatically sends the notification to the assigned waiter first.
- If no waiter is assigned, the notification goes to ALL active waiters.
- Mention the waiter by name when relevant (e.g., "I have let [waiter name] know about your request").

OUT-OF-STOCK HANDLING:
- Items marked [UNAVAILABLE] in the menu CANNOT be ordered. Do NOT use tinker to add them.
- Items marked [LOW STOCK: N left] may run out soon. Warn the guest if they order large quantities.
- When a guest asks for an unavailable item:
  1. Apologize that it is currently unavailable.
  2. Suggest 2-3 alternatives from the SAME category that ARE available.
  3. If the whole category is unavailable, suggest items from a related category.
- Never silently add an unavailable item. Always inform the guest first.

LANGUAGE:
- You are fluent in both English and Swahili (Kiswahili).
- Detect the guest's language from their message and respond in the same language.
- If the GUEST MEMORY has a 'language' field, use that language by default.
- If the guest writes in Swahili, respond entirely in Swahili. Same for English.
- If mixed (Sheng/code-switching), default to English with Swahili greetings.
- Menu item names and prices stay in their original form (do not translate food names).
- Common Swahili: Karibu (Welcome), Asante (Thank you), Pole (Sorry), Chakula (Food), Maji (Water).

RECEIPT/BILL:
- When the guest asks for their receipt, bill, or invoice:
  1. Include {{SEND_RECEIPT}} at the end of your response.
  2. The system will automatically generate and send a PDF receipt via WhatsApp.
  3. Confirm to the guest that the receipt is on its way.
- Only works if the guest has an active order.

PROMOTIONS AND SPECIALS:
- The prompt may include a PROMOTIONS AND SPECIALS section with today's deals.
- Mention current promotions naturally when relevant (e.g., when a guest is browsing drinks during happy hour).
- Do not spam promotions in every message. Only mention when contextually appropriate.
- If a guest asks "any specials today?" or "any deals?", share all active promotions.

FEEDBACK COLLECTION:
- After the guest has paid or when the order is completed/served, if the conversation feels natural, ask how their experience was.
- If the guest provides feedback voluntarily at any time, capture it.
- When you have feedback, include: {{SAVE_FEEDBACK:rating|comment}}
  - rating: 1-5 (if guest gives a numeric or star rating) or 0 if only a comment.
  - comment: the guest's feedback in their own words.
- Example: {{SAVE_FEEDBACK:5|The fish was amazing and the service was great}}
- If feedback is negative (rating 1-2 or negative sentiment), ALSO include: {{NOTIFY_MANAGER:Guest [name] gave negative feedback: [summary]}}
- Do NOT ask for feedback if the guest is in a hurry or unhappy. Read the room.
- Do NOT ask for feedback more than once per session.

LOYALTY POINTS:
- The SESSION section shows the guest's current loyalty points balance.
- Points are earned at 1 point per TZS 1,000 spent (calculated automatically after order completion).
- If the guest asks about loyalty points, share their balance and explain how to earn.
- Do NOT process point redemption via tinker. If the guest wants to redeem, say you will notify a manager and include {{NOTIFY_MANAGER:Guest [name] wants to redeem [N] loyalty points}}.

OPERATING HOURS:
- The SESSION section shows whether the restaurant is currently OPEN or CLOSED with specific hours.
- When CLOSED: do NOT accept new dine-in orders. Politely say the restaurant is closed and share opening hours.
- When CLOSED: DO allow reservations for future dates, menu browsing, loyalty point inquiries, and feedback.
- When OPEN: normal operation.
- Always share the specific hours when declining service due to being closed.
SYSTEM;
    }

    /**
     * Build the user-facing prompt — session + memory + pre-fetched data + message.
     *
     * Pre-fetches menu items, active order, and available tables so the AI
     * can answer most questions WITHOUT running database queries (tool calls).
     * This eliminates 1-3 round trips per message, saving 5-15 seconds.
     */
    protected function buildUserPrompt(Guest $guest, string $userText, array $sessionData): string
    {
        $session = $this->conversationManager->getSession($guest->phone_number);

        // ── Session context ──
        $parts = [];
        $ctx = "=== SESSION ===\n";
        $ctx .= "Guest: {$guest->name} (ID: {$guest->id}, Phone: {$guest->phone_number})\n";
        $ctx .= "Time: {$this->currentTime()}\n";

        // Operating hours
        $openingHours = Setting::get('opening_hours', '08:00');
        $closingHours = Setting::get('closing_hours', '23:00');
        $now = now()->format('H:i');
        $isOpen = $now >= $openingHours && $now <= $closingHours;
        $ctx .= "Operating Hours: {$openingHours} - {$closingHours}\n";
        $ctx .= 'Currently: ' . ($isOpen ? 'OPEN' : 'CLOSED') . "\n";

        // Loyalty points
        $ctx .= "Loyalty Points: {$guest->loyalty_points}\n";

        // Preferred language
        $profile = $guest->preferences ?? [];
        if (isset($profile['language'])) {
            $ctx .= "Preferred Language: " . ($profile['language'] === 'sw' ? 'Swahili' : 'English') . "\n";
        }

        if ($session->current_table_id) {
            $ctx .= "Table: {$session->current_table_id}\n";
        }
        if ($session->current_order_id) {
            $ctx .= "Active Order: {$session->current_order_id}\n";
        }
        $ctx .= "=== END SESSION ===";
        $parts[] = $ctx;

        // ── Guest memory (profile + conversation history + order history) ──
        $parts[] = $this->memoryService->buildMemoryContext($guest);

        // ── Pre-fetched menu (eliminates menu query tool calls) ──
        $parts[] = $this->buildMenuContext();

        // ── Pre-fetched active order details (eliminates order query tool calls) ──
        if ($session->current_order_id) {
            $parts[] = $this->buildActiveOrderContext($session->current_order_id);
            $parts[] = $this->buildPaymentStatusContext($session->current_order_id);
        }

        // ── Pre-fetched available tables (eliminates table query tool calls) ──
        $parts[] = $this->buildTablesContext();

        // ── Payment methods available ──
        $parts[] = $this->buildPaymentContext();

        // ── Upcoming reservations for this guest ──
        $parts[] = $this->buildReservationContext($guest);

        // ── Promotions and specials ──
        $parts[] = $this->buildPromotionsContext();

        // ── The guest's message ──
        $parts[] = "Guest says: {$userText}";

        return implode("\n\n", array_filter($parts));
    }

    /** Stub: no menu in bank context. */
    protected function buildMenuContext(): string
    {
        return '';
    }

    /** Stub: no order context in bank. */
    protected function buildActiveOrderContext(int $orderId): string
    {
        return '';
    }

    /** Stub: no payment status in bank. */
    protected function buildPaymentStatusContext(int $orderId): string
    {
        return '';
    }

    /** Stub: no tables in bank. */
    protected function buildTablesContext(): string
    {
        return '';
    }

    /**
     * Build payment methods context for the prompt.
     * Lists all enabled payment methods with their Lipa Namba numbers.
     */
    protected function buildPaymentContext(): string
    {
        $lines = ['=== PAYMENT METHODS (available for guests) ==='];
        $hasAny = false;

        // MNO — VodaCom
        if ((bool) Setting::get('vodacom_enabled', false)) {
            $lipa = Setting::get('vodacom_lipa_namba', '');
            if ($lipa) {
                $lines[] = "- VodaCom M-Pesa: Lipa Namba {$lipa}";
                $hasAny = true;
            }
        }

        // MNO — Yas
        if ((bool) Setting::get('yas_enabled', false)) {
            $lipa = Setting::get('yas_lipa_namba', '');
            if ($lipa) {
                $lines[] = "- Yas: Lipa Namba {$lipa}";
                $hasAny = true;
            }
        }

        // MNO — AirTel
        if ((bool) Setting::get('airtel_enabled', false)) {
            $lipa = Setting::get('airtel_lipa_namba', '');
            if ($lipa) {
                $lines[] = "- AirTel Money: Lipa Namba {$lipa}";
                $hasAny = true;
            }
        }

        // Bank
        if ((bool) Setting::get('bank_enabled', false)) {
            $bankName = Setting::get('bank_name', 'Bank');
            $lipa = Setting::get('bank_lipa_namba', '');
            $accName = Setting::get('bank_account_name', '');
            $accNum = Setting::get('bank_account_number', '');
            if ($lipa) {
                $detail = "{$bankName}: Lipa Namba {$lipa}";
                if ($accName) {
                    $detail .= " (Account: {$accName}, {$accNum})";
                }
                $lines[] = "- {$detail}";
                $hasAny = true;
            }
        }

        if (! $hasAny) {
            return '';
        }

        $lines[] = '=== END PAYMENT METHODS ===';

        return implode("\n", $lines);
    }

    /** Stub: payment QR sending can be reimplemented for bank. */
    protected function sendPaymentQRCodes(string $phone): void
    {
        // No-op; reimplement in bank context if needed.
    }

    /** Strip NOTIFY tags from response (staff notifications stubbed in bank). */
    protected function parseAndDispatchNotifications(string $response, Guest $guest): string
    {
        $pattern = '/\{\{NOTIFY_(WAITER|MANAGER|KITCHEN|BAR):(.+?)\}\}/s';
        if (preg_match_all($pattern, $response, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $response = str_replace($match[0], '', $response);
            }
            $response = preg_replace('/\n{3,}/', "\n\n", $response);
        }
        return $response;
    }

    /** Stub: no staff notifications in bank (reimplement with User/roles if needed). */
    protected function dispatchStaffNotification(string $target, string $message, Guest $guest): void
    {
        Log::channel('whatsapp')->info('AI staff notification (stubbed)', ['target' => $target, 'message' => mb_substr($message, 0, 80)]);
    }

    /** Stub. */
    protected function buildNotificationContext(Guest $guest): array
    {
        return [
            'guest_id' => $guest->id,
            'guest_name' => $guest->name ?? 'Guest',
            'guest_phone' => $guest->phone_number,
        ];
    }

    /** Stub: handoff can be reimplemented for bank staff. */
    protected function handleHumanHandoff(Guest $guest, string $reason): void
    {
        Log::channel('whatsapp')->info('AI handoff requested (stubbed)', ['guest_id' => $guest->id, 'reason' => $reason]);
    }

    /** Stub: no receipt in bank context. */
    protected function sendReceiptPdf(string $phone, Guest $guest): void
    {
    }

    /** Stub: feedback can be reimplemented for bank. */
    protected function saveFeedback(Guest $guest, int $rating, string $comment): void
    {
        $profile = $guest->preferences ?? [];
        $profile['last_feedback'] = ['rating' => $rating, 'date' => now()->toDateString()];
        $guest->update(['preferences' => $profile]);
    }

    /** Stub: no reservations in bank. */
    protected function buildReservationContext(Guest $guest): string
    {
        return '';
    }

    /** Stub: no promotions in bank. */
    protected function buildPromotionsContext(): string
    {
        return '';
    }

    /**
     * Call the AI sidecar HTTP endpoint.
     * Sends phone_number for per-session locking, system_prompt for role
     * instructions, and max_chars to constrain response length.
     */
    protected function callSidecar(string $phone, string $systemPrompt, string $userPrompt): ?string
    {
        try {
            // Ensure all strings are valid UTF-8 to prevent json_encode failures
            $safeSystem = mb_convert_encoding($systemPrompt, 'UTF-8', 'UTF-8');
            $safePrompt = mb_convert_encoding($userPrompt, 'UTF-8', 'UTF-8');

            $response = Http::timeout(130)
                ->post($this->sidecarUrl, [
                    'phone_number' => $phone,
                    'system_prompt' => $safeSystem,
                    'prompt' => $safePrompt,
                    'max_chars' => self::WHATSAPP_MAX_CHARS,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['success'] ?? false) && isset($data['data']['answer'])) {
                    return trim($data['data']['answer']);
                }
            }

            // 429 = another message from this phone is already being processed
            if ($response->status() === 429) {
                Log::channel('whatsapp')->info('AI Agent: sidecar busy with this phone, skipping', [
                    'phone' => $phone,
                ]);

                return null;
            }

            Log::channel('whatsapp')->error('AI Agent: sidecar error response', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('AI Agent: sidecar request failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send AI response to WhatsApp, splitting into multiple messages if needed.
     * WhatsApp limit: 4096 chars per message. We split at 3800 for safety.
     */
    protected function sendWhatsAppResponse(string $phone, string $text): void
    {
        if (strlen($text) <= self::WHATSAPP_MAX_CHARS) {
            $this->whatsappService->sendTextMessage($phone, $text);
            return;
        }

        // Split into chunks at paragraph boundaries
        $chunks = $this->splitMessage($text, self::WHATSAPP_MAX_CHARS);

        foreach ($chunks as $i => $chunk) {
            if (count($chunks) > 1) {
                $part = ($i + 1) . '/' . count($chunks);
                $chunk = trim($chunk) . "\n({$part})";
            }
            $this->whatsappService->sendTextMessage($phone, $chunk);
        }

        Log::channel('whatsapp')->info('AI Agent: split into ' . count($chunks) . ' messages', [
            'phone' => $phone,
            'total_length' => strlen($text),
        ]);
    }

    /**
     * Split a long message into chunks, preferring paragraph/line breaks.
     */
    protected function splitMessage(string $text, int $maxChars): array
    {
        $chunks = [];
        $remaining = $text;

        while (strlen($remaining) > $maxChars) {
            $chunk = substr($remaining, 0, $maxChars);

            // Try to split at the last double newline (paragraph break)
            $lastParagraph = strrpos($chunk, "\n\n");
            if ($lastParagraph !== false && $lastParagraph > $maxChars * 0.3) {
                $splitAt = $lastParagraph;
            } else {
                // Fall back to last single newline
                $lastNewline = strrpos($chunk, "\n");
                if ($lastNewline !== false && $lastNewline > $maxChars * 0.3) {
                    $splitAt = $lastNewline;
                } else {
                    // Last resort: split at last space
                    $lastSpace = strrpos($chunk, ' ');
                    $splitAt = $lastSpace !== false ? $lastSpace : $maxChars;
                }
            }

            $chunks[] = trim(substr($remaining, 0, $splitAt));
            $remaining = trim(substr($remaining, $splitAt));
        }

        if (!empty($remaining)) {
            $chunks[] = trim($remaining);
        }

        return $chunks;
    }

    /**
     * Clean up AI response for WhatsApp delivery.
     * Strips markdown, preambles, and developer-facing text.
     */
    protected function sanitizeForWhatsApp(string $text): string
    {
        // Remove preamble lines like "Here's the reply:" or "Here's the message to send:"
        $text = preg_replace('/^(Here\'s|Use this|Below is|This is).*?:\s*\n/i', '', $text);

        // Remove --- delimiters
        $text = preg_replace('/^---\s*$/m', '', $text);

        // Strip markdown bold (**text** or __text__)
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
        $text = preg_replace('/__(.+?)__/', '$1', $text);

        // Strip markdown italic (*text* or _text_) — but not bullet dashes
        $text = preg_replace('/(?<!\w)\*([^*\n]+?)\*(?!\w)/', '$1', $text);

        // Strip markdown headers (# ## ###)
        $text = preg_replace('/^#{1,3}\s+/m', '', $text);

        // Strip backtick code
        $text = preg_replace('/`([^`]+)`/', '$1', $text);

        // Strip code blocks
        $text = preg_replace('/```[\s\S]*?```/', '', $text);

        // Collapse multiple blank lines into one
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Get current time formatted for prompt.
     */
    protected function currentTime(): string
    {
        return now()->format('l, F j, Y g:i A');
    }
}
