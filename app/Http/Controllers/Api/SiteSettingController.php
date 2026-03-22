<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SiteSettingController extends Controller
{
    use ApiResponse;

    private const MAX_KEY_LENGTH = 128;
    private const MAX_BATCH_SIZE = 50;

    /**
     * Get all settings or a specific setting
     */
    public function index(Request $request): JsonResponse
    {
        $key = $request->query('key');

        if ($key) {
            $value = SiteSetting::get($key);
            return $this->success($value);
        }

        $settings = Cache::remember('site_settings_all', 300, function () {
            return SiteSetting::all()->pluck('value', 'key');
        });

        return $this->success($settings);
    }

    /**
     * Update or create a setting
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string|max:' . self::MAX_KEY_LENGTH,
            'value' => 'nullable|string',
        ]);

        SiteSetting::set($request->key, $request->value);

        // Invalidate the all-settings index cache
        Cache::forget('site_settings_all');

        return $this->success(null, '设置已更新');
    }

    /**
     * Batch update settings
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        $settings = $request->validate([
            'settings' => 'required|array|max:' . self::MAX_BATCH_SIZE,
        ]);

        // Validate each key in the batch
        foreach (array_keys($settings['settings']) as $key) {
            if (!is_string($key) || strlen($key) > self::MAX_KEY_LENGTH) {
                return $this->error('无效的设置键', 422);
            }
        }

        // Wrap in transaction: all-or-nothing for data integrity
        DB::transaction(function () use ($settings) {
            foreach ($settings['settings'] as $key => $value) {
                SiteSetting::set($key, $value);
            }
        });

        // Invalidate the all-settings index cache after batch update
        Cache::forget('site_settings_all');

        return $this->success(null, '设置已批量更新');
    }
}
