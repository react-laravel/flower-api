<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class IdempotencyService
{
    private const DEFAULT_TTL = 86400; // 24 hours
    private string $cacheStore;

    public function __construct(string $cacheStore = null)
    {
        $this->cacheStore = $cacheStore ?? config('cache.default', 'redis');
    }

    /**
     * Get the cache store instance
     */
    protected function cache(): \Illuminate\Contracts\Cache\Repository
    {
        return Cache::store($this->cacheStore);
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
     *
     * @return string|false Returns lock token if acquired, false if already held
     */
    public function acquireLock(string $key, int $ttl = 30): string|false
    {
        $lockToken = uniqid('lock_', true);
        $lockKey = $this->getLockKey($key);
        // Use add() for atomic lock acquisition (SETNX behavior)
        // Returns true if lock was acquired, false if already held
        $acquired = $this->cache()->add($lockKey, [
            'token' => $lockToken,
            'locked_at' => now()->toIso8601String(),
        ], $ttl);

        if ($acquired) {
            return $lockToken;
        }

        return false;
    }

    /**
     * Release a lock (only if owned by the given token)
     *
     * @return bool True if released, false if lock was not owned by this token
     */
    public function releaseLock(string $key, string $token): bool
    {
        $lockKey = $this->getLockKey($key);
        $lockData = $this->cache()->get($lockKey);

        if (!$lockData) {
            return false;
        }

        // Verify ownership before releasing
        if ($lockData['token'] !== $token) {
            return false;
        }

        $this->cache()->forget($lockKey);

        return true;
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