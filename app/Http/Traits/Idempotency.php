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
     * Returns cached response if already processed, or executes handler for new request.
     *
     * Concurrency safety: uses atomic lock acquisition FIRST to prevent race conditions.
     * If lock is acquired, we won the race — check isProcessed() to distinguish
     * "first request ever" (process it) from "we lost race and someone else is processing"
     * (wait and return 409).
     *
     * Transaction safety: handler is responsible for wrapping writes in DB::transaction().
     * Response is cached BEFORE returning to ensure atomicity: if caching fails after
     * the handler succeeds, we log and continue (DB is source of truth on retry).
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

        // ATTEMPT 1: Try to acquire lock (atomic SETNX). This is the primary gate.
        // If we get the lock, we might be the first request OR we might have beaten
        // a concurrent retry. We differentiate by checking isProcessed().
        if ($this->idempotencyService->acquireLock($idempotencyKey, 30)) {
            try {
                // Double-check: another request may have processed this key while
                // we were acquiring the lock (e.g., our own retry after lock expiry).
                if ($this->idempotencyService->isProcessed($idempotencyKey)) {
                    $cached = $this->idempotencyService->getResponse($idempotencyKey);
                    if ($cached) {
                        return response()->json([
                            'success' => true,
                            'data' => $cached['response']['data'] ?? null,
                            'message' => $cached['response']['message'] ?? null,
                            'idempotent' => true,
                        ], 200); // Always 200 for cached responses
                    }
                }

                // Execute the handler (should include DB::transaction for write operations)
                $response = $handler();

                // Cache the response (before returning) to maintain atomicity:
                // if handler succeeded but caching fails, the DB commit happened but
                // response isn't cached — on retry the handler runs again (harmless
                // idempotent duplicate) rather than returning an inconsistent response.
                $this->cacheResponse($idempotencyKey, $response);

                return $response;
            } finally {
                $this->idempotencyService->releaseLock($idempotencyKey);
            }
        }

        // Lock not acquired — another request holds it.
        // The lock holder may still be processing OR may have finished and released the lock
        // (e.g., our own retry after the lock TTL expired but before isProcessed was set).
        // Check isProcessed first: if already done, return cached (idempotent retry).
        // Otherwise, another request IS actively processing → return 409 conflict.
        if ($this->idempotencyService->isProcessed($idempotencyKey)) {
            $cached = $this->idempotencyService->getResponse($idempotencyKey);
            if ($cached) {
                return response()->json([
                    'success' => true,
                    'data' => $cached['response']['data'] ?? null,
                    'message' => $cached['response']['message'] ?? null,
                    'idempotent' => true,
                ], 200); // Always 200 for cached response (not 201 which was the original create status)
            }
        }

        // Another request is actively processing — return conflict
        return $this->error('请求正在处理中，请稍后重试', 409);
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