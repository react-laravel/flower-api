<?php

namespace App\Services;

use App\Services\Traits\CacheLocking;

class IdempotencyService
{
    use CacheLocking;

    private const DEFAULT_TTL = 86400; // 24 hours

    public function __construct(string $cacheStore = null)
    {
        $this->initCacheStore($cacheStore);
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
     * Acquire a lock for processing (atomic operation).
     */
    public function acquireLock(string $key, int $ttl = 30): bool
    {
        return $this->acquireLockInternal($key, $ttl);
    }

    /**
     * Release a lock.
     */
    public function releaseLock(string $key): void
    {
        $this->cache()->forget($this->getLockKey($key));
    }

    /**
     * Internal lock acquisition that returns owner token.
     */
    private function acquireLockInternal(string $key, int $ttl): bool
    {
        $lockKey = $this->getLockKey($key);
        $owner = uniqid('lock_', true);

        return $this->cache()->add($lockKey, [
            'locked_at' => now()->toIso8601String(),
            'owner' => $owner,
        ], $ttl);
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
    protected function getLockKey(string $key): string
    {
        return 'idempotency_lock:' . $key;
    }
}
