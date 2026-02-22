<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'discount_type',
        'discount_value',
        'applicable_items',
        'day_of_week',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'applicable_items' => 'array',
        'is_active' => 'boolean',
        'discount_value' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Active promotions within their date range.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', today());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', today());
            });
    }

    /**
     * Promotions valid for today's day of week.
     */
    public function scopeForToday(Builder $query): Builder
    {
        $today = strtolower(now()->format('l'));

        return $query->where(function ($q) use ($today) {
            $q->whereNull('day_of_week')->orWhere('day_of_week', $today);
        });
    }

    /**
     * Happy hour promotions currently in their time window.
     */
    public function scopeCurrentlyRunning(Builder $query): Builder
    {
        $now = now()->format('H:i:s');

        return $query->active()->forToday()
            ->where(function ($q) use ($now) {
                $q->whereNull('start_time')
                    ->orWhere(function ($q2) use ($now) {
                        $q2->where('start_time', '<=', $now)
                            ->where('end_time', '>=', $now);
                    });
            });
    }

    /**
     * Get display status: Active, Scheduled, Expired, Inactive.
     */
    public function getStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'Inactive';
        }
        if ($this->end_date && $this->end_date->lt(today())) {
            return 'Expired';
        }
        if ($this->start_date && $this->start_date->gt(today())) {
            return 'Scheduled';
        }

        return 'Active';
    }
}
