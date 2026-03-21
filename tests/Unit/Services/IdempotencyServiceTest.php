<?php

namespace Tests\Unit\Services;

use App\Services\IdempotencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class IdempotencyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected IdempotencyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IdempotencyService();
        
        // Ensure we use array cache for testing
        Cache::flush();
    }

    public function test_is_duplicate_returns_false_for_new_key(): void
    {
        $result = $this->service->isDuplicate('new-key-123');
        $this->assertFalse($result);
    }

    public function test_is_duplicate_returns_true_after_mark_processed(): void
    {
        $key = 'test-key-456';
        
        $this->service->markProcessed($key, ['status' => 'ok']);
        
        $result = $this->service->isDuplicate($key);
        $this->assertTrue($result);
    }

    public function test_is_duplicate_returns_false_for_empty_key(): void
    {
        $this->assertFalse($this->service->isDuplicate(''));
        $this->assertFalse($this->service->isDuplicate(null));
    }

    public function test_mark_processed_returns_true_for_new_key(): void
    {
        $result = $this->service->markProcessed('new-key', ['data' => 'test']);
        $this->assertTrue($result);
    }

    public function test_mark_processed_updates_existing_key(): void
    {
        $key = 'existing-key';
        $this->service->markProcessed($key, ['data' => 'first']);
        
        // Second attempt should update the existing key
        $result = $this->service->markProcessed($key, ['data' => 'second']);
        $this->assertTrue($result);
        
        // Response should be updated
        $response = $this->service->getProcessedResponse($key);
        $this->assertEquals(['data' => 'second'], $response);
    }

    public function test_get_processed_response_returns_cached_response(): void
    {
        $key = 'response-key';
        $expectedResponse = ['flower_id' => 123, 'name' => '玫瑰'];
        
        $this->service->markProcessed($key, $expectedResponse);
        
        $result = $this->service->getProcessedResponse($key);
        $this->assertEquals($expectedResponse, $result);
    }

    public function test_get_processed_response_returns_null_for_missing_key(): void
    {
        $result = $this->service->getProcessedResponse('nonexistent-key');
        $this->assertNull($result);
    }

    public function test_check_and_mark_returns_null_for_new_key(): void
    {
        $result = $this->service->checkAndMark('new-check-key', ['status' => 'created']);
        $this->assertNull($result);
        
        // Key should now be marked as processed
        $this->assertTrue($this->service->isDuplicate('new-check-key'));
    }

    public function test_check_and_mark_returns_existing_response_for_duplicate(): void
    {
        $key = 'duplicate-check-key';
        $existingResponse = ['id' => 999, 'message' => 'already processed'];
        
        // First call marks as processed
        $this->service->markProcessed($key, $existingResponse);
        
        // Second call should return existing response
        $result = $this->service->checkAndMark($key, ['different' => 'data']);
        $this->assertEquals($existingResponse, $result);
    }

    public function test_remove_deletes_idempotency_key(): void
    {
        $key = 'to-be-removed';
        $this->service->markProcessed($key, ['data' => 'test']);
        
        $result = $this->service->remove($key);
        $this->assertTrue($result);
        
        $this->assertFalse($this->service->isDuplicate($key));
    }

    public function test_remove_returns_false_for_nonexistent_key(): void
    {
        $result = $this->service->remove('nonexistent-remove-key');
        $this->assertFalse($result);
    }

    public function test_empty_key_operations_are_safe(): void
    {
        // These should not throw exceptions
        $this->assertFalse($this->service->isDuplicate(''));
        $this->assertFalse($this->service->markProcessed('', []));
        $this->assertNull($this->service->getProcessedResponse(''));
        $this->assertNull($this->service->checkAndMark('', []));
        $this->assertFalse($this->service->remove(''));
    }

    public function test_ttl_can_be_customized(): void
    {
        $service = new IdempotencyService();
        $service->setTtl(3600);
        
        $this->assertEquals(3600, $service->getTtl());
    }
}
