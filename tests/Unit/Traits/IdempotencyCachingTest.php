<?php

namespace Tests\Unit\Traits;

use App\Http\Traits\IdempotencyCaching;
use App\Services\IdempotencyService;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class IdempotencyCachingTest extends TestCase
{
    use IdempotencyCaching;

    protected IdempotencyService $idempotencyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->idempotencyService = new IdempotencyService();
    }

    /**
     * Test getCachedResponse returns cached data for a given key.
     */
    public function test_get_cached_response_returns_cached_data(): void
    {
        $key = 'test-key-' . uniqid();
        $responseData = ['data' => ['id' => 1], 'message' => 'Success'];

        $this->idempotencyService->markProcessed($key, $responseData);

        $result = $this->getCachedResponse($key);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('response', $result);
        $this->assertEquals($responseData, $result['response']);
    }

    /**
     * Test getCachedResponse returns null when no cached data exists.
     */
    public function test_get_cached_response_returns_null_when_not_cached(): void
    {
        $key = 'non-existent-key-' . uniqid();

        $result = $this->getCachedResponse($key);

        $this->assertNull($result);
    }

    /**
     * Test markProcessed stores response data correctly.
     */
    public function test_mark_processed_stores_response_data(): void
    {
        $key = 'mark-processed-key-' . uniqid();
        $responseData = ['data' => ['id' => 42], 'message' => 'Created'];

        $this->markProcessed($key, $responseData);

        $cached = $this->idempotencyService->getResponse($key);
        $this->assertEquals($responseData, $cached['response']);
    }

    /**
     * Test buildCachedResponse returns correct JsonResponse structure.
     */
    public function test_build_cached_response_returns_correct_structure(): void
    {
        $cached = [
            'response' => ['data' => ['id' => 1], 'message' => 'Test message'],
        ];

        $response = $this->buildCachedResponse($cached);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals(['id' => 1], $data['data']);
        $this->assertEquals('Test message', $data['message']);
        $this->assertTrue($data['idempotent']);
    }

    /**
     * Test buildCachedResponse handles missing message in cached data.
     */
    public function test_build_cached_response_handles_missing_message(): void
    {
        $cached = [
            'response' => ['data' => ['id' => 1]],
        ];

        $response = $this->buildCachedResponse($cached);

        $data = $response->getData(true);
        $this->assertNull($data['message']);
    }

    /**
     * Test cacheResponseOrFail successfully caches a valid JsonResponse.
     */
    public function test_cache_response_or_fail_caches_valid_response(): void
    {
        $key = 'cache-fail-key-' . uniqid();
        $response = new JsonResponse(['success' => true, 'data' => ['id' => 1]]);

        $this->cacheResponseOrFail($key, $response);

        $cached = $this->idempotencyService->getResponse($key);
        $this->assertEquals(['id' => 1], $cached['response']['data']);
        $this->assertFalse($cached['response']['pending'] ?? false);
    }

    /**
     * Test cacheResponseOrFail throws RuntimeException when JSON decode fails.
     */
    public function test_cache_response_or_fail_throws_on_invalid_json(): void
    {
        $key = 'invalid-json-key-' . uniqid();
        $response = new class extends JsonResponse {
            public function getContent(): string
            {
                return 'not valid json{';
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to decode response JSON');

        $this->cacheResponseOrFail($key, $response);
    }
}