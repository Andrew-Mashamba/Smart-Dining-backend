<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'prep_area',
        'prep_time_minutes',
        'status',
        'stock_quantity',
        'unit',
        'low_stock_threshold',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'prep_time_minutes' => 'integer',
        'stock_quantity' => 'integer',
    ];

    /**
     * Get the category that owns the menu item.
     */
    public function menuCategory(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    /**
     * Get all order items for this menu item.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get all inventory transactions for this menu item.
     */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Scope to get only available items.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope to get items by prep area.
     */
    public function scopePrepArea($query, string $prepArea)
    {
        return $query->where('prep_area', $prepArea);
    }

    /**
     * The "booted" method of the model.
     * Clear menu cache when menu items are modified.
     */
    protected static function booted(): void
    {
        static::created(function () {
            MenuCategory::clearMenuCache();
        });

        static::updated(function () {
            MenuCategory::clearMenuCache();
        });

        static::deleted(function () {
            MenuCategory::clearMenuCache();
        });
    }
}
