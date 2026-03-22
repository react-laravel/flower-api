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

    private const SENSITIVE_PATTERNS = [
        'smtp_', 'aws_', 'password', 'secret', 'key', 'token', 'credential', 'auth',
    ];

    /**
     * Get all settings or a specific setting
     * Note: only returns non-sensitive public settings via the bulk endpoint.
     * Sensitive keys (password, secret, key, token) require admin auth.
     */
    public function index(Request $request): JsonResponse
    {
        $key = $request->query('key');

        if ($key) {
            if ($this->keyMatchesSensitivePattern($key)) {
                return $this->error('无效的设置键', 400);
            }

            $value = SiteSetting::getValue($key);
            return $this->success($value);
        }

        $settings = SiteSetting::all()->pluck('value', 'key')
            ->filter(fn($value, $settingKey) => !$this->keyMatchesSensitivePattern($settingKey));

        return $this->success($settings);
    }

    private function keyMatchesSensitivePattern(string $key): bool
    {
        return (bool) preg_match('/(' . implode('|', self::SENSITIVE_PATTERNS) . ')/i', $key);
    }

    /**
     * Update or create a setting
     */
    public function update(Request $request): JsonResponse
    {
        $this->authorize('update', SiteSetting::class);

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
        $this->authorize('update', SiteSetting::class);

        $settings = $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($settings['settings'] as $key => $value) {
            SiteSetting::setValue($key, $value);
        }

        return $this->success(null, '设置已批量更新');
    }
}
