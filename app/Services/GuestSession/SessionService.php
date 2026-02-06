<?php

namespace App\Services\GuestSession;

use App\Models\Guest;
use App\Models\GuestSession;
use App\Models\Table;
use Illuminate\Support\Str;

class SessionService
{
    /**
     * Start a new guest session
     */
    public function startSession(Guest $guest, ?Table $table = null): GuestSession
    {
        // End any existing active sessions for this guest
        $this->endActiveSessionsForGuest($guest);

        $session = GuestSession::create([
            'guest_id' => $guest->id,
            'table_id' => $table?->id,
            'session_token' => $this->generateSessionToken(),
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Mark table as occupied if assigned
        if ($table) {
            $table->markAsOccupied();
        }

        // Update guest's last visit
        $guest->update(['last_visit_at' => now()]);

        return $session;
    }

    /**
     * Assign a table to an existing session
     */
    public function assignTable(GuestSession $session, Table $table): void
    {
        if ($session->status !== 'active') {
            throw new \Exception('Can only assign tables to active sessions');
        }

        if (! $table->isAvailable()) {
            throw new \Exception('Table is not available');
        }

        // Release previous table if any
        if ($session->table_id) {
            $previousTable = Table::find($session->table_id);
            if ($previousTable) {
                $previousTable->markAsAvailable();
            }
        }

        $session->update(['table_id' => $table->id]);
        $table->markAsOccupied();
    }

    /**
     * End a guest session
     */
    public function endSession(GuestSession $session): void
    {
        if ($session->status === 'ended') {
            return;
        }

        // Check if there are any unpaid orders
        $unpaidOrders = $session->orders()
            ->whereDoesntHave('payments', function ($query) {
                $query->where('status', 'completed');
            })
            ->count();

        if ($unpaidOrders > 0) {
            throw new \Exception('Cannot end session with unpaid orders');
        }

        $session->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);

        // Release table
        if ($session->table) {
            $session->table->markAsAvailable();
        }

        // Award loyalty points based on session orders
        $this->awardLoyaltyPoints($session);
    }

    /**
     * Get active session for a guest
     */
    public function getActiveSession(Guest $guest): ?GuestSession
    {
        return GuestSession::where('guest_id', $guest->id)
            ->where('status', 'active')
            ->with(['table', 'orders'])
            ->first();
    }

    /**
     * Get session by token
     */
    public function getSessionByToken(string $token): ?GuestSession
    {
        return GuestSession::where('session_token', $token)
            ->where('status', 'active')
            ->with(['guest', 'table', 'orders'])
            ->first();
    }

    /**
     * Generate a unique session token
     */
    protected function generateSessionToken(): string
    {
        do {
            $token = strtoupper(Str::random(8));
        } while (GuestSession::where('session_token', $token)->exists());

        return $token;
    }

    /**
     * End all active sessions for a guest
     */
    protected function endActiveSessionsForGuest(Guest $guest): void
    {
        $activeSessions = GuestSession::where('guest_id', $guest->id)
            ->where('status', 'active')
            ->get();

        foreach ($activeSessions as $session) {
            // Force end without payment check
            $session->update([
                'status' => 'ended',
                'ended_at' => now(),
            ]);

            if ($session->table) {
                $session->table->markAsAvailable();
            }
        }
    }

    /**
     * Award loyalty points based on session spending
     */
    protected function awardLoyaltyPoints(GuestSession $session): void
    {
        $totalSpent = $session->orders()
            ->where('status', 'completed')
            ->sum('total_amount');

        // Award 1 point per 1000 TZS spent
        $pointsEarned = floor($totalSpent / 1000);

        if ($pointsEarned > 0) {
            $session->guest->increment('loyalty_points', $pointsEarned);

            \Log::info('Loyalty points awarded', [
                'guest_id' => $session->guest_id,
                'points_earned' => $pointsEarned,
                'total_spent' => $totalSpent,
            ]);
        }
    }

    /**
     * Get session summary with orders and payments
     */
    public function getSessionSummary(GuestSession $session): array
    {
        $session->load(['guest', 'table', 'orders.items.menuItem', 'orders.payments']);

        $totalSpent = $session->orders->sum('total_amount');
        $totalPaid = $session->orders->flatMap->payments
            ->where('status', 'completed')
            ->sum('amount');

        return [
            'session_id' => $session->id,
            'session_token' => $session->session_token,
            'guest' => [
                'name' => $session->guest->name,
                'phone' => $session->guest->phone_number,
                'loyalty_points' => $session->guest->loyalty_points,
            ],
            'table' => $session->table?->name,
            'status' => $session->status,
            'started_at' => $session->started_at,
            'ended_at' => $session->ended_at,
            'orders' => $session->orders->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'items_count' => $order->items->count(),
                ];
            }),
            'financial_summary' => [
                'total_spent' => $totalSpent,
                'total_paid' => $totalPaid,
                'balance' => $totalSpent - $totalPaid,
            ],
        ];
    }
}
