<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\Idempotency;
use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteSettingController extends Controller
{
    use ApiResponse, Idempotency;

    /**
     * Patterns used to detect sensitive setting keys that should not be
     * exposed via the public API.
     */
    private const SENSITIVE_PATTERNS = [
        'smtp_', 'aws_', 'password', 'secret', 'token', 'credential',
        'sendgrid_', 'mailgun_', 'twilio_', 'stripe_', 'slack_',
        'github_', 'openai_', 'mailchimp_', 'fb_|facebook_', 'google_',
        'jwt_', 'private_', 'encryption_', 'paypal_',
    ];

    public function __construct(private SiteSettingService $siteSettingService)
    {
    }

    /**
     * Get all settings or a specific setting
     * Note: only returns non-sensitive public settings via the bulk endpoint.
     * Sensitive keys require admin auth.
     */
    public function index(Request $request): JsonResponse
    {
        $key = $request->query('key');

        if ($key) {
            if ($this->keyMatchesSensitivePattern($key)) {
                return $this->error('无效的设置键', 400);
            }

            $value = $this->siteSettingService->get($key);
            return $this->success($value);
        }

        // Filter out potentially sensitive keys from public response
        $settings = $this->siteSettingService->all()
            ->filter(fn($value, $settingKey) => !$this->keyMatchesSensitivePattern($settingKey));

        return $this->success($settings);
    }

    private function keyMatchesSensitivePattern(string $key): bool
    {
        return (bool) preg_match(
            '/(' . implode('|', self::SENSITIVE_PATTERNS) . ')/i',
            $key
        );
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

            $this->siteSettingService->set($request->key, $request->value);

            return $this->success(null, '设置已更新');
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

            $this->siteSettingService->batchSet($settings['settings']);

            return $this->success(null, '设置已批量更新');
        });
    }
}
