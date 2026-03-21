<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteSettingController extends Controller
{
    use ApiResponse;

    /**
     * Get all settings or a specific setting
     */
    public function index(Request $request): JsonResponse
    {
        $key = $request->query('key');

        if ($key) {
            $value = SiteSetting::getValue($key);
            return $this->success($value);
        }

        $settings = SiteSetting::all()->pluck('value', 'key');
        return $this->success($settings);
    }

    /**
     * Update or create a setting
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'nullable|string',
        ]);

        SiteSetting::setValue($request->key, $request->value);

        return $this->success(null, '设置已更新');
    }

    /**
     * Batch update settings
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        $settings = $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($settings['settings'] as $key => $value) {
            SiteSetting::setValue($key, $value);
        }

        return $this->success(null, '设置已批量更新');
    }
}
