<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_number',
        'table_id',
        'guest_id',
        'waiter_id',
        'order_source',
        'status',
        'subtotal',
        'tax',
        'total',
        'special_instructions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($order) {
            // Generate order_number after the order is created and has an ID
            $order->order_number = 'ORD-'.date('Ymd').'-'.str_pad($order->id, 4, '0', STR_PAD_LEFT);
            $order->saveQuietly(); // Use saveQuietly to prevent firing events again
        });
    }

    /**
     * Get the guest for this order.
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * Get the table for this order.
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    /**
     * Get the waiter for this order.
     */
    public function waiter(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'waiter_id');
    }

    /**
     * Get all items in this order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Alias for orderItems() for backwards compatibility.
     */
    public function items(): HasMany
    {
        return $this->orderItems();
    }

    /**
     * Get all payments for this order.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the tip for this order.
     */
    public function tip(): HasOne
    {
        return $this->hasOne(Tip::class);
    }

    /**
     * Calculate and update order totals.
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->orderItems()->sum('subtotal');

        // Get tax rate from settings, default to 18% if not set
        $taxRate = Setting::get('tax_rate', 18) / 100;
        $tax = $subtotal * $taxRate;
        $total = $subtotal + $tax;

        $this->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ]);
    }

    /**
     * Update order status.
     */
    public function updateStatus(string $status): void
    {
        $this->update(['status' => $status]);
    }

    /**
     * Check if order is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if order is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
