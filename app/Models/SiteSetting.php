<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static $cachePrefix = 'site_setting:';
    protected static $cacheTtlSeconds = 3600;

    public static function getValue($key, $default = null)
    {
        $cacheKey = static::normalizeKey($key);

        return Cache::remember($cacheKey, static::$cacheTtlSeconds, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function setValue($key, $value)
    {
        $cacheKey = static::normalizeKey($key);
        Cache::forget($cacheKey);

        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    protected static function normalizeKey(string $key): string
    {
        return static::$cachePrefix . $key;
    }
}
