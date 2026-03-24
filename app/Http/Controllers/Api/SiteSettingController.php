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

    /** @var array<string> Patterns matching sensitive setting keys */
    private const SENSITIVE_KEY_PATTERNS = [
        'smtp_', 'aws_', 'password', 'secret', 'key', 'token', 'credential', 'auth',
    ];

    /**
     * Get all settings, or a single setting by ?key= query param.
     * Sensitive keys are always filtered out.
     */
    public function index(Request $request): JsonResponse
    {
        $key = $request->query('key');

        if ($key) {
            if (self::matchesSensitivePattern($key)) {
                return $this->error('无效的设置键', 400);
            }
            $value = SiteSetting::getValue($key);
            return $this->success($value);
        }

        // Bulk listing — filter out sensitive keys
        $settings = SiteSetting::all()->pluck('value', 'key')
            ->filter(fn($value, $settingKey) => !self::matchesSensitivePattern($settingKey));

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

    /**
     * Check if a setting key matches sensitive patterns.
     */
    private static function matchesSensitivePattern(string $key): bool
    {
        return (bool) preg_match(
            '/(' . implode('|', self::SENSITIVE_KEY_PATTERNS) . ')/i',
            $key
        );
    }
}
