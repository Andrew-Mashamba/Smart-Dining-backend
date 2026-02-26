<?php

namespace App\Services\WhatsApp;

use App\Models\Guest;
use App\Models\GuestConversation;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Manages long-term memory for WhatsApp AI concierge.
 *
 * Three tiers:
 *   Tier 1: Core Profile — guests.preferences JSON (always in prompt)
 *   Tier 2: Conversation Log — guest_conversations table (recent history)
 *   Tier 3: Order History — orders/order_items (queried on demand by agent)
 */
class GuestMemoryService
{
    /**
     * Max recent messages to load into context.
     */
    const MAX_RECENT_MESSAGES = 20;

    /**
     * Max recent orders to summarize.
     */
    const MAX_RECENT_ORDERS = 5;

    /**
     * Build the full memory context block for the AI prompt.
     * This is injected into the system prompt so the agent always
     * knows who the guest is and what they like.
     */
    public function buildMemoryContext(Guest $guest): string
    {
        $sections = [];

        // Tier 1: Core profile
        $profile = $this->getProfile($guest);
        if (! empty($profile)) {
            $sections[] = $this->formatProfile($guest, $profile);
        } else {
            $sections[] = $this->formatNewGuest($guest);
        }

        // Tier 2: Recent conversation history
        $recentMessages = $this->getRecentConversations($guest);
        if (! empty($recentMessages)) {
            $sections[] = $this->formatConversationHistory($recentMessages);
        }

        // Tier 3: Recent order summary
        $recentOrders = $this->getRecentOrders($guest);
        if (! empty($recentOrders)) {
            $sections[] = $this->formatOrderHistory($recentOrders);
        }

        return implode("\n\n", $sections);
    }

    /**
     * Save a conversation turn (user message + AI response).
     */
    public function saveConversationTurn(Guest $guest, string $userMessage, string $aiResponse, string $messageType = 'text'): void
    {
        GuestConversation::create([
            'guest_id' => $guest->id,
            'role' => 'user',
            'content' => $userMessage,
            'message_type' => $messageType,
        ]);

        // Truncate long AI responses for storage (keep full for short ones)
        // Use mb_substr to avoid cutting multi-byte UTF-8 characters
        $storedResponse = mb_strlen($aiResponse) > 1000
            ? mb_substr($aiResponse, 0, 997) . '...'
            : $aiResponse;

        GuestConversation::create([
            'guest_id' => $guest->id,
            'role' => 'assistant',
            'content' => $storedResponse,
            'message_type' => $messageType,
        ]);
    }

    /**
     * Extract facts from a conversation and update the guest profile.
     * Called after each AI interaction to keep the profile fresh.
     *
     * This builds a prompt asking the sidecar LLM to extract facts,
     * but since that's expensive, we do it inline with a simple
     * rule-based extraction for common patterns + periodic LLM extraction.
     */
    public function updateProfileFromConversation(Guest $guest, string $userMessage, string $aiResponse): void
    {
        $profile = $guest->preferences ?? [];

        // Update visit tracking
        $profile['visit_count'] = ($profile['visit_count'] ?? 0);
        $profile['last_interaction'] = now()->toIso8601String();

        // Check if the AI created an order — extract from response
        if (preg_match('/order.*(confirmed|placed|created)/i', $aiResponse)) {
            $profile['last_order_date'] = now()->toDateString();
        }

        // Check if a reservation was made
        if (preg_match('/reserv.*(confirmed|booked|created)/i', $aiResponse)) {
            $profile['last_reservation_date'] = now()->toDateString();
        }

        // Extract dietary mentions from user message
        $this->extractDietaryInfo($userMessage, $profile);

        // Extract table preference
        $this->extractTablePreference($userMessage, $aiResponse, $profile);

        // Detect language preference
        $this->detectLanguage($userMessage, $profile);

        // Save updated profile
        $guest->update(['preferences' => $profile]);
    }

    /**
     * Perform a deep profile update using order history.
     * Should be called periodically (e.g., after every 5th conversation)
     * to compute "usual order" from actual data.
     */
    public function refreshProfileFromOrderHistory(Guest $guest): void
    {
        $profile = $guest->preferences ?? [];

        // Compute usual order from last 20 orders
        $topItems = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->where('orders.guest_id', $guest->id)
            ->where('orders.status', '!=', 'cancelled')
            ->select('menu_items.id', 'menu_items.name', DB::raw('SUM(order_items.quantity) as total_qty'), DB::raw('COUNT(*) as order_count'))
            ->groupBy('menu_items.id', 'menu_items.name')
            ->orderByDesc('order_count')
            ->limit(5)
            ->get();

        if ($topItems->isNotEmpty()) {
            $profile['usual_orders'] = $topItems->map(fn ($item) => [
                'item_id' => $item->id,
                'name' => $item->name,
                'frequency' => $item->order_count,
            ])->toArray();
        }

        // Compute visit count
        $profile['visit_count'] = Order::where('guest_id', $guest->id)
            ->where('status', '!=', 'cancelled')
            ->distinct('created_at')
            ->count();

        // Compute average spend
        $avgSpend = Order::where('guest_id', $guest->id)
            ->where('status', 'paid')
            ->avg('total');
        if ($avgSpend) {
            $profile['avg_spend'] = round($avgSpend);
        }

        // Get last table used
        $lastOrder = Order::where('guest_id', $guest->id)
            ->whereNotNull('table_id')
            ->latest()
            ->first();
        if ($lastOrder && $lastOrder->table_id) {
            $profile['last_table_id'] = $lastOrder->table_id;
        }

        $profile['profile_refreshed_at'] = now()->toIso8601String();

        $guest->update(['preferences' => $profile]);

        Log::channel('whatsapp')->info('Guest profile refreshed from order history', [
            'guest_id' => $guest->id,
            'usual_orders' => $profile['usual_orders'] ?? [],
        ]);
    }

    /**
     * Get the guest's core profile (preferences JSON).
     */
    public function getProfile(Guest $guest): array
    {
        return $guest->preferences ?? [];
    }

    /**
     * Get recent conversation messages for a guest.
     */
    public function getRecentConversations(Guest $guest, int $limit = null): array
    {
        $limit = $limit ?? self::MAX_RECENT_MESSAGES;

        return GuestConversation::where('guest_id', $guest->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['role', 'content', 'created_at'])
            ->reverse()
            ->values()
            ->toArray();
    }

    /**
     * Get recent orders for a guest with items.
     */
    public function getRecentOrders(Guest $guest, int $limit = null): array
    {
        $limit = $limit ?? self::MAX_RECENT_ORDERS;

        return Order::where('guest_id', $guest->id)
            ->where('status', '!=', 'cancelled')
            ->with(['orderItems.menuItem:id,name,price'])
            ->latest()
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Format the core profile for inclusion in the AI prompt.
     */
    protected function formatProfile(Guest $guest, array $profile): string
    {
        $lines = ["=== GUEST MEMORY (facts you know about this guest) ==="];
        $lines[] = "Name: {$guest->name}";

        if ($guest->first_visit_at) {
            $lines[] = "First visit: " . $guest->first_visit_at->format('M j, Y');
        }
        if ($guest->last_visit_at) {
            $lines[] = "Last visit: " . $guest->last_visit_at->format('M j, Y');
        }
        if (isset($profile['visit_count'])) {
            $lines[] = "Total visits: {$profile['visit_count']}";
        }

        // Dietary info
        if (! empty($profile['allergies'])) {
            $lines[] = "Allergies: " . implode(', ', (array) $profile['allergies']);
        }
        if (! empty($profile['dietary'])) {
            $lines[] = "Dietary: {$profile['dietary']}";
        }

        // Favorites
        if (! empty($profile['usual_orders'])) {
            $items = collect($profile['usual_orders'])
                ->map(fn ($o) => $o['name'] . ' (ordered ' . $o['frequency'] . 'x)')
                ->implode(', ');
            $lines[] = "Usual orders: {$items}";
        }
        if (isset($profile['favorite_table'])) {
            $lines[] = "Favorite table: {$profile['favorite_table']}";
        }
        if (isset($profile['last_table_id'])) {
            $lastTable = \App\Models\Table::find($profile['last_table_id']);
            $lastTableName = $lastTable ? $lastTable->name : "ID {$profile['last_table_id']}";
            $lines[] = "Last table: {$lastTableName}";
        }

        // Payment preference
        if (! empty($profile['preferred_payment'])) {
            $lines[] = "Preferred payment: {$profile['preferred_payment']}";
        }

        // Average spend
        if (isset($profile['avg_spend'])) {
            $lines[] = "Average spend: TZS " . number_format($profile['avg_spend']);
        }

        // Special notes
        if (! empty($profile['special_notes'])) {
            $lines[] = "Notes: {$profile['special_notes']}";
        }
        if (! empty($profile['special_dates'])) {
            foreach ((array) $profile['special_dates'] as $sd) {
                $lines[] = "Special date: {$sd['date']} ({$sd['occasion']})";
            }
        }

        // Last interaction summary
        if (! empty($profile['last_interaction_summary'])) {
            $lines[] = "Last interaction: {$profile['last_interaction_summary']}";
        }

        $lines[] = "=== END GUEST MEMORY ===";

        return implode("\n", $lines);
    }

    /**
     * Format context for a brand new guest (no profile yet).
     */
    protected function formatNewGuest(Guest $guest): string
    {
        $name = $guest->name ?? 'Guest';

        return "=== GUEST MEMORY ===\n"
            . "This is a NEW guest. Name: {$name}.\n"
            . "No previous interactions or preferences on file.\n"
            . "Be extra welcoming. Learn their name if not known.\n"
            . "=== END GUEST MEMORY ===";
    }

    /**
     * Format recent conversation history for the prompt.
     */
    protected function formatConversationHistory(array $messages): string
    {
        if (empty($messages)) {
            return '';
        }

        $lines = ["=== RECENT CONVERSATION HISTORY ==="];

        foreach ($messages as $msg) {
            $role = $msg['role'] === 'user' ? 'Guest' : 'You';
            $time = \Carbon\Carbon::parse($msg['created_at'])->diffForHumans();
            // Truncate long messages in history (mb_substr for UTF-8 safety)
            $content = $msg['content'];
            if (mb_strlen($content) > 300) {
                $content = mb_substr($content, 0, 297) . '...';
            }
            $lines[] = "[{$time}] {$role}: {$content}";
        }

        $lines[] = "=== END CONVERSATION HISTORY ===";

        return implode("\n", $lines);
    }

    /**
     * Format recent order history for the prompt.
     */
    protected function formatOrderHistory(array $orders): string
    {
        if (empty($orders)) {
            return '';
        }

        $lines = ["=== PAST ORDER HISTORY (for reference only — these are NOT current active orders) ==="];

        foreach ($orders as $order) {
            $date = \Carbon\Carbon::parse($order['created_at'])->format('M j, Y');
            $status = $order['status'];
            $total = number_format($order['total'] ?? 0);
            $items = collect($order['order_items'] ?? [])
                ->map(fn ($oi) => ($oi['menu_item']['name'] ?? 'Unknown') . ' x' . $oi['quantity'])
                ->implode(', ');
            $orderNum = $order['order_number'] ?? 'N/A';

            $lines[] = "- {$date}: #{$orderNum} — {$items} (TZS {$total}, {$status})";
        }

        $lines[] = "NOTE: Only refer to these as past orders. The guest's CURRENT active order is in the ACTIVE ORDER section above (if any). If there is no ACTIVE ORDER section, the guest has NO current order.";
        $lines[] = "=== END PAST ORDER HISTORY ===";

        return implode("\n", $lines);
    }

    /**
     * Extract dietary info from user messages (rule-based).
     */
    protected function extractDietaryInfo(string $text, array &$profile): void
    {
        $text = strtolower($text);

        // Allergy detection
        $allergyPatterns = [
            'allergic to' => '/allergic\s+to\s+([\w\s,]+)/i',
            'allergy' => '/(\w+)\s+allergy/i',
            'can\'t eat' => '/can\'?t\s+eat\s+([\w\s,]+)/i',
            'no ' => '/\bno\s+(nuts|shellfish|dairy|gluten|soy|eggs?|fish|peanuts?|wheat)\b/i',
        ];

        foreach ($allergyPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $allergen = trim($matches[1]);
                $existing = $profile['allergies'] ?? [];
                if (! in_array($allergen, $existing)) {
                    $existing[] = $allergen;
                    $profile['allergies'] = $existing;
                }
            }
        }

        // Dietary preference detection
        $dietaryMap = [
            'vegetarian' => 'vegetarian',
            'vegan' => 'vegan',
            'pescatarian' => 'pescatarian',
            'halal' => 'halal',
            'kosher' => 'kosher',
            'gluten.?free' => 'gluten-free',
            'keto' => 'keto',
        ];

        foreach ($dietaryMap as $pattern => $value) {
            if (preg_match("/\b{$pattern}\b/i", $text)) {
                $profile['dietary'] = $value;
            }
        }
    }

    /**
     * Extract table preference from conversation.
     */
    protected function extractTablePreference(string $userMsg, string $aiResponse, array &$profile): void
    {
        // "I like table 7" / "table 7 please" / "my favorite table"
        if (preg_match('/(?:like|prefer|love|want|usual)\s+table\s+(\w+)/i', $userMsg, $m)) {
            $profile['favorite_table'] = $m[1];
        }

        // "same table as last time" — handled by the agent via last_table_id

        // Seating preference: "outdoor" / "by the window" / "ocean view"
        $locationPatterns = [
            'outdoor' => '/\b(outdoor|outside|terrace|patio)\b/i',
            'indoor' => '/\b(indoor|inside)\b/i',
            'bar' => '/\b(at the bar|bar seat)\b/i',
            'ocean_view' => '/\b(ocean|sea|water)\s*(view|side)\b/i',
        ];

        foreach ($locationPatterns as $pref => $pattern) {
            if (preg_match($pattern, $userMsg)) {
                $profile['seating_preference'] = $pref;
            }
        }
    }

    /**
     * Detect language from user message (rule-based Swahili detection).
     */
    protected function detectLanguage(string $text, array &$profile): void
    {
        $text = strtolower($text);

        $swahiliWords = [
            'habari', 'mambo', 'karibu', 'asante', 'tafadhali', 'nataka',
            'naomba', 'chakula', 'maji', 'vipi', 'sawa', 'ndiyo', 'hapana',
            'pesa', 'bei', 'meza', 'samahani', 'shukrani', 'pole', 'bwana',
            'dada', 'kaka', 'ndugu', 'sana', 'kidogo', 'kubwa', 'tena',
            'leo', 'kesho', 'jana', 'sasa', 'hapa', 'kula', 'kunywa',
            'sahani', 'menyu', 'orodha', 'agiza', 'malipo', 'nini', 'wapi',
        ];

        $swahiliCount = 0;
        foreach ($swahiliWords as $word) {
            if (str_contains($text, $word)) {
                $swahiliCount++;
            }
        }

        if ($swahiliCount >= 2) {
            $profile['language'] = 'sw';
        } elseif ($swahiliCount === 0 && ! isset($profile['language'])) {
            $profile['language'] = 'en';
        }
    }

    /**
     * Check if the profile should be refreshed from order history.
     * Returns true if the profile hasn't been refreshed recently.
     */
    public function shouldRefreshProfile(Guest $guest): bool
    {
        $profile = $guest->preferences ?? [];
        $lastRefresh = $profile['profile_refreshed_at'] ?? null;

        if (! $lastRefresh) {
            return true;
        }

        // Refresh if older than 24 hours
        return \Carbon\Carbon::parse($lastRefresh)->lt(now()->subHours(24));
    }
}
