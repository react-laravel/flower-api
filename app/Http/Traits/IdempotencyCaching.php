<?php

namespace App\Http\Traits;

use App\Services\IdempotencyService;
use Illuminate\Http\JsonResponse;

/**
 * Handles response caching for idempotency.
 * Part of the Idempotency trait split to reduce complexity.
 */
trait IdempotencyCaching
{
    protected IdempotencyService $idempotencyService;

    /**
     * Get cached response for the idempotency key.
     */
    protected function getCachedResponse(string $idempotencyKey): ?array
    {
        return $this->idempotencyService->getResponse($idempotencyKey);
    }

    /**
     * Mark the idempotency key as processed with the given response data.
     */
    protected function markProcessed(string $idempotencyKey, array $responseData): void
    {
        $this->idempotencyService->markProcessed($idempotencyKey, $responseData);
    }

    /**
     * Build a JsonResponse from a cached idempotency entry.
     */
    protected function buildCachedResponse(array $cached): JsonResponse
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
    protected function cacheResponseOrFail(string $idempotencyKey, JsonResponse $response): void
    {
        $responseData = json_decode($response->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                'Failed to decode response JSON for idempotency caching: ' . json_last_error_msg()
            );
        }

        try {
            $this->markProcessed($idempotencyKey, [
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
            throw new \RuntimeException(
                'Idempotency cache failed after handler success. Request failed to prevent duplicate execution on retry.',
                0,
                $e
            );
        }
    }
}
