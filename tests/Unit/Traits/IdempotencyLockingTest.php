<?php

namespace Tests\Unit\Traits;

use App\Http\Traits\IdempotencyLocking;
use App\Services\IdempotencyService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class IdempotencyLockingTest extends TestCase
{
    use IdempotencyLocking;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->idempotencyService = new IdempotencyService('array');
    }

    public function test_acquire_lock_returns_true_when_lock_available(): void
    {
        $key = 'lock-available-' . uniqid();

        $result = $this->acquireLock($key);

        $this->assertTrue($result);
    }

    public function test_acquire_lock_returns_false_when_already_locked(): void
    {
        $key = 'lock-held-' . uniqid();

        // First lock should succeed
        $first = $this->acquireLock($key);
        // Second lock should fail
        $second = $this->acquireLock($key);

        $this->assertTrue($first);
        $this->assertFalse($second);
    }

    public function test_acquire_lock_respects_custom_ttl(): void
    {
        $key = 'lock-ttl-' . uniqid();

        $result = $this->acquireLock($key, 5);

        $this->assertTrue($result);
        $this->assertTrue($this->idempotencyService->isLocked($key));
    }

    public function test_release_lock_removes_existing_lock(): void
    {
        $key = 'lock-release-' . uniqid();

        $this->acquireLock($key);
        $this->releaseLock($key);

        $this->assertFalse($this->idempotencyService->isLocked($key));
    }

    public function test_release_lock_does_not_throw_when_no_lock_exists(): void
    {
        $key = 'no-lock-' . uniqid();

        // Should not throw
        $this->releaseLock($key);

        $this->assertFalse($this->idempotencyService->isLocked($key));
    }

    public function test_lock_is_independent_per_key(): void
    {
        $key1 = 'lock-ind-1-' . uniqid();
        $key2 = 'lock-ind-2-' . uniqid();

        // Each key is independent — acquiring one does not affect another
        $result1 = $this->acquireLock($key1);
        $result2 = $this->acquireLock($key2);

        $this->assertTrue($result1);
        $this->assertTrue($result2);
        $this->assertTrue($this->idempotencyService->isLocked($key1));
        $this->assertTrue($this->idempotencyService->isLocked($key2));

        $this->releaseLock($key1);
        $this->assertFalse($this->idempotencyService->isLocked($key1));
        $this->assertTrue($this->idempotencyService->isLocked($key2));

        $this->releaseLock($key2);
        $this->assertFalse($this->idempotencyService->isLocked($key2));
    }

    public function test_acquire_and_release_cycle(): void
    {
        $key = 'lock-cycle-' . uniqid();

        // Acquire
        $acquired = $this->acquireLock($key);
        $this->assertTrue($acquired);
        $this->assertTrue($this->idempotencyService->isLocked($key));

        // Release
        $this->releaseLock($key);
        $this->assertFalse($this->idempotencyService->isLocked($key));

        // Re-acquire after release should succeed
        $reacquired = $this->acquireLock($key);
        $this->assertTrue($reacquired);
    }
}
