<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'is_secret',
        'description',
    ];

    protected $casts = [
        'is_secret' => 'boolean',
    ];

    /**
     * Get a setting by key with optional default fallback
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $setting = static::where('key', $key)->first();

        return $setting && $setting->value !== null ? $setting->value : $default;
    }

    /**
     * Set/update a setting by key
     */
    public static function set(string $key, ?string $value, string $group = 'general', bool $isSecret = false, ?string $description = null): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $group,
                'is_secret' => $isSecret,
                'description' => $description,
            ]
        );
    }
}
