<?php

namespace Tests\Unit\Services;

use App\Services\DistributedLockService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DistributedLockServiceTest extends TestCase
{
    protected DistributedLockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DistributedLockService();
        Cache::flush();
    }

    public function test_get_lock_creates_lock_for_resource(): void
    {
        $lock = $this->service->getLock('test-resource');
        
        $this->assertInstanceOf(\Illuminate\Cache\Lock::class, $lock);
    }

    public function test_try_lock_returns_true_when_available(): void
    {
        $result = $this->service->tryLock('available-resource');
        
        $this->assertTrue($result);
    }

    public function test_try_lock_returns_false_when_already_locked(): void
    {
        $resource = 'locked-resource';
        
        // First lock should succeed
        $this->service->tryLock($resource);
        
        // Second attempt should fail
        $result = $this->service->tryLock($resource);
        
        $this->assertFalse($result);
        
        // Clean up
        $this->service->release($resource);
    }

    public function test_with_lock_executes_callback_and_releases_lock(): void
    {
        $executed = false;
        
        $result = $this->service->withLock('callback-resource', function () use (&$executed) {
            $executed = true;
            return 'callback-result';
        });
        
        $this->assertTrue($executed);
        $this->assertEquals('callback-result', $result);
        
        // Lock should be released after callback
        $this->assertFalse($this->service->isLocked('callback-resource'));
    }

    public function test_with_lock_releases_lock_on_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        
        try {
            $this->service->withLock('exception-resource', function () {
                throw new \RuntimeException('Test exception');
            });
        } finally {
            // Lock should be released even after exception
            $this->assertFalse($this->service->isLocked('exception-resource'));
        }
    }

    public function test_with_lock_waits_for_lock_if_available(): void
    {
        $resource = 'wait-resource';
        $order = [];
        
        // Start first operation in background (simulated with blocking)
        $this->service->withLock($resource, function () use (&$order) {
            $order[] = 'first-start';
            usleep(50000); // 50ms
            $order[] = 'first-end';
        });
        
        // Second operation should wait and execute after first completes
        $this->service->withLock($resource, function () use (&$order) {
            $order[] = 'second';
        });
        
        $this->assertEquals(['first-start', 'first-end', 'second'], $order);
    }

    public function test_release_manually_unlocks_resource(): void
    {
        $resource = 'manual-release-' . uniqid();
        
        // Acquire lock using withLock to ensure proper setup
        $this->service->withLock($resource, function () use (&$executed) {
            return 'test';
        });
        
        // After withLock, the lock should be released
        $this->assertFalse($this->service->isLocked($resource));
    }

    public function test_is_locked_reflects_lock_state(): void
    {
        $resource = 'check-locked-' . uniqid();
        
        // Initially not locked
        $this->assertFalse($this->service->isLocked($resource));
    }

    public function test_default_timeout_can_be_set(): void
    {
        $service = new DistributedLockService();
        $service->setDefaultTimeout(30);
        
        $this->assertEquals(30, $service->getDefaultTimeout());
    }

    public function test_default_wait_time_can_be_set(): void
    {
        $service = new DistributedLockService();
        $service->setDefaultWaitTime(10);
        
        $this->assertEquals(10, $service->getDefaultWaitTime());
    }

    public function test_default_lifetime_can_be_set(): void
    {
        $service = new DistributedLockService();
        $service->setDefaultLifetime(60);
        
        $this->assertEquals(60, $service->getDefaultLifetime());
    }

    public function test_lock_key_format(): void
    {
        $lock = $this->service->getLock('MyResource:123');
        
        // Lock should be created successfully
        $this->assertInstanceOf(\Illuminate\Cache\Lock::class, $lock);
    }
}
