<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\ReliableOperations;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SiteSettingController extends Controller
{
    use ApiResponse, ReliableOperations;

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

        $settings = SiteSetting::all()->pluck('value', 'key');
        return $this->success($settings);
    }

    /**
     * Update or create a setting with transaction and locking
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'nullable|string',
        ]);

        $lockKey = 'sitesetting:' . $request->key;

        try {
            return $this->lock()->withLock(
                $lockKey,
                function () use ($request) {
                    return $this->withTransaction(function () use ($request) {
                        SiteSetting::set($request->key, $request->value);

                        Log::info("SiteSettingController: Updated setting", [
                            'key' => $request->key,
                        ]);

                        return $this->success(null, '设置已更新');
                    });
                }
            );
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            return $this->error('操作太频繁，请稍后重试', 409);
        }
    }

    /**
     * Batch update settings with transaction and idempotency
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        // Check idempotency
        $idempotencyCheck = $this->checkIdempotency($request);
        if ($idempotencyCheck !== null) {
            return $idempotencyCheck;
        }

        $settings = $request->validate([
            'settings' => 'required|array',
        ]);

        try {
            $response = $this->withTransaction(function () use ($settings) {
                foreach ($settings['settings'] as $key => $value) {
                    SiteSetting::set($key, $value);
                }

                Log::info("SiteSettingController: Batch updated settings", [
                    'count' => count($settings['settings']),
                ]);

                return $this->success(null, '设置已批量更新');
            });

            $this->markIdempotencyProcessed($request, $response);

            return $response;
        } catch (\Exception $e) {
            Log::error("SiteSettingController: Batch update failed", [
                'error' => $e->getMessage(),
            ]);
            return $this->error('批量更新失败：' . $e->getMessage(), 500);
        }
    }
}
