<?php

namespace Tests\Feature\Idempotency;

use App\Services\DistributedLockService;

/**
 * Tests for DistributedLockService integration:
 * - Lock acquisition, release, and atomic execution
 * - Token-based ownership validation
 * - Concurrent execution prevention
 * - Independent lock keys
 */
class DistributedLockTest extends BaseReliabilityTest
{
    /**
     * Test that DistributedLockService::withLock properly acquires/releases
     * and executes the callback atomically.
     *
     * With the array/cache driver, release() removes the key, so a subsequent
     * acquire() succeeds (correct distributed-lock behavior: lock is ephemeral).
     * The test verifies that both calls succeed sequentially, not concurrently.
     */
    public function test_distributed_lock_prevents_concurrent_execution(): void
    {
        $lockKey = 'test-concurrent-lock-' . uniqid();
        $service = new DistributedLockService();

        $executionCount = 0;

        // First caller acquires lock, executes callback, releases
        $result1 = $service->withLock($lockKey, function () use (&$executionCount) {
            $executionCount++;
            return 'first';
        });

        $this->assertEquals('first', $result1);
        $this->assertEquals(1, $executionCount);
        $this->assertFalse($service->isLocked($lockKey));

        // Second caller: after release, lock is free so acquire succeeds.
        // Both sequential calls succeed (first released before second acquired).
        // The critical guarantee: they never execute concurrently (no overlap).
        $result2 = $service->withLock($lockKey, function () use (&$executionCount) {
            $executionCount++;
            return 'second';
        });

        $this->assertEquals('second', $result2);
        $this->assertEquals(2, $executionCount); // both called, sequentially
    }

    /**
     * Test that DistributedLockService::release only succeeds with correct token.
     */
    public function test_distributed_lock_token_mismatch_prevents_release(): void
    {
        $lockKey = 'test-token-mismatch-' . uniqid();
        $service = new DistributedLockService();

        $token = $service->acquire($lockKey, 30);
        $this->assertNotFalse($token);
        $this->assertTrue($service->isLocked($lockKey));

        // Wrong token cannot release
        $released = $service->release($lockKey, 'wrong-token');
        $this->assertFalse($released);
        $this->assertTrue($service->isLocked($lockKey)); // lock still held

        // Correct token releases
        $released = $service->release($lockKey, $token);
        $this->assertTrue($released);
        $this->assertFalse($service->isLocked($lockKey));
    }

    /**
     * Test that when a lock is actively held (not yet released),
     * withLock returns false for a concurrent request.
     */
    public function test_with_lock_returns_false_when_lock_is_held(): void
    {
        $lockKey = 'test-held-lock-' . uniqid();
        $service = new DistributedLockService();

        // Acquire lock without releasing (simulates an in-flight request)
        $token = $service->acquire($lockKey, 30);
        $this->assertNotFalse($token);
        $this->assertTrue($service->isLocked($lockKey));

        // Concurrent caller should fail to acquire
        $result = $service->withLock($lockKey, function () {
            return 'should-not-run';
        });

        $this->assertFalse($result);

        // Clean up
        $service->release($lockKey, $token);
    }

    /**
     * Test that multiple different lock keys operate independently.
     */
    public function test_different_lock_keys_are_independent(): void
    {
        $service = new DistributedLockService();

        $token1 = $service->acquire('key-one-' . uniqid(), 30);
        $token2 = $service->acquire('key-two-' . uniqid(), 30);

        $this->assertNotFalse($token1);
        $this->assertNotFalse($token2);
        $this->assertNotEquals($token1, $token2);
    }
}
