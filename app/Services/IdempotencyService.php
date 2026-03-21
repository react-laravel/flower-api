<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service to handle idempotency for API requests.
 * Prevents duplicate submissions when users accidentally double-click or retry requests.
 */
class IdempotencyService
{
    /**
     * Idempotency key TTL in seconds (default: 24 hours)
     */
    protected int $ttl;

    /**
     * Cache store to use
     */
    protected string $store;

    public function __construct()
    {
        $this->ttl = 86400; // 24 hours
        $this->store = config('cache.default', 'database');
    }

    /**
     * Check if an idempotency key has already been processed.
     *
     * @param string|null $key The idempotency key
     * @return bool True if already processed (duplicate), false if new
     */
    public function isDuplicate(?string $key): bool
    {
        if ($key === null || $key === '') {
            return false;
        }

        $cacheKey = $this->getCacheKey($key);

        // Use atomic lock check - if the key exists, it's a duplicate
        if (Cache::store($this->store)->has($cacheKey)) {
            Log::info("Idempotency: Duplicate request detected", ['key' => $key]);
            return true;
        }

        return false;
    }

    /**
     * Mark an idempotency key as processed.
     *
     * @param string $key The idempotency key
     * @param mixed $response The response data to return for duplicate requests
     * @return bool True if successfully marked, false if already existed
     */
    public function markProcessed(string $key, mixed $response = null): bool
    {
        if (empty($key)) {
            return false;
        }

        $cacheKey = $this->getCacheKey($key);

        // The key should already exist (added by checkAndMark)
        // We need to update it with the actual response
        Cache::store($this->store)->put($cacheKey, [
            'processed_at' => now()->toIso8601String(),
            'response' => $response,
        ], $this->ttl);

        Log::info("Idempotency: Request marked as processed", ['key' => $key]);

        return true;
    }

    /**
     * Get the cached response for an already-processed idempotency key.
     *
     * @param string $key The idempotency key
     * @return mixed The cached response or null
     */
    public function getProcessedResponse(string $key): mixed
    {
        if (empty($key)) {
            return null;
        }

        $cacheKey = $this->getCacheKey($key);
        $data = Cache::store($this->store)->get($cacheKey);

        return $data['response'] ?? null;
    }

    /**
     * Check and mark in one atomic operation.
     * Returns the existing response if duplicate, or null if new.
     *
     * @param string $key The idempotency key
     * @param mixed $response The response to cache if new
     * @return mixed The existing response if duplicate, null if new request
     */
    public function checkAndMark(string $key, mixed $response = null): mixed
    {
        if (empty($key)) {
            return null;
        }

        $cacheKey = $this->getCacheKey($key);

        // Check if key exists first
        if (Cache::store($this->store)->has($cacheKey)) {
            // Key exists - return cached response (if any)
            return $this->getProcessedResponse($key);
        }

        // Key doesn't exist - try to add it with pending status
        $pending = ['status' => 'pending', 'processed_at' => now()->toIso8601String()];
        $result = Cache::store($this->store)->add($cacheKey, $pending, $this->ttl);

        if (!$result) {
            // Another process already added it - get the response
            return $this->getProcessedResponse($key);
        }

        return null;
    }

    /**
     * Remove an idempotency key (for testing or manual cleanup).
     *
     * @param string $key The idempotency key
     * @return bool
     */
    public function remove(string $key): bool
    {
        if (empty($key)) {
            return false;
        }

        return Cache::store($this->store)->forget($this->getCacheKey($key));
    }

    /**
     * Generate a cache key for the idempotency key.
     *
     * @param string $key The idempotency key
     * @return string
     */
    protected function getCacheKey(string $key): string
    {
        return "idempotency:{$key}";
    }

    /**
     * Set a custom TTL.
     *
     * @param int $seconds
     * @return self
     */
    public function setTtl(int $seconds): self
    {
        $this->ttl = $seconds;
        return $this;
    }

    /**
     * Get the TTL.
     *
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }
}
