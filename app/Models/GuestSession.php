<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GuestSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'table_id',
        'guest_id',
        'session_token',
        'started_at',
        'ended_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($guestSession) {
            if (empty($guestSession->session_token)) {
                $guestSession->session_token = Str::random(32);
            }
        });
    }

    /**
     * Scope a query to only include active sessions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    /**
     * Get the guest for this session.
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * Get the table for this session.
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    /**
     * Get all orders for this session.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'session_id');
    }

    /**
     * Close the session.
     */
    public function close(): void
    {
        $this->update([
            'ended_at' => now(),
        ]);
    }

    /**
     * Check if session is active.
     */
    public function isActive(): bool
    {
        return $this->ended_at === null;
    }
}
