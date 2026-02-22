<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'reference_number',
        'guest_id',
        'table_id',
        'reservation_date',
        'reservation_time',
        'party_size',
        'location',
        'status',
        'special_requests',
        'source',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'party_size' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $reservation) {
            if (empty($reservation->reference_number)) {
                $date = $reservation->reservation_date->format('Ymd');
                $count = static::whereDate('reservation_date', $reservation->reservation_date)->lockForUpdate()->count() + 1;
                $suffix = strtoupper(substr(uniqid(), -3));
                $reservation->reference_number = "RES-{$date}-".str_pad($count, 3, '0', STR_PAD_LEFT)."-{$suffix}";
            }
        });
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function scopeUpcoming($query)
    {
        return $query->where('reservation_date', '>=', today())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('reservation_date')
            ->orderBy('reservation_time');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('reservation_date', $date);
    }
}
