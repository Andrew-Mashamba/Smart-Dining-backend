<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppSession;

class ConversationManager
{
    const STATE_MAIN_MENU = 'MAIN_MENU';
    const STATE_RESERVATION_GUESTS = 'RESERVATION_GUESTS';
    const STATE_RESERVATION_DATE = 'RESERVATION_DATE';
    const STATE_RESERVATION_TIME = 'RESERVATION_TIME';
    const STATE_RESERVATION_LOCATION = 'RESERVATION_LOCATION';
    const STATE_RESERVATION_NAME = 'RESERVATION_NAME';
    const STATE_RESERVATION_CONFIRM = 'RESERVATION_CONFIRM';
    const STATE_ORDER_TABLE = 'ORDER_TABLE';
    const STATE_ORDER_CATEGORY = 'ORDER_CATEGORY';
    const STATE_ORDER_ITEMS = 'ORDER_ITEMS';
    const STATE_ORDER_QUANTITY = 'ORDER_QUANTITY';
    const STATE_ORDER_SPECIAL = 'ORDER_SPECIAL';
    const STATE_ORDER_CART = 'ORDER_CART';
    const STATE_ORDER_CONFIRM = 'ORDER_CONFIRM';
    const STATE_CURRENT_ORDER = 'CURRENT_ORDER';
    const STATE_NOTIFICATION_TABLE = 'NOTIFICATION_TABLE';
    const STATE_PAYMENT_METHOD = 'PAYMENT_METHOD';
    const STATE_PAYMENT_MPESA = 'PAYMENT_MPESA';
    const STATE_PAYMENT_PROCESSING = 'PAYMENT_PROCESSING';
    const STATE_AI_CONVERSATION = 'AI_CONVERSATION';

    protected StateManager $stateManager;

    public function __construct(StateManager $stateManager)
    {
        $this->stateManager = $stateManager;
    }

    /**
     * Get or create a session for a phone number.
     * Auto-resets expired sessions.
     */
    public function getSession(string $phoneNumber): WhatsAppSession
    {
        $session = WhatsAppSession::firstOrCreate(
            ['phone_number' => $phoneNumber],
            ['state' => self::STATE_MAIN_MENU, 'last_activity_at' => now()]
        );

        if ($session->isExpired()) {
            $session->update([
                'state' => self::STATE_MAIN_MENU,
                'data' => null,
                'current_order_id' => null,
                'last_activity_at' => now(),
            ]);
            $this->stateManager->clearStateByPhone($phoneNumber);
        }

        return $session;
    }

    /**
     * Get the current conversation state (cache-first, then DB fallback).
     */
    public function getState(string $phoneNumber): string
    {
        // Try cache first for performance
        $cachedState = $this->stateManager->getStateByPhone($phoneNumber);
        if ($cachedState) {
            return $cachedState;
        }

        // Fall back to DB and sync cache
        $session = $this->getSession($phoneNumber);
        $state = $session->state ?? self::STATE_MAIN_MENU;
        $this->stateManager->setStateByPhone($phoneNumber, $state);

        return $state;
    }

    /**
     * Set conversation state and optionally merge data.
     */
    public function setState(string $phoneNumber, string $state, array $data = []): void
    {
        $session = $this->getSession($phoneNumber);

        $updateData = [
            'state' => $state,
            'last_activity_at' => now(),
        ];

        if (! empty($data)) {
            $updateData['data'] = array_merge($session->data ?? [], $data);
        }

        $session->update($updateData);

        // Sync to cache for fast reads
        $this->stateManager->setStateByPhone($phoneNumber, $state);
    }

    /**
     * Get all session data.
     */
    public function getSessionData(string $phoneNumber): array
    {
        return $this->getSession($phoneNumber)->data ?? [];
    }

    /**
     * Update a specific key in session data.
     */
    public function updateSessionData(string $phoneNumber, string $key, mixed $value): void
    {
        $session = $this->getSession($phoneNumber);
        $data = $session->data ?? [];
        $data[$key] = $value;

        $session->update([
            'data' => $data,
            'last_activity_at' => now(),
        ]);

        $this->stateManager->updateContextByPhone($phoneNumber, $key, $value);
    }

    /**
     * Set the current order for this session.
     */
    public function setCurrentOrder(string $phoneNumber, int $orderId): void
    {
        $this->getSession($phoneNumber)->update([
            'current_order_id' => $orderId,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Set the current table for this session.
     */
    public function setCurrentTable(string $phoneNumber, int $tableId): void
    {
        $this->getSession($phoneNumber)->update([
            'current_table_id' => $tableId,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Link a guest to this session.
     */
    public function linkGuest(string $phoneNumber, int $guestId): void
    {
        $this->getSession($phoneNumber)->update([
            'guest_id' => $guestId,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Clear session state and data (reset to main menu).
     * Preserves current_table_id by default (guest stays at their table).
     */
    public function clearSession(string $phoneNumber, bool $clearTable = false): void
    {
        $session = $this->getSession($phoneNumber);

        $updateData = [
            'state' => self::STATE_MAIN_MENU,
            'data' => null,
            'current_order_id' => null,
            'last_activity_at' => now(),
        ];

        if ($clearTable) {
            $updateData['current_table_id'] = null;
        }

        $session->update($updateData);

        $this->stateManager->clearStateByPhone($phoneNumber);
    }

    /**
     * Check if a session has expired.
     */
    public function isSessionExpired(string $phoneNumber): bool
    {
        $session = WhatsAppSession::where('phone_number', $phoneNumber)->first();

        if (! $session) {
            return true;
        }

        return $session->isExpired();
    }
}
