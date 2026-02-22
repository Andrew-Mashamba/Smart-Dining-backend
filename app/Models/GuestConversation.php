<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestConversation extends Model
{
    protected $fillable = [
        'guest_id',
        'role',
        'content',
        'message_type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
