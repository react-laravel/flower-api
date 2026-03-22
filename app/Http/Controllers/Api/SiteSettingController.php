<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\Idempotency;
use App\Models\SiteSetting;
use App\Services\DistributedLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiteSettingController extends Controller
{
    use ApiResponse;
    use Idempotency;

    private DistributedLockService $lockService;

    public function __construct()
    {
        $this->lockService = new DistributedLockService();
    }

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
     * Update or create a setting
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'nullable|string',
        ]);

        return $this->handleIdempotentRequest($request, function () use ($validated) {
            return $this->withLock('settings_update_' . $validated['key'], function () use ($validated) {
                SiteSetting::set($validated['key'], $validated['value']);
                return $this->success(null, '设置已更新');
            });
        });
    }

    /**
     * Batch update settings with proper transaction and distributed locking
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        return $this->handleIdempotentRequest($request, function () use ($request) {
            $validated = $request->validate([
                'settings' => 'required|array',
            ]);

            return $this->withLock('settings_batch_update', function () use ($validated) {
                return DB::transaction(function () use ($validated) {
                    foreach ($validated['settings'] as $key => $value) {
                        SiteSetting::set($key, $value);
                    }
                    return $this->success(null, '设置已批量更新');
                });
            });
        });
    }

    /**
     * Execute callback with distributed lock
     */
    private function withLock(string $key, callable $callback): JsonResponse
    {
        $token = $this->lockService->acquire($key, 30);

        if (!$token) {
            return $this->error('服务忙，请稍后重试', 409);
        }

        try {
            $result = $callback();
            return $result instanceof JsonResponse ? $result : $this->success($result);
        } finally {
            $this->lockService->release($key, $token);
        }
    }
}
