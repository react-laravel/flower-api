<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class IdempotencyService
{
    private const DEFAULT_TTL = 86400; // 24 hours
    private ?string $cacheStore;

    public function __construct(string $cacheStore = null)
    {
        $this->cacheStore = $cacheStore;
    }

    /**
     * Get the cache store instance
     * Uses the configured default store if no specific store was set
     */
    protected function cache(): \Illuminate\Contracts\Cache\Repository
    {
        // Use explicit store to ensure consistency - prefer 'array' for testing
        $store = $this->cacheStore ?? config('cache.default', 'array');
        return Cache::store($store);
    }

    /**
     * Check if a request with this idempotency key has already been processed
     */
    public function isProcessed(string $key): bool
    {
        return $this->cache()->has($this->getCacheKey($key));
    }

    /**
     * Mark a request as processed and store the response
     */
    public function markProcessed(string $key, mixed $response, int $ttl = self::DEFAULT_TTL): void
    {
        $cacheKey = $this->getCacheKey($key);
        $this->cache()->put($cacheKey, [
            'response' => $response,
            'processed_at' => now()->toIso8601String(),
        ], $ttl);
    }

    /**
     * Get the cached response for an idempotency key
     */
    public function getResponse(string $key): ?array
    {
        return $this->cache()->get($this->getCacheKey($key));
    }

    /**
     * Check if a lock is held (for distributed locking)
     */
    public function isLocked(string $key): bool
    {
        return $this->cache()->has($this->getLockKey($key));
    }

    /**
     * Acquire a lock for processing (atomic operation)
     */
    public function acquireLock(string $key, int $ttl = 30): bool
    {
        $lockKey = $this->getLockKey($key);
        // Use add() for atomic lock acquisition (SETNX behavior)
        // Returns true if lock was acquired, false if already held
        return $this->cache()->add($lockKey, [
            'locked_at' => now()->toIso8601String(),
            'owner' => uniqid('lock_', true),
        ], $ttl);
    }

    /**
     * Release a lock
     */
    public function releaseLock(string $key): void
    {
        $this->cache()->forget($this->getLockKey($key));
    }

    /**
     * Generate a cache key for the idempotency key
     */
    private function getCacheKey(string $key): string
    {
        return 'idempotency:' . $key;
    }

    /**
     * Generate a lock key
     */
    private function getLockKey(string $key): string
    {
        return 'idempotency_lock:' . $key;
    }
}