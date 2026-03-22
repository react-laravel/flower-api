<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    private const CACHE_TTL_SECONDS = 300; // 5 minutes

    public static function get($key, $default = null)
    {
        $cacheKey = 'site_setting:' . $key;

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function set($key, $value)
    {
        // Invalidate cache on write
        Cache::forget('site_setting:' . $key);

        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
