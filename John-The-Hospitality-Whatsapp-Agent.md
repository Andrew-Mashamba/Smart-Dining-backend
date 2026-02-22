# Smart Dining AI System — Deep Analysis

## 1. Architecture Overview

The AI is a WhatsApp concierge chatbot for a Dar es Salaam restaurant. It's a 6-layer pipeline:

```
WhatsApp Cloud API
    → WebhookController (HTTP entry, returns 200 instantly)
        → MessageHandler (dedup, guest resolution, routing)
            → ProcessAiMessage job (async on "ai" queue)
                → AiAgentService (prompt building, response parsing)
                    → Python sidecar (port 8101, spawns Claude Code CLI)
```

## 2. Request Lifecycle (step by step)

### Step 1: Webhook Ingress (WebhookController::handle)
- Receives POST from WhatsApp Cloud API
- Parses the Meta webhook envelope (entry[].changes[].value.messages[])
- Forwards each message to MessageHandler::handle()
- Always returns 200 (even on error — prevents WhatsApp retries)

### Step 2: Message Handling (MessageHandler::handle)
- Marks the message as read (blue ticks)
- Gets or creates a Guest record using phone number as natural key
- Links guest to a WhatsAppSession
- Deduplication: Caches wamid for 10 minutes to skip WhatsApp webhook retries
- Routing decision: Checks Setting::get('whatsapp_ai_enabled'):
  - AI ON → Dispatches ProcessAiMessage job to the ai queue, frees the PHP worker immediately
  - AI OFF → Falls through to FlowManager (state-machine menu bot, synchronous)

### Step 3: Async AI Processing (ProcessAiMessage job)
- Queue: ai (dedicated, separate from notifications and default)
- Timeout: 150 seconds, 2 tries, 5s backoff
- Sends typing indicator first (guest sees "typing...")
- Calls AiAgentService::processMessage()
- Fallback: If AI fails (exception, null response, sidecar down), falls back to FlowManager — the guest always gets a response

### Step 4: Prompt Engineering (AiAgentService)

The prompt has two parts:

**System Prompt** (buildSystemPrompt) — ~390 lines of instructions covering:
- Role: restaurant concierge in Dar es Salaam
- Output rules: plain text only, no markdown, no code blocks
- Session isolation: only operate on this guest's data
- Pre-fetched data awareness: answer from prompt data, don't query DB for reads
- Write operation templates: exact Eloquent snippets for creating orders, items, reservations
- Order modification/cancellation rules with status checks
- Payment flow: check payment status, trigger {{SEND_PAYMENT_QR}}
- Staff notification tags: {{NOTIFY_WAITER}}, {{NOTIFY_MANAGER}}, {{NOTIFY_KITCHEN}}, {{NOTIFY_BAR}}
- Human handoff: {{HANDOFF_TO_STAFF:reason}}
- Feedback collection: {{SAVE_FEEDBACK:rating|comment}}
- Language: bilingual English + Swahili, auto-detect
- Promotions, loyalty points, operating hours, out-of-stock handling

**User Prompt** (buildUserPrompt) — pre-fetches ALL context to minimize tool calls:
- SESSION: guest name, ID, phone, time, operating hours, loyalty points, language, table, active order
- GUEST MEMORY: profile from preferences JSON (allergies, dietary, usual orders, visit count, avg spend)
- CONVERSATION HISTORY: last 20 messages from guest_conversations
- RECENT ORDERS: last 5 orders with items
- MENU: full menu grouped by category with prices, stock warnings, unavailable items
- ACTIVE ORDER: current order items with prep status, waiter name, totals
- PAYMENT STATUS: all payments for active order (completed, pending, failed, balance)
- AVAILABLE TABLES: grouped by location
- PAYMENT METHODS: enabled MNO providers (VodaCom, Yas, AirTel) + bank with Lipa Namba numbers
- UPCOMING RESERVATIONS: this guest's future reservations
- PROMOTIONS AND SPECIALS: active promotions from Promotion model
- Finally: Guest says: {message}

### Step 5: Python Sidecar (scripts/ai-assistant.py)
- ThreadingHTTPServer on 127.0.0.1:8101
- Concurrent: each request runs in its own thread
- Per-phone session locking: A threading lock per phone number serializes messages from the same guest (prevents race conditions on orders). Different guests process in parallel
- If a phone is already being processed: returns 429, AiAgentService returns null → fallback to FlowManager
- Calls the Claude Code CLI (/usr/local/bin/agent) as a subprocess:
  `agent -p --output-format text --model auto --force --trust --workspace /var/www/html/Smart-Dining <prompt>`
- CLI runs with --trust and --force — the AI can execute php artisan tinker to write to the database (create orders, reservations, etc.)
- 120-second timeout per request
- Stale lock cleanup every 5 minutes

### Step 6: Response Processing (processMessage cont.)

After getting the raw AI response, AiAgentService:

1. **Sanitizes** — strips markdown, code blocks, preambles
2. **Parses action tags** (invisible to guest, stripped before sending):
   - `{{SEND_PAYMENT_QR}}` → sends QR code images for each enabled payment method
   - `{{SEND_RECEIPT}}` → generates PDF receipt via DomPDF, sends as WhatsApp document
   - `{{HANDOFF_TO_STAFF:reason}}` → notifies all waiters + managers via FCM + DB
   - `{{SAVE_FEEDBACK:rating|comment}}` → saves to feedback table + updates guest preferences
   - `{{NOTIFY_WAITER|MANAGER|KITCHEN|BAR:message}}` → dispatches SendFcmNotification + stores AiStaffNotification in DB
3. **Splits long messages** — WhatsApp limit is 4096 chars; splits at 3800 at paragraph/line/word boundaries
4. **Saves conversation** to guest_conversations table (user + assistant turns)
5. **Updates guest profile** — rule-based extraction of dietary info, table preferences, language detection
6. **Periodic deep refresh** — every 24 hours, recomputes "usual orders", visit count, average spend from order history

## 3. Memory System (3-Tier)

**Tier 1: Core Profile** (guests.preferences JSON)
- Always in prompt. Stores: allergies, dietary, usual orders, favorite table, avg spend, language, last interaction
- Updated after every conversation (rule-based extraction)
- Deep-refreshed every 24 hours from order history

**Tier 2: Conversation Log** (guest_conversations table)
- Last 20 messages loaded into prompt
- Both user and assistant turns stored
- AI responses truncated to 1000 chars for storage
- Displayed in prompt as [time ago] Guest/You: message

**Tier 3: Order History** (orders + order_items)
- Last 5 orders with items summarized in prompt
- Used for "my usual" / repeat order patterns
- Feeds into the periodic profile refresh (computes top 5 most-ordered items)

## 4. Session Management

- WhatsAppSession model: per-phone, stores state, data JSON, current_order_id, current_table_id
- Dual-layer state: Cache (1hr TTL for fast reads) + DB (persistent)
- Session expiry: configurable via whatsapp_session_timeout setting (default 3600s)
- When AI is active: state is set to AI_CONVERSATION
- When AI fails: FlowManager uses the traditional state machine (MAIN_MENU → ORDER_TABLE → ORDER_CATEGORY → etc.)

## 5. Notification Dispatch

When the AI includes notification tags:
1. Target resolution: WAITER → assigned waiter (or all waiters), MANAGER → managers + admins, KITCHEN → chefs, BAR → bartenders
2. FCM push via SendFcmNotification job on notifications queue
3. DB notification via AiStaffNotification (Laravel database channel) — shown in the Livewire NotificationBell component

## 6. Fallback System

The system has a complete fallback chain:
1. AI enabled → try AI agent
2. AI sidecar returns 429 (busy) → return null → fall back to FlowManager
3. AI sidecar returns error/timeout → return null → fall back to FlowManager
4. AI returns empty response → fall back to FlowManager
5. AI exception → catch → fall back to FlowManager
6. FlowManager exception → catch → send "something went wrong" + reset to MAIN_MENU

The FlowManager is a traditional state-machine bot with sub-bots: MenuBot, ReservationBot, NotificationBot, PaymentBot.

## 7. Key Design Decisions

| Decision | Rationale |
|---|---|
| Pre-fetch all data into prompt | Eliminates 1-3 tool-call round trips per message (saves 5-15s) |
| Async via queue job | Webhook returns 200 instantly; AI processing takes 10-120s |
| Per-phone threading locks | Prevents race conditions on concurrent messages from same guest |
| System prompt kept stable | Enables Anthropic's server-side prompt caching (up to 85% faster) |
| Claude Code CLI with --trust | AI can execute php artisan tinker for write operations (orders, reservations) |
| Tag-based actions | AI controls payment QR, receipts, staff notifications via invisible tags |
| 3-tier memory | Balances prompt size vs. personalization depth |
| FlowManager fallback | Guests always get a response even if AI is completely down |

## 8. Potential Concerns

1. **Security**: The AI runs with --trust --force and can execute arbitrary tinker commands. The system prompt constrains it, but prompt injection via guest messages is a theoretical risk.
2. **Prompt size**: With full menu + order + memory + history, prompts can approach the 24,000 char limit, especially for restaurants with large menus.
3. **No conversation threading in Claude**: The sidecar spawns a fresh CLI process per message — there's no multi-turn context window at the LLM level beyond what's in the prompt. All context comes from the pre-fetched data + conversation history from the DB.
4. **Single point of failure**: The Python sidecar is a single process. If it crashes, all AI requests fail (though FlowManager catches them).
5. **Cost**: Every message triggers a full LLM call with a large prompt (system + user context). High-volume restaurants could see significant API costs.

## Key Files

| File | Purpose |
|---|---|
| app/Services/WhatsApp/AiAgentService.php | Central AI processing (~1229 lines) |
| app/Services/WhatsApp/GuestMemoryService.php | 3-tier memory (profile, conversation, orders) |
| app/Services/WhatsApp/MessageHandler.php | Webhook message routing + dedup |
| app/Services/WhatsApp/ConversationManager.php | Session state management (cache + DB) |
| app/Services/WhatsApp/StateManager.php | Cache-layer state reads/writes |
| app/Services/WhatsApp/FlowManager.php | Fallback state-machine bot |
| app/Services/WhatsApp/WhatsAppService.php | WhatsApp Cloud API (text, buttons, lists, images, docs) |
| app/Jobs/ProcessAiMessage.php | Async queue job for AI processing |
| app/Jobs/SendFcmNotification.php | FCM push notification dispatch |
| app/Notifications/AiStaffNotification.php | Laravel DB notification for staff |
| app/Models/WhatsAppSession.php | Session model (phone, state, order, table) |
| app/Models/GuestConversation.php | Conversation log model |
| app/Http/Controllers/WhatsApp/WebhookController.php | Webhook entry point |
| scripts/ai-assistant.py | Python sidecar (threading HTTP + CLI subprocess) |
