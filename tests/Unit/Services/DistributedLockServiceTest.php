<?php

namespace Tests\Unit\Services;

use App\Services\DistributedLockService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DistributedLockServiceTest extends TestCase
{
    private DistributedLockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->service = new DistributedLockService('array');
    }

    // ============================================================
    // acquire()
    // ============================================================

    public function test_acquire_returns_token_when_lock_available(): void
    {
        $token = $this->service->acquire('test-lock-key');

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertStringStartsWith('lock_', $token);
    }

    public function test_acquire_returns_false_when_lock_held(): void
    {
        $firstToken = $this->service->acquire('held-lock-key');

        $secondResult = $this->service->acquire('held-lock-key');

        $this->assertNotFalse($firstToken);
        $this->assertFalse($secondResult);
    }

    public function test_acquire_uses_custom_ttl(): void
    {
        $token = $this->service->acquire('custom-ttl-lock', 60);

        $this->assertIsString($token);
        $this->assertTrue($this->service->isLocked('custom-ttl-lock'));
    }

    public function test_acquire_returns_unique_tokens(): void
    {
        $token1 = $this->service->acquire('unique-token-lock-1');
        $this->service->release('unique-token-lock-1', $token1);
        $token2 = $this->service->acquire('unique-token-lock-2');

        $this->assertNotEquals($token1, $token2);
    }

    // ============================================================
    // release()
    // ============================================================

    public function test_release_returns_true_with_correct_token(): void
    {
        $token = $this->service->acquire('release-lock-key');

        $result = $this->service->release('release-lock-key', $token);

        $this->assertTrue($result);
        $this->assertFalse($this->service->isLocked('release-lock-key'));
    }

    public function test_release_returns_false_with_wrong_token(): void
    {
        $token = $this->service->acquire('wrong-token-lock');
        $wrongToken = 'wrong_token_value';

        $result = $this->service->release('wrong-token-lock', $wrongToken);

        $this->assertFalse($result);
        $this->assertTrue($this->service->isLocked('wrong-token-lock'));
    }

    public function test_release_returns_false_for_nonexistent_lock(): void
    {
        $result = $this->service->release('nonexistent-lock', 'any-token');

        $this->assertFalse($result);
    }

    public function test_release_allows_reacquire_after_release(): void
    {
        $token1 = $this->service->acquire('reacquire-lock');
        $this->service->release('reacquire-lock', $token1);

        $token2 = $this->service->acquire('reacquire-lock');

        $this->assertIsString($token2);
        $this->assertNotFalse($token2);
    }

    // ============================================================
    // withLock()
    // ============================================================

    public function test_with_lock_executes_callback(): void
    {
        $result = $this->service->withLock('callback-lock', function () {
            return 'callback-result';
        });

        $this->assertEquals('callback-result', $result);
    }

    public function test_with_lock_releases_lock_on_success(): void
    {
        $token = $this->service->acquire('success-release-lock');
        $this->service->release('success-release-lock', $token);

        $this->service->withLock('success-release-lock', function () {
            return 'success';
        });

        $this->assertFalse($this->service->isLocked('success-release-lock'));
    }

    public function test_with_lock_releases_lock_on_exception(): void
    {
        $this->service->acquire('exception-release-lock');

        try {
            $this->service->withLock('exception-release-lock', function () {
                throw new \Exception('Test exception');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        $this->assertFalse($this->service->isLocked('exception-release-lock'));
    }

    public function test_with_lock_returns_false_when_lock_unavailable(): void
    {
        $token = $this->service->acquire('unavailable-lock');

        $result = $this->service->withLock('unavailable-lock', function () {
            return 'should-not-run';
        });

        $this->assertFalse($result);
    }

    // ============================================================
    // isLocked()
    // ============================================================

    public function test_is_locked_returns_false_when_not_locked(): void
    {
        $result = $this->service->isLocked('not-locked-key');

        $this->assertFalse($result);
    }

    public function test_is_locked_returns_true_when_locked(): void
    {
        $this->service->acquire('locked-key');

        $result = $this->service->isLocked('locked-key');

        $this->assertTrue($result);
    }

    // ============================================================
    // getLockInfo()
    // ============================================================

    public function test_get_lock_info_returns_null_when_not_locked(): void
    {
        $result = $this->service->getLockInfo('not-locked-info-key');

        $this->assertNull($result);
    }

    public function test_get_lock_info_returns_lock_data(): void
    {
        $token = $this->service->acquire('info-lock-key');

        $result = $this->service->getLockInfo('info-lock-key');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('acquired_at', $result);
        $this->assertArrayHasKey('owner', $result);
        $this->assertEquals($token, $result['token']);
    }

    // ============================================================
    // forceRelease()
    // ============================================================

    public function test_force_release_removes_lock(): void
    {
        $token = $this->service->acquire('force-release-lock');

        $this->service->forceRelease('force-release-lock');

        $this->assertFalse($this->service->isLocked('force-release-lock'));
    }

    // ============================================================
    // Concurrency scenarios
    // ============================================================

    public function test_concurrent_acquire_only_one_succeeds(): void
    {
        $firstAcquire = $this->service->acquire('concurrent-lock');
        $secondAcquire = $this->service->acquire('concurrent-lock');

        $this->assertNotFalse($firstAcquire);
        $this->assertFalse($secondAcquire);
    }

    public function test_lock_auto_expires_after_ttl(): void
    {
        $token = $this->service->acquire('ttl-expire-lock', 1);

        $this->assertTrue($this->service->isLocked('ttl-expire-lock'));

        // Wait for TTL to expire
        sleep(2);

        $this->assertFalse($this->service->isLocked('ttl-expire-lock'));
    }
}