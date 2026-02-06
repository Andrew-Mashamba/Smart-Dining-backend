<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Guest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone_number',
        'name',
        'loyalty_points',
        'preferences',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'preferences' => 'array',
        'loyalty_points' => 'integer',
    ];

    /**
     * Get all orders for the guest.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all guest sessions for the guest.
     */
    public function guest_sessions(): HasMany
    {
        return $this->hasMany(GuestSession::class);
    }

    /**
     * Get all sessions for the guest (alias).
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(GuestSession::class);
    }

    /**
     * Get all payments through orders.
     */
    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(Payment::class, Order::class);
    }

    /**
     * Get the active session for the guest.
     */
    public function activeSession()
    {
        return $this->sessions()->where('status', 'active')->latest()->first();
    }

    /**
     * Update last visit timestamp.
     */
    public function updateLastVisit(): void
    {
        $this->update(['last_visit_at' => now()]);
    }
}
