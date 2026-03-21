<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Cache\Lock;
use Illuminate\Cache\LuaScripts;

/**
 * Service to handle distributed locking using Redis.
 * Prevents race conditions when multiple processes access the same resource.
 */
class DistributedLockService
{
    /**
     * Default lock timeout in seconds
     */
    protected int $defaultTimeout;

    /**
     * Default wait time in seconds (how long to wait to acquire lock)
     */
    protected int $defaultWaitTime;

    /**
     * Default lock lifetime in seconds
     */
    protected int $defaultLifetime;

    public function __construct()
    {
        $this->defaultTimeout = 10;
        $this->defaultWaitTime = 5;
        $this->defaultLifetime = 30;
    }

    /**
     * Get a lock for a specific resource.
     *
     * @param string $resource The resource identifier to lock
     * @param int|null $waitTime Maximum time to wait for lock acquisition
     * @param int|null $lifetime How long the lock should be held (auto-release)
     * @return Lock
     */
    public function getLock(string $resource, ?int $waitTime = null, ?int $lifetime = null): Lock
    {
        $waitTime = $waitTime ?? $this->defaultWaitTime;
        $lifetime = $lifetime ?? $this->defaultLifetime;

        $cacheKey = $this->getLockKey($resource);

        return Cache::lock($cacheKey, $lifetime, $waitTime);
    }

    /**
     * Execute a callback with a distributed lock.
     *
     * @param string $resource The resource identifier to lock
     * @param callable $callback The callback to execute
     * @param int|null $waitTime Maximum time to wait for lock acquisition
     * @param int|null $lifetime How long the lock should be held
     * @return mixed The callback result
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function withLock(string $resource, callable $callback, ?int $waitTime = null, ?int $lifetime = null): mixed
    {
        $lock = $this->getLock($resource, $waitTime, $lifetime);

        try {
            // Block until we acquire the lock
            $lock->block($waitTime ?? $this->defaultWaitTime);

            Log::info("DistributedLock: Lock acquired", ['resource' => $resource]);

            return $callback();
        } finally {
            $lock->release();

            Log::info("DistributedLock: Lock released", ['resource' => $resource]);
        }
    }

    /**
     * Try to acquire a lock without blocking.
     *
     * @param string $resource The resource identifier to lock
     * @param int|null $lifetime How long the lock should be held
     * @return bool True if lock acquired, false otherwise
     */
    public function tryLock(string $resource, ?int $lifetime = null): bool
    {
        $lock = $this->getLock($resource, 0, $lifetime ?? $this->defaultLifetime);

        return $lock->get();
    }

    /**
     * Release a lock manually.
     *
     * @param string $resource The resource identifier to unlock
     * @return bool
     */
    public function release(string $resource): bool
    {
        $lock = $this->getLock($resource);

        return $lock->release();
    }

    /**
     * Check if a resource is currently locked.
     *
     * @param string $resource The resource identifier
     * @return bool
     */
    public function isLocked(string $resource): bool
    {
        $cacheKey = $this->getLockKey($resource);

        return Cache::has($cacheKey);
    }

    /**
     * Generate a lock key for a resource.
     *
     * @param string $resource The resource identifier
     * @return string
     */
    protected function getLockKey(string $resource): string
    {
        return "lock:{$resource}";
    }

    /**
     * Set default timeout for lock acquisition.
     *
     * @param int $seconds
     * @return self
     */
    public function setDefaultTimeout(int $seconds): self
    {
        $this->defaultTimeout = $seconds;
        return $this;
    }

    /**
     * Set default wait time for lock acquisition.
     *
     * @param int $seconds
     * @return self
     */
    public function setDefaultWaitTime(int $seconds): self
    {
        $this->defaultWaitTime = $seconds;
        return $this;
    }

    /**
     * Set default lock lifetime.
     *
     * @param int $seconds
     * @return self
     */
    public function setDefaultLifetime(int $seconds): self
    {
        $this->defaultLifetime = $seconds;
        return $this;
    }

    /**
     * Get default timeout.
     *
     * @return int
     */
    public function getDefaultTimeout(): int
    {
        return $this->defaultTimeout;
    }

    /**
     * Get default wait time.
     *
     * @return int
     */
    public function getDefaultWaitTime(): int
    {
        return $this->defaultWaitTime;
    }

    /**
     * Get default lifetime.
     *
     * @return int
     */
    public function getDefaultLifetime(): int
    {
        return $this->defaultLifetime;
    }
}
