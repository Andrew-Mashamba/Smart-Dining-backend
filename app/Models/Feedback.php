<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedback';

    protected $fillable = [
        'guest_id',
        'order_id',
        'rating',
        'comment',
        'source',
        'staff_response',
        'responded_by',
        'responded_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'responded_at' => 'datetime',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'responded_by');
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeNegative(Builder $query): Builder
    {
        return $query->where('rating', '<=', 2);
    }

    public function scopeUnresponded(Builder $query): Builder
    {
        return $query->whereNull('staff_response');
    }
}
