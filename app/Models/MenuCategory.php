<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MenuCategory extends Model
{
    use HasFactory;

    /**
     * Cache key for menu data
     */
    const CACHE_KEY = 'menu_categories_with_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'display_order',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'display_order' => 'integer',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('ordered', function ($query) {
            $query->orderBy('display_order');
        });

        // Clear menu cache when categories are created, updated, or deleted
        static::created(function () {
            self::clearMenuCache();
        });

        static::updated(function () {
            self::clearMenuCache();
        });

        static::deleted(function () {
            self::clearMenuCache();
        });
    }

    /**
     * Get cached menu categories with their items.
     * Cache for 1 hour (3600 seconds).
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getCachedMenu()
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            return self::with(['menuItems' => function ($query) {
                $query->where('status', 'available');
            }])->where('status', 'active')->get();
        });
    }

    /**
     * Clear the menu cache.
     *
     * @return void
     */
    public static function clearMenuCache()
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get the menu items for the category.
     */
    public function menuItems()
    {
        return $this->hasMany(MenuItem::class, 'category_id');
    }
}
