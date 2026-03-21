<?php

namespace Tests\Unit;

use App\Services\IdempotencyService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class IdempotencyServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Test acquireLock returns true when lock is not held
     */
    public function test_acquire_lock_returns_true_when_not_held(): void
    {
        $service = new IdempotencyService();
        $key = 'test-lock-' . uniqid();

        $result = $service->acquireLock($key, 30);

        $this->assertTrue($result);
        $this->assertTrue($service->isLocked($key));
    }

    /**
     * Test acquireLock returns false when already held
     */
    public function test_acquire_lock_returns_false_when_already_held(): void
    {
        $service = new IdempotencyService();
        $key = 'test-lock-' . uniqid();

        // First acquire should succeed
        $result1 = $service->acquireLock($key, 30);
        $this->assertTrue($result1);

        // Second acquire should fail
        $result2 = $service->acquireLock($key, 30);
        $this->assertFalse($result2);
    }

    /**
     * Test isLocked returns false after lock is released
     */
    public function test_is_locked_returns_false_after_release(): void
    {
        $service = new IdempotencyService();
        $key = 'test-lock-' . uniqid();

        $service->acquireLock($key, 30);
        $this->assertTrue($service->isLocked($key));

        $service->releaseLock($key);
        $this->assertFalse($service->isLocked($key));
    }

    /**
     * Test markProcessed stores response correctly
     */
    public function test_mark_processed_stores_response(): void
    {
        $service = new IdempotencyService();
        $key = 'test-processed-' . uniqid();
        $response = [
            'data' => ['id' => 1, 'name' => 'Test'],
            'message' => 'Success',
            'status' => 200,
        ];

        $service->markProcessed($key, $response);

        $this->assertTrue($service->isProcessed($key));
        $cached = $service->getResponse($key);
        $this->assertNotNull($cached);
        $this->assertEquals($response, $cached['response']);
    }

    /**
     * Test isProcessed returns false when not processed
     */
    public function test_is_processed_returns_false_when_not_processed(): void
    {
        $service = new IdempotencyService();
        $key = 'never-processed-' . uniqid();

        $this->assertFalse($service->isProcessed($key));
    }

    /**
     * Test multiple locks with different keys work independently
     */
    public function test_multiple_locks_work_independently(): void
    {
        $service = new IdempotencyService();
        $key1 = 'lock-1-' . uniqid();
        $key2 = 'lock-2-' . uniqid();

        $result1 = $service->acquireLock($key1, 30);
        $this->assertTrue($result1);

        $result2 = $service->acquireLock($key2, 30);
        $this->assertTrue($result2);

        $this->assertTrue($service->isLocked($key1));
        $this->assertTrue($service->isLocked($key2));

        $service->releaseLock($key1);
        $this->assertFalse($service->isLocked($key1));
        $this->assertTrue($service->isLocked($key2));
    }

    /**
     * Test getResponse returns null for non-existent key
     */
    public function test_get_response_returns_null_for_non_existent(): void
    {
        $service = new IdempotencyService();
        $key = 'non-existent-' . uniqid();

        $this->assertNull($service->getResponse($key));
    }
}