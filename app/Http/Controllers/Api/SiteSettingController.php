<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\Idempotency;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiteSettingController extends Controller
{
    use ApiResponse, Idempotency;

    /**
     * Pre-compiled regex for sensitive key detection (case-insensitive).
     * Extracted from inline array + preg_match calls to fix regex-rebuilt-on-every-call smell.
     */
    private const SENSITIVE_REGEX = '/(smtp_|aws_|password|secret|key|token|credential|auth)/i';

    /**
     * Get all settings or a specific setting
     * Note: only returns non-sensitive public settings via the bulk endpoint.
     * Sensitive keys (password, secret, key, token) require admin auth.
     */
    public function index(Request $request): JsonResponse
    {
        $key = $request->query('key');

        if ($key) {
            if (preg_match(self::SENSITIVE_REGEX, $key)) {
                return $this->error('无效的设置键', 400);
            }

            $value = SiteSetting::getValue($key);
            return $this->success($value);
        }

        $settings = SiteSetting::all()->pluck('value', 'key')
            ->filter(fn($value, $settingKey) => !preg_match(
                self::SENSITIVE_REGEX,
                $settingKey
            ));

        return $this->success($settings);
    }

    /**
     * Update or create a setting
     */
    public function update(Request $request): JsonResponse
    {
        return $this->handleIdempotentRequest($request, function () use ($request) {
            $this->authorize('update', new SiteSetting());

            $request->validate([
                'key' => 'required|string',
                'value' => 'nullable|string',
            ]);

            return DB::transaction(function () use ($request) {
                SiteSetting::setValue($request->key, $request->value);
                return $this->success(null, '设置已更新');
            });
        });
    }

    /**
     * Batch update settings
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        return $this->handleIdempotentRequest($request, function () use ($request) {
            $this->authorize('update', new SiteSetting());

            $settings = $request->validate([
                'settings' => 'required|array',
            ]);

            return DB::transaction(function () use ($settings) {
                foreach ($settings['settings'] as $key => $value) {
                    SiteSetting::setValue($key, $value);
                }
                return $this->success(null, '设置已批量更新');
            });
        });
    }
}
