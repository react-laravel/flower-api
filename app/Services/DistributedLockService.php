<?php

namespace App\Services;

use App\Services\Traits\CacheLocking;
use Illuminate\Support\Facades\Log;

class DistributedLockService
{
    use CacheLocking;

    private const DEFAULT_LOCK_TTL = 30; // seconds

    public function __construct(string $cacheStore = null)
    {
        $this->initCacheStore($cacheStore);
    }

    /**
     * Acquire a distributed lock
     *
     * @param string $key The lock key
     * @param int $ttl Lock expiration time in seconds
     * @return string|false Returns lock token if acquired, false otherwise
     */
    public function acquire(string $key, int $ttl = self::DEFAULT_LOCK_TTL): string|false
    {
        $lockToken = uniqid('lock_', true);
        $lockKey = $this->getLockKey($key);

        // Try to acquire the lock using atomic operation
        $acquired = $this->cache()->add($lockKey, [
            'token' => $lockToken,
            'acquired_at' => now()->toIso8601String(),
            'owner' => gethostname(),
        ], $ttl);

        if ($acquired) {
            Log::debug("Lock acquired: {$key}", ['token' => $lockToken]);
            return $lockToken;
        }

        return false;
    }

    /**
     * Release a distributed lock
     *
     * @param string $key The lock key
     * @param string $token The lock token received when acquiring
     * @return bool True if released, false if lock was not owned by this token
     */
    public function release(string $key, string $token): bool
    {
        $lockKey = $this->getLockKey($key);
        $lockData = $this->cache()->get($lockKey);

        if (!$lockData) {
            return false;
        }

        // Verify ownership before releasing
        if ($lockData['token'] !== $token) {
            Log::warning("Lock release failed - token mismatch: {$key}");
            return false;
        }

        $this->cache()->forget($lockKey);
        Log::debug("Lock released: {$key}", ['token' => $token]);

        return true;
    }

    /**
     * Execute a callback with a lock
     *
     * @param string $key The lock key
     * @param callable $callback The callback to execute
     * @param int $ttl Lock expiration time in seconds
     * @return mixed Returns the callback result, or false if lock couldn't be acquired
     */
    public function withLock(string $key, callable $callback, int $ttl = self::DEFAULT_LOCK_TTL): mixed
    {
        $token = $this->acquire($key, $ttl);

        if (!$token) {
            Log::warning("Failed to acquire lock: {$key}");
            return false;
        }

        try {
            return $callback();
        } finally {
            $this->release($key, $token);
        }
    }

    /**
     * Get lock information
     */
    public function getLockInfo(string $key): ?array
    {
        return $this->cache()->get($this->getLockKey($key));
    }

    /**
     * Force release a lock (use with caution)
     */
    public function forceRelease(string $key): void
    {
        $this->cache()->forget($this->getLockKey($key));
        Log::warning("Lock force released: {$key}");
    }

    /**
     * Generate the lock cache key
     */
    protected function getLockKey(string $key): string
    {
        return 'distributed_lock:' . $key;
    }
}
