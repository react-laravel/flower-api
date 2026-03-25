<?php

namespace Tests\Unit\Services\Traits;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use App\Services\Traits\CacheLocking;

/**
 * Test for CacheLocking trait.
 *
 * This trait provides shared caching functionality for distributed operations.
 */
class CacheLockingTest extends TestCase
{
    private CacheLockingDummy $dummy;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->dummy = new CacheLockingDummy();
        $this->dummy->initCacheStore('array');
    }

    // ============================================================
    // initCacheStore()
    // ============================================================

    public function test_init_cache_store_accepts_custom_store(): void
    {
        $dummy = new CacheLockingDummy();
        $dummy->initCacheStore('array');

        $cache = $dummy->cache();
        $this->assertInstanceOf(\Illuminate\Contracts\Cache\Repository::class, $cache);
    }

    public function test_init_cache_store_with_null_uses_default(): void
    {
        $dummy = new CacheLockingDummy();
        $dummy->initCacheStore(null);

        // Should not throw, uses config default
        $cache = $dummy->cache();
        $this->assertInstanceOf(\Illuminate\Contracts\Cache\Repository::class, $cache);
    }

    // ============================================================
    // cache()
    // ============================================================

    public function test_cache_returns_repository_instance(): void
    {
        $cache = $this->dummy->cache();
        $this->assertInstanceOf(\Illuminate\Contracts\Cache\Repository::class, $cache);
    }

    // ============================================================
    // isLocked()
    // ============================================================

    public function test_is_locked_returns_false_when_key_not_exists(): void
    {
        $this->assertFalse($this->dummy->isLocked('nonexistent-key'));
    }

    public function test_is_locked_returns_true_when_key_exists(): void
    {
        $key = 'test-lock-key';
        $lockKey = $this->dummy->getLockKey($key);

        // Manually put a lock in cache
        Cache::store('array')->put($lockKey, 'locked', 60);

        $this->assertTrue($this->dummy->isLocked($key));
    }

    public function test_is_locked_uses_get_lock_key_format(): void
    {
        $key = 'my-key';
        $lockKey = $this->dummy->getLockKey($key);

        // The lock key should be 'lock:my-key' based on the dummy implementation
        $this->assertEquals('lock:my-key', $lockKey);

        // Verify isLocked checks the correct cache key
        Cache::store('array')->put($lockKey, 'locked', 60);
        $this->assertTrue($this->dummy->isLocked($key));
    }

    // ============================================================
    // getLockKey()
    // ============================================================

    public function test_get_lock_key_returns_formatted_key(): void
    {
        $key = 'resource-123';
        $lockKey = $this->dummy->getLockKey($key);

        $this->assertEquals('lock:resource-123', $lockKey);
    }
}

/**
 * Dummy class that uses the CacheLocking trait for testing.
 */
class CacheLockingDummy
{
    use CacheLocking;

    protected function getLockKey(string $key): string
    {
        return 'lock:' . $key;
    }
}
