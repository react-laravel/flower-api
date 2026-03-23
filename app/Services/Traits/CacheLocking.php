<?php

namespace App\Services\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * Shared caching functionality for distributed operations.
 * Provides common cache store access.
 */
trait CacheLocking
{
    private string $cacheStore;

    /**
     * Get the cache store instance.
     */
    protected function cache(): \Illuminate\Contracts\Cache\Repository
    {
        return Cache::store($this->cacheStore);
    }

    /**
     * Initialize the cache store.
     */
    protected function initCacheStore(?string $cacheStore = null): void
    {
        $this->cacheStore = $cacheStore ?? config('cache.default', 'redis');
    }

    /**
     * Check if a lock is currently held.
     */
    public function isLocked(string $key): bool
    {
        return $this->cache()->has($this->getLockKey($key));
    }

    /**
     * Generate the lock cache key. Override in classes that use this trait.
     */
    abstract protected function getLockKey(string $key): string;
}