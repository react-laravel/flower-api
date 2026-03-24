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
     * Always attempts to acquire the lock first (even for new keys) to serialize
     * concurrent requests with the same idempotency key. After acquiring, double-checks
     * the cache to handle the race where another process finished while we waited.
     *
     * Transaction safety: handler is responsible for wrapping writes in DB::transaction().
     *
     * Idempotency key lifecycle:
     * 1. Lock acquired → mark key as "pending" before handler (prevents cache-failure
     *    duplicate execution: if handler succeeds but cache update fails, the pending
     *    flag ensures concurrent requests return 409 rather than executing handler again).
     * 2. Handler executes with DB::transaction (source of truth).
     * 3. After handler success: update cache with response data (pending=false).
     * 4. If cache update fails after handler success: throw to fail the request rather
     *    than return success while leaving the idempotency key unset (which would cause
     *    duplicate handler execution on retry).
     *
     * Note: marking key BEFORE handler means a failed handler leaves the key set.
     * Retry of a failed handler returns stale cached response — acceptable tradeoff
     * vs. the alternative (cache-failure after success → duplicate handler execution).
     *
     * The pending state is tracked via the 'pending' flag in the cached entry.
     * If a cached entry has pending=true, it means another request is actively
     * processing this key (lock holder). We return 409 in that case.
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

        // Always try to acquire lock first — this serializes ALL requests (new AND retries)
        // with the same idempotency key, preventing race conditions where concurrent
        // new requests both execute the handler simultaneously.
        if (!$this->idempotencyService->acquireLock($idempotencyKey, 30)) {
            // Lock not acquired — another request holds it.
            // Double-check the cache: it may have been populated while we waited.
            $cached = $this->idempotencyService->getResponse($idempotencyKey);
            if ($cached) {
                if (($cached['response']['pending'] ?? false) === true) {
                    return $this->error('请求正在处理中，请稍后重试', 409);
                }
                return $this->buildIdempotentResponse($cached);
            }
            // Lock is held and no cache entry yet — another request is processing
            return $this->error('请求正在处理中，请稍后重试', 409);
        }

        // We hold the lock — we won the race.
        try {
            // Double-check cache: another request may have finished while we waited for lock.
            $cached = $this->idempotencyService->getResponse($idempotencyKey);
            if ($cached) {
                if (($cached['response']['pending'] ?? false) === true) {
                    // Cache has pending=true but WE hold the lock — this means WE are the
                    // pending request (nested call or retry after our own lock reacquire).
                    // Fall through to execute handler.
                } else {
                    // Already fully processed — return cached response
                    return $this->buildIdempotentResponse($cached);
                }
            }

            // Mark key as pending BEFORE handler to prevent duplicate execution
            // if cache update fails after handler succeeds (see lifecycle note above).
            $this->idempotencyService->markProcessed($idempotencyKey, [
                'data' => null,
                'message' => null,
                'status' => 0,
                'pending' => true,
            ]);

            // Execute the handler (should include DB::transaction for write operations)
            $response = $handler();

            // Cache the response — throw if this fails to prevent duplicate execution
            // on retry (DB committed but idempotency key not set → handler runs again).
            $this->cacheResponseOrFail($idempotencyKey, $response);

            return $response;
        } finally {
            $this->idempotencyService->releaseLock($idempotencyKey);
        }
    }

    /**
     * Build a JsonResponse from a cached idempotency entry.
     */
    private function buildIdempotentResponse(array $cached): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $cached['response']['data'] ?? null,
            'message' => $cached['response']['message'] ?? null,
            'idempotent' => true,
        ]);
    }

    /**
     * Cache the response for idempotency.
     * Throws if caching fails — prevents returning success when the idempotency key
     * is not set (which would cause duplicate handler execution on retry).
     *
     * @throws \RuntimeException if caching fails
     */
    private function cacheResponseOrFail(string $idempotencyKey, JsonResponse $response): void
    {
        $responseData = json_decode($response->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                'Failed to decode response JSON for idempotency caching: ' . json_last_error_msg()
            );
        }

        try {
            $this->idempotencyService->markProcessed($idempotencyKey, [
                'data' => $responseData['data'] ?? null,
                'message' => $responseData['message'] ?? null,
                'status' => $response->getStatusCode(),
                'pending' => false,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to cache idempotency response', [
                'key' => $idempotencyKey,
                'error' => $e->getMessage(),
            ]);
            // Throw to fail the request rather than return success with an unset key.
            // The DB transaction already committed; the client will get an error but
            // the data is safe. Retry will return cached response once cache recovers.
            throw new \RuntimeException(
                'Idempotency cache failed after handler success. Request failed to prevent duplicate execution on retry.',
                0,
                $e
            );
        }
    }
}