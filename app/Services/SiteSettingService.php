<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Collection;

/**
 * Site setting service handling configuration business logic.
 * Extracted from SiteSettingController to fix SRP violation.
 * Also fixes OCP, LSP, and DIP violations by providing an injectable service.
 */
class SiteSettingService
{
    /**
     * Get a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = SiteSetting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, mixed $value): SiteSetting
    {
        return SiteSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
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
     */
    public function getMany(array $keys): array
    {
        $settings = SiteSetting::whereIn('key', $keys)->get();
        $result = [];

        foreach ($keys as $key) {
            $setting = $settings->firstWhere('key', $key);
            $result[$key] = $setting ? $setting->value : null;
        }

        return $result;
    }

    /**
     * Batch update multiple settings.
     */
    public function batchSet(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value);
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
        return SiteSetting::where('key', $key)->delete() > 0;
    }
}
