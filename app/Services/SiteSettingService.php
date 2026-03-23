<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Site setting service handling configuration business logic.
 * Extracted from SiteSettingController to fix SRP violation.
 * Also fixes OCP, LSP, and DIP violations by providing an injectable service.
 */
class SiteSettingService
{
    private const CACHE_TTL_SECONDS = 300; // 5 minutes
    private const CACHE_KEY_PREFIX = 'site_setting:';

    /**
     * Get a setting value by key.
     * Results are cached for CACHE_TTL_SECONDS to avoid repeated DB queries.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            self::CACHE_KEY_PREFIX . $key,
            self::CACHE_TTL_SECONDS,
            fn () => $this->getUncached($key, $default)
        );
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, mixed $value): SiteSetting
    {
        $setting = SiteSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        // Bust the cache so next get() reflects the new value
        Cache::forget(self::CACHE_KEY_PREFIX . $key);

        return $setting;
    }

    /**
     * Get all settings as key-value pairs.
     */
    public function all(): Collection
    {
        return SiteSetting::all()->pluck('value', 'key');
    }

    /**
     * Get multiple settings by keys.
     * Uses a single DB query; result array preserves key order.
     *
     * @param array<string> $keys
     * @return array<string, mixed>
     */
    public function getMany(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $values = SiteSetting::whereIn('key', $keys)->get()->pluck('value', 'key');

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $values[$key] ?? null;
        }

        return $result;
    }

    /**
     * Batch update multiple settings.
     * Invalidates cache for each updated key.
     *
     * @param array<string, mixed> $settings
     */
    public function batchSet(array $settings): void
    {
        foreach ($settings as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
            Cache::forget(self::CACHE_KEY_PREFIX . $key);
        }
    }

    /**
     * Check if a setting exists.
     */
    public function has(string $key): bool
    {
        return SiteSetting::where('key', $key)->exists();
    }

    /**
     * Delete a setting.
     */
    public function delete(string $key): bool
    {
        $deleted = SiteSetting::where('key', $key)->delete() > 0;

        if ($deleted) {
            Cache::forget(self::CACHE_KEY_PREFIX . $key);
        }

        return $deleted;
    }

    /**
     * Uncached read — used internally and by cache misses.
     */
    private function getUncached(string $key, mixed $default): mixed
    {
        $setting = SiteSetting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}
