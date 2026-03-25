<?php

namespace Tests\Unit\Traits;

use App\Http\Traits\ApiResponse;
use App\Http\Traits\IdempotencyLocking;
use App\Services\IdempotencyService;
use Tests\TestCase;

class IdempotencyLockingTest extends TestCase
{
    use ApiResponse, IdempotencyLocking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->idempotencyService = new IdempotencyService();
    }

    /**
     * Test acquireLock returns true when lock is acquired
     */
    public function test_acquire_lock_returns_true_when_successful(): void
    {
        $key = 'lock-key-' . uniqid();

        $result = $this->acquireLock($key, 30);

        $this->assertTrue($result);
        $this->assertTrue($this->idempotencyService->isLocked($key));

        // Cleanup
        $this->idempotencyService->releaseLock($key);
    }

    /**
     * Test acquireLock returns false when lock is already held
     */
    public function test_acquire_lock_returns_false_when_already_locked(): void
    {
        $key = 'already-locked-key-' . uniqid();

        // First acquire
        $this->idempotencyService->acquireLock($key, 30);

        // Try to acquire again
        $result = $this->acquireLock($key, 30);

        $this->assertFalse($result);

        // Cleanup
        $this->idempotencyService->releaseLock($key);
    }

    /**
     * Test acquireLock with custom TTL
     */
    public function test_acquire_lock_with_custom_ttl(): void
    {
        $key = 'ttl-key-' . uniqid();

        $result = $this->acquireLock($key, 60);

        $this->assertTrue($result);

        // Cleanup
        $this->idempotencyService->releaseLock($key);
    }

    /**
     * Test releaseLock releases the lock
     */
    public function test_release_lock_releases_the_lock(): void
    {
        $key = 'release-key-' . uniqid();

        $this->idempotencyService->acquireLock($key, 30);
        $this->assertTrue($this->idempotencyService->isLocked($key));

        $this->releaseLock($key);

        $this->assertFalse($this->idempotencyService->isLocked($key));
    }

    /**
     * Test releaseLock does not throw when lock does not exist
     */
    public function test_release_lock_does_not_throw_when_not_locked(): void
    {
        $key = 'no-lock-key-' . uniqid();

        // Should not throw
        $this->releaseLock($key);

        $this->assertFalse($this->idempotencyService->isLocked($key));
    }

    /**
     * Test lock can be acquired after release
     */
    public function test_lock_can_be_reacquired_after_release(): void
    {
        $key = 'reacquire-key-' . uniqid();

        // Acquire and release
        $this->acquireLock($key, 30);
        $this->releaseLock($key);

        // Should be able to acquire again
        $result = $this->acquireLock($key, 30);

        $this->assertTrue($result);

        // Cleanup
        $this->idempotencyService->releaseLock($key);
    }
}
