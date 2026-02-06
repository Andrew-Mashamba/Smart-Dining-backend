<?php

namespace App\Services\WhatsApp;

use App\Models\Guest;
use Illuminate\Support\Facades\Cache;

class StateManager
{
    protected int $stateTTL = 3600; // 1 hour

    /**
     * Get current conversation state for guest
     */
    public function getState(Guest $guest): string
    {
        return Cache::get($this->getStateKey($guest), 'NEW');
    }

    /**
     * Set conversation state for guest
     */
    public function setState(Guest $guest, string $state): void
    {
        Cache::put($this->getStateKey($guest), $state, $this->stateTTL);
    }

    /**
     * Get conversation context data
     */
    public function getContext(Guest $guest): array
    {
        return Cache::get($this->getContextKey($guest), []);
    }

    /**
     * Set conversation context data
     */
    public function setContext(Guest $guest, array $context): void
    {
        Cache::put($this->getContextKey($guest), $context, $this->stateTTL);
    }

    /**
     * Update specific context field
     *
     * @param  mixed  $value
     */
    public function updateContext(Guest $guest, string $key, $value): void
    {
        $context = $this->getContext($guest);
        $context[$key] = $value;
        $this->setContext($guest, $context);
    }

    /**
     * Clear conversation state and context
     */
    public function clearState(Guest $guest): void
    {
        Cache::forget($this->getStateKey($guest));
        Cache::forget($this->getContextKey($guest));
    }

    /**
     * Get cache key for state
     */
    protected function getStateKey(Guest $guest): string
    {
        return "whatsapp:state:{$guest->phone_number}";
    }

    /**
     * Get cache key for context
     */
    protected function getContextKey(Guest $guest): string
    {
        return "whatsapp:context:{$guest->phone_number}";
    }
}
