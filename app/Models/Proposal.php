<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proposal extends Model
{
    protected $fillable = [
        'reference',
        'title',
        'client_name',
        'client_email',
        'client_company',
        'summary',
        'body',
        'amount',
        'currency',
        'valid_until',
        'status',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'valid_until' => 'date',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateReference(): string
    {
        $prefix = 'PROP-';
        $last = static::orderBy('id', 'desc')->first();

        return $prefix . str_pad((string) (($last?->id ?? 0) + 1), 5, '0', STR_PAD_LEFT);
    }
}
