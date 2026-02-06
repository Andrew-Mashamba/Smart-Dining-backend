<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'location',
        'capacity',
        'status',
        'qr_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'capacity' => 'integer',
        'status' => 'string',
    ];

    /**
     * Get all orders for the table.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all guest sessions for the table.
     */
    public function guest_sessions(): HasMany
    {
        return $this->hasMany(GuestSession::class);
    }

    /**
     * Check if table is available.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Mark table as occupied.
     */
    public function markAsOccupied(): void
    {
        $this->update(['status' => 'occupied']);
    }

    /**
     * Mark table as available.
     */
    public function markAsAvailable(): void
    {
        $this->update(['status' => 'available']);
    }
}
