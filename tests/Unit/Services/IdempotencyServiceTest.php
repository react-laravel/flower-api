<?php

namespace Tests\Unit\Services;

use App\Services\IdempotencyService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class IdempotencyServiceTest extends TestCase
{
    private IdempotencyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->service = new IdempotencyService('array');
    }

    // ============================================================
    // isProcessed()
    // ============================================================

    public function test_is_processed_returns_false_for_new_key(): void
    {
        $result = $this->service->isProcessed('new-key');

        $this->assertFalse($result);
    }

    public function test_is_processed_returns_true_after_mark_processed(): void
    {
        $this->service->markProcessed('test-key', ['data' => 'value']);

        $this->assertTrue($this->service->isProcessed('test-key'));
    }

    // ============================================================
    // markProcessed()
    // ============================================================

    public function test_mark_processed_stores_response(): void
    {
        $response = ['success' => true, 'data' => ['id' => 1]];

        $this->service->markProcessed('test-key', $response);
        $result = $this->service->getResponse('test-key');

        $this->assertNotNull($result);
        $this->assertEquals($response, $result['response']);
    }

    public function test_mark_processed_records_timestamp(): void
    {
        $this->service->markProcessed('test-key', ['data' => 'value']);
        $result = $this->service->getResponse('test-key');

        $this->assertNotNull($result['processed_at']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $result['processed_at']);
    }

    public function test_mark_processed_respects_custom_ttl(): void
    {
        $this->service->markProcessed('short-ttl-key', ['data' => 'value'], 1);

        $result = $this->service->getResponse('short-ttl-key');

        $this->assertNotNull($result);
        $this->assertEquals(['data' => 'value'], $result['response']);
    }

    // ============================================================
    // getResponse()
    // ============================================================

    public function test_get_response_returns_null_for_new_key(): void
    {
        $result = $this->service->getResponse('nonexistent-key');

        $this->assertNull($result);
    }

    public function test_get_response_returns_stored_response(): void
    {
        $storedResponse = ['success' => true, 'data' => ['name' => 'Test']];
        $this->service->markProcessed('my-key', $storedResponse);

        $result = $this->service->getResponse('my-key');

        $this->assertEquals($storedResponse, $result['response']);
    }

    public function test_get_response_returns_processed_at_timestamp(): void
    {
        $this->service->markProcessed('timestamp-key', ['data' => 'test']);
        $result = $this->service->getResponse('timestamp-key');

        $this->assertArrayHasKey('processed_at', $result);
        $this->assertNotEmpty($result['processed_at']);
    }

    // ============================================================
    // Lock operations (isLocked, acquireLock, releaseLock)
    // ============================================================

    public function test_is_locked_returns_false_when_not_locked(): void
    {
        $result = $this->service->isLocked('unlocked-key');

        $this->assertFalse($result);
    }

    public function test_is_locked_returns_true_when_locked(): void
    {
        $this->service->acquireLock('locked-key');

        $this->assertTrue($this->service->isLocked('locked-key'));
    }

    public function test_acquire_lock_returns_true_on_success(): void
    {
        $result = $this->service->acquireLock('lock-success-key');

        $this->assertTrue($result);
    }

    public function test_acquire_lock_returns_false_when_already_locked(): void
    {
        $this->service->acquireLock('already-locked-key');

        $result = $this->service->acquireLock('already-locked-key');

        $this->assertFalse($result);
    }

    public function test_acquire_lock_uses_custom_ttl(): void
    {
        $result = $this->service->acquireLock('custom-ttl-key', 5);

        $this->assertTrue($result);
        $this->assertTrue($this->service->isLocked('custom-ttl-key'));
    }

    public function test_release_lock_removes_lock(): void
    {
        $this->service->acquireLock('release-test-key');
        $this->service->releaseLock('release-test-key');

        $this->assertFalse($this->service->isLocked('release-test-key'));
    }

    public function test_release_lock_allows_reacquire(): void
    {
        $this->service->acquireLock('reacquire-key');
        $this->service->releaseLock('reacquire-key');

        $result = $this->service->acquireLock('reacquire-key');

        $this->assertTrue($result);
    }

    // ============================================================
    // Key generation
    // ============================================================

    public function test_idempotency_keys_are_namespaced(): void
    {
        $this->service->markProcessed('namespaced-key', ['data' => 'test']);

        $this->assertTrue(Cache::store('array')->has('idempotency:namespaced-key'));
    }

    public function test_lock_keys_are_namespaced(): void
    {
        $this->service->acquireLock('lock-namespaced-key');

        $this->assertTrue(Cache::store('array')->has('idempotency_lock:lock-namespaced-key'));
    }

    // ============================================================
    // Integration scenarios
    // ============================================================

    public function test_full_idempotency_cycle(): void
    {
        $key = 'full-cycle-key';

        $this->assertFalse($this->service->isProcessed($key));
        $this->assertNull($this->service->getResponse($key));

        $response = ['success' => true, 'data' => ['id' => 123]];
        $this->service->markProcessed($key, $response);

        $this->assertTrue($this->service->isProcessed($key));
        $storedResponse = $this->service->getResponse($key);
        $this->assertEquals($response, $storedResponse['response']);
    }

    public function test_lock_prevents_concurrent_processing(): void
    {
        $key = 'concurrent-key';

        $firstLock = $this->service->acquireLock($key);
        $secondLock = $this->service->acquireLock($key);

        $this->assertTrue($firstLock);
        $this->assertFalse($secondLock);
    }
}