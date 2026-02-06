<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    /**
     * Get a setting value by key with optional default
     *
     * @param  mixed  $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        // Cache settings for 1 hour to reduce database queries
        $setting = Cache::remember("setting.{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (! $setting) {
            return $default;
        }

        // Cast value based on type
        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value by key
     *
     * @param  mixed  $value
     */
    public static function set(string $key, $value, string $type = 'string'): bool
    {
        // Convert value to string for storage
        $storedValue = is_array($value) || is_object($value)
            ? json_encode($value)
            : (string) $value;

        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
            ]
        );

        // Clear cache for this setting
        Cache::forget("setting.{$key}");

        return $setting->wasRecentlyCreated || $setting->wasChanged();
    }

    /**
     * Cast value based on type
     *
     * @param  string  $value
     * @return mixed
     */
    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
