<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Setting;

class WhatsAppSession extends Model
{
    protected $table = 'whatsapp_sessions';

    protected $fillable = [
        'phone_number',
        'state',
        'data',
        'guest_id',
        'current_order_id',
        'current_table_id',
        'last_activity_at',
    ];

    protected $casts = [
        'data' => 'array',
        'last_activity_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $session) {
            $session->last_activity_at ??= now();
        });
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function currentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'current_order_id');
    }

    public function currentTable(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'current_table_id');
    }

    public function isExpired(): bool
    {
        if (! $this->last_activity_at) {
            return true;
        }

        $timeout = Setting::get('whatsapp_session_timeout', config('whatsapp.session_timeout', 3600));

        return $this->last_activity_at->diffInSeconds(now()) > $timeout;
    }
}
