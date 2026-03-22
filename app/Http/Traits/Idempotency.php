<?php

namespace App\Http\Traits;

use App\Services\IdempotencyService;
use Illuminate\Http\JsonResponse;

trait Idempotency
{
    protected IdempotencyService $idempotencyService;

    /**
     * Initialize the idempotency service
     */
    protected function initIdempotency(): void
    {
        $this->idempotencyService = new IdempotencyService();
    }

    /**
     * Get idempotency key from request header
     */
    protected function getIdempotencyKey(\Illuminate\Http\Request $request): ?string
    {
        return $request->header('X-Idempotency-Key');
    }

    /**
     * Handle idempotent request
     * Returns cached response if already processed, or executes handler for new request
     * Ensures proper transaction boundary - handler executes within transaction,
     * then response is cached only after successful commit
     */
    protected function handleIdempotentRequest(
        \Illuminate\Http\Request $request,
        callable $handler
    ): JsonResponse {
        $this->initIdempotency();
        $idempotencyKey = $this->getIdempotencyKey($request);

        // If no idempotency key, proceed normally without locking
        if (!$idempotencyKey) {
            return $handler();
        }

        // Check if already processed - return cached response if so
        if ($this->idempotencyService->isProcessed($idempotencyKey)) {
            $cached = $this->idempotencyService->getResponse($idempotencyKey);
            if ($cached) {
                return response()->json([
                    'success' => true,
                    'data' => $cached['response']['data'] ?? null,
                    'message' => $cached['response']['message'] ?? null,
                    'idempotent' => true,
                ], $cached['response']['status'] ?? 200);
            }
        }

        // Check if currently being processed by another request
        if ($this->idempotencyService->isLocked($idempotencyKey)) {
            return $this->error('请求正在处理中，请稍后重试', 409);
        }

        // Acquire lock for processing (atomic operation)
        if (!$this->idempotencyService->acquireLock($idempotencyKey, 30)) {
            return $this->error('无法获取处理权限，请稍后重试', 409);
        }

        try {
            // Execute the handler (should include DB::transaction for write operations)
            $response = $handler();

            // Cache the response after handler succeeds and transaction commits
            // If this fails, we still return the response but log the error
            // as the DB operation has already succeeded
            $this->cacheResponse($idempotencyKey, $response);

            return $response;
        } finally {
            $this->idempotencyService->releaseLock($idempotencyKey);
        }
    }

    /**
     * Cache the response for idempotency
     * Failures are logged but don't prevent response from being returned
     */
    private function cacheResponse(string $idempotencyKey, JsonResponse $response): void
    {
        try {
            $responseData = json_decode($response->getContent(), true);
            $this->idempotencyService->markProcessed($idempotencyKey, [
                'data' => $responseData['data'] ?? null,
                'message' => $responseData['message'] ?? null,
                'status' => $response->getStatusCode(),
            ]);
        } catch (\Throwable $e) {
            // Log the error but don't fail the request
            // The response was already generated and transaction committed
            // On retry, a new idempotency record will be created
            \Log::error('Failed to cache idempotency response', [
                'key' => $idempotencyKey,
                'error' => $e->getMessage(),
            ]);
        }
    }
}