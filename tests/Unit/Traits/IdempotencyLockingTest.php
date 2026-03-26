<?php

namespace Tests\Unit\Traits;

use App\Http\Traits\ApiResponse;
use App\Http\Traits\Idempotency;
use App\Http\Traits\IdempotencyLocking;
use Tests\TestCase;

/**
 * Unit tests for IdempotencyLocking trait.
 *
 * This trait handles lock acquisition and release for idempotency.
 * Part of the Idempotency trait split to reduce complexity.
 */
class IdempotencyLockingTest extends TestCase
{
    use ApiResponse, Idempotency, IdempotencyLocking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initIdempotency();
    }

    // ============================================================
    // acquireLock()
    // ============================================================

    public function test_acquire_lock_returns_true_when_lock_available(): void
    {
        $key = 'available-lock-' . uniqid();

        $result = $this->acquireLock($key, 30);

        $this->assertTrue($result);
    }

    public function test_acquire_lock_returns_false_when_already_locked(): void
    {
        $key = 'held-lock-' . uniqid();
        $this->acquireLock($key, 30);

        $result = $this->acquireLock($key, 30);

        $this->assertFalse($result);
    }

    public function test_acquire_lock_uses_custom_ttl(): void
    {
        $key = 'custom-ttl-lock-' . uniqid();

        $result = $this->acquireLock($key, 5);

        $this->assertTrue($result);
        $this->assertTrue($this->idempotencyService->isLocked($key));
    }

    public function test_acquire_lock_allows_reacquire_after_release(): void
    {
        $key = 'reacquire-lock-' . uniqid();
        $this->acquireLock($key, 30);
        $this->releaseLock($key);

        $result = $this->acquireLock($key, 30);

        $this->assertTrue($result);
    }

    // ============================================================
    // releaseLock()
    // ============================================================

    public function test_release_lock_returns_void(): void
    {
        $key = 'release-lock-' . uniqid();
        $this->acquireLock($key, 30);

        $result = $this->releaseLock($key);

        $this->assertNull($result);
    }

    public function test_release_lock_makes_key_available_again(): void
    {
        $key = 'available-after-release-' . uniqid();
        $this->acquireLock($key, 30);
        $this->releaseLock($key);

        $acquired = $this->acquireLock($key, 30);

        $this->assertTrue($acquired);
    }

    public function test_release_lock_does_nothing_for_unlocked_key(): void
    {
        $key = 'never-locked-' . uniqid();

        // Should not throw
        $this->releaseLock($key);

        $this->assertTrue(true);
    }
}
