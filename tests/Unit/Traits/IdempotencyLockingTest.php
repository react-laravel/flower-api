<?php

namespace Tests\Unit\Traits;

use App\Http\Traits\IdempotencyLocking;
use App\Services\IdempotencyService;
use Tests\TestCase;

class IdempotencyLockingTest extends TestCase
{
    use IdempotencyLocking;

    protected IdempotencyService $idempotencyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->idempotencyService = new IdempotencyService();
    }

    /**
     * Test acquireLock returns true when lock is successfully acquired.
     */
    public function test_acquire_lock_returns_true_on_success(): void
    {
        $key = 'lock-key-' . uniqid();

        $result = $this->acquireLock($key, 30);

        $this->assertTrue($result);
    }

    /**
     * Test acquireLock returns false when lock is already held.
     */
    public function test_acquire_lock_returns_false_when_locked(): void
    {
        $key = 'already-locked-key-' . uniqid();

        // First lock should succeed
        $this->acquireLock($key, 30);

        // Second lock attempt should fail
        $result = $this->acquireLock($key, 30);

        $this->assertFalse($result);
    }

    /**
     * Test acquireLock with custom TTL.
     */
    public function test_acquire_lock_with_custom_ttl(): void
    {
        $key = 'ttl-key-' . uniqid();

        $result = $this->acquireLock($key, 60);

        $this->assertTrue($result);
    }

    /**
     * Test releaseLock frees the lock for another request.
     */
    public function test_release_lock_frees_lock(): void
    {
        $key = 'release-key-' . uniqid();

        $this->acquireLock($key, 30);
        $this->releaseLock($key);

        // Now another request should be able to acquire the lock
        $result = $this->acquireLock($key, 30);

        $this->assertTrue($result);
    }

    /**
     * Test releaseLock on a key that was never locked does not throw.
     */
    public function test_release_lock_on_unlocked_key_does_not_throw(): void
    {
        $key = 'never-locked-key-' . uniqid();

        // Should not throw
        $this->releaseLock($key);

        $this->assertTrue(true);
    }

    /**
     * Test lock is released after handler execution completes.
     */
    public function test_lock_is_released_after_execution(): void
    {
        $key = 'execution-key-' . uniqid();

        $this->acquireLock($key, 30);

        // Simulate some work
        $this->releaseLock($key);

        $this->assertFalse($this->idempotencyService->isLocked($key));
    }

    /**
     * Test multiple sequential locks can be acquired and released for different keys.
     */
    public function test_sequential_locks_work_correctly_for_different_keys(): void
    {
        $key1 = 'seq-key-1-' . uniqid();
        $key2 = 'seq-key-2-' . uniqid();

        $result1 = $this->acquireLock($key1, 30);
        $this->assertTrue($result1);

        $this->releaseLock($key1);

        $result2 = $this->acquireLock($key1, 30);
        $this->assertTrue($result2);

        // key2 should also be acquirable
        $result3 = $this->acquireLock($key2, 30);
        $this->assertTrue($result3);
    }
}