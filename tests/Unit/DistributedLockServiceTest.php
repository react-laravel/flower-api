<?php

namespace Tests\Unit;

use App\Services\DistributedLockService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DistributedLockServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Test acquire returns token on success
     */
    public function test_acquire_returns_token_on_success(): void
    {
        $service = new DistributedLockService();
        $key = 'dist-lock-' . uniqid();

        $token = $service->acquire($key, 30);

        $this->assertNotFalse($token);
        $this->assertIsString($token);
        $this->assertTrue($service->isLocked($key));
    }

    /**
     * Test acquire returns false when already locked
     */
    public function test_acquire_returns_false_when_locked(): void
    {
        $service = new DistributedLockService();
        $key = 'dist-lock-' . uniqid();

        $token1 = $service->acquire($key, 30);
        $this->assertNotFalse($token1);

        $token2 = $service->acquire($key, 30);
        $this->assertFalse($token2);
    }

    /**
     * Test release only succeeds with correct token
     */
    public function test_release_only_succeeds_with_correct_token(): void
    {
        $service = new DistributedLockService();
        $key = 'dist-lock-' . uniqid();

        $token = $service->acquire($key, 30);
        $this->assertNotFalse($token);

        // Wrong token should fail
        $result = $service->release($key, 'wrong-token');
        $this->assertFalse($result);
        $this->assertTrue($service->isLocked($key));

        // Correct token should succeed
        $result = $service->release($key, $token);
        $this->assertTrue($result);
        $this->assertFalse($service->isLocked($key));
    }

    /**
     * Test withLock executes callback when lock acquired
     */
    public function test_with_lock_executes_callback(): void
    {
        $service = new DistributedLockService();
        $key = 'dist-lock-' . uniqid();

        $result = $service->withLock($key, function () {
            return 'success';
        }, 30);

        $this->assertEquals('success', $result);
        $this->assertFalse($service->isLocked($key));
    }

    /**
     * Test withLock returns false when lock not acquired
     */
    public function test_with_lock_returns_false_when_not_acquired(): void
    {
        $service = new DistributedLockService();
        $key = 'dist-lock-' . uniqid();

        // Acquire lock first
        $service->acquire($key, 30);

        // withLock should return false
        $result = $service->withLock($key, function () {
            return 'should not run';
        }, 30);

        $this->assertFalse($result);
    }

    /**
     * Test getLockInfo returns lock data
     */
    public function test_get_lock_info_returns_lock_data(): void
    {
        $service = new DistributedLockService();
        $key = 'dist-lock-' . uniqid();

        $token = $service->acquire($key, 30);
        $info = $service->getLockInfo($key);

        $this->assertNotNull($info);
        $this->assertEquals($token, $info['token']);
        $this->assertArrayHasKey('acquired_at', $info);
        $this->assertArrayHasKey('owner', $info);
    }

    /**
     * Test forceRelease removes lock
     */
    public function test_force_release_removes_lock(): void
    {
        $service = new DistributedLockService();
        $key = 'dist-lock-' . uniqid();

        $service->acquire($key, 30);
        $this->assertTrue($service->isLocked($key));

        $service->forceRelease($key);
        $this->assertFalse($service->isLocked($key));
    }

    /**
     * Test callback exception releases lock properly
     */
    public function test_callback_exception_releases_lock(): void
    {
        $service = new DistributedLockService();
        $key = 'dist-lock-exception-' . uniqid();

        try {
            $service->withLock($key, function () {
                throw new \RuntimeException('Test exception');
            }, 30);
        } catch (\RuntimeException $e) {
            // Expected
        }

        // Lock should be released after exception
        $this->assertFalse($service->isLocked($key));
    }
}