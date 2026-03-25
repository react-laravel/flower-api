<?php

namespace Tests\Unit\Traits;

use App\Http\Traits\ApiResponse;
use App\Http\Traits\IdempotencyCaching;
use App\Services\IdempotencyService;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class IdempotencyCachingTest extends TestCase
{
    use ApiResponse, IdempotencyCaching;

    protected function setUp(): void
    {
        parent::setUp();
        $this->idempotencyService = new IdempotencyService();
    }

    /**
     * Test getCachedResponse returns null when no cached response exists
     */
    public function test_get_cached_response_returns_null_when_not_cached(): void
    {
        $key = 'non-existent-key-' . uniqid();

        $cached = $this->getCachedResponse($key);

        $this->assertNull($cached);
    }

    /**
     * Test getCachedResponse returns cached response when it exists
     */
    public function test_get_cached_response_returns_cached_data(): void
    {
        $key = 'cached-key-' . uniqid();
        $cachedData = [
            'data' => ['id' => 1, 'name' => 'Test'],
            'message' => 'Success',
            'status' => 200,
            'pending' => false,
        ];
        $this->idempotencyService->markProcessed($key, $cachedData);

        $result = $this->getCachedResponse($key);

        $this->assertIsArray($result);
        $this->assertEquals($cachedData, $result['response']);
    }

    /**
     * Test markProcessed stores response data for idempotency key
     */
    public function test_mark_processed_stores_response_data(): void
    {
        $key = 'processed-key-' . uniqid();
        $responseData = [
            'data' => ['id' => 42],
            'message' => 'Created',
            'status' => 201,
            'pending' => false,
        ];

        $this->markProcessed($key, $responseData);

        $this->assertTrue($this->idempotencyService->isProcessed($key));
    }

    /**
     * Test buildCachedResponse returns JsonResponse with correct structure
     */
    public function test_build_cached_response_returns_json_response(): void
    {
        $cached = [
            'response' => [
                'data' => ['id' => 1, 'name' => 'Test Flower'],
                'message' => 'Flower retrieved',
                'status' => 200,
            ],
        ];

        $response = $this->buildCachedResponse($cached);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals(['id' => 1, 'name' => 'Test Flower'], $data['data']);
        $this->assertEquals('Flower retrieved', $data['message']);
        $this->assertTrue($data['idempotent']);
    }

    /**
     * Test buildCachedResponse handles missing message in cached data
     */
    public function test_build_cached_response_handles_missing_message(): void
    {
        $cached = [
            'response' => [
                'data' => ['id' => 1],
                'status' => 200,
            ],
        ];

        $response = $this->buildCachedResponse($cached);

        $data = $response->getData(true);
        $this->assertNull($data['message']);
    }

    /**
     * Test cacheResponseOrFail throws RuntimeException when JSON decode fails
     */
    public function test_cache_response_or_fail_throws_on_invalid_json(): void
    {
        $key = 'invalid-json-key-' . uniqid();

        // Create a response with invalid JSON content
        $response = new JsonResponse(['data' => 'test']);
        $response->setContent('not valid json {');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to decode response JSON');

        $this->cacheResponseOrFail($key, $response);
    }

    /**
     * Test cacheResponseOrFail successfully caches valid response
     */
    public function test_cache_response_or_fail_caches_valid_response(): void
    {
        $key = 'valid-cache-key-' . uniqid();
        $response = new JsonResponse([
            'success' => true,
            'data' => ['id' => 100],
            'message' => 'Created successfully',
        ]);

        $this->cacheResponseOrFail($key, $response);

        $this->assertTrue($this->idempotencyService->isProcessed($key));
        $cached = $this->idempotencyService->getResponse($key);
        $this->assertEquals(['id' => 100], $cached['response']['data']);
        $this->assertEquals('Created successfully', $cached['response']['message']);
    }

    /**
     * Test cacheResponseOrFail throws RuntimeException when markProcessed fails
     */
    public function test_cache_response_or_fail_throws_when_mark_processed_fails(): void
    {
        $key = 'fail-cache-key-' . uniqid();

        // Create a mock that will throw when markProcessed is called
        $mockService = $this->getMockBuilder(IdempotencyService::class)
            ->onlyMethods(['markProcessed'])
            ->getMock();

        $mockService->method('markProcessed')
            ->willThrowException(new \RuntimeException('Cache failure'));

        $this->idempotencyService = $mockService;

        $response = new JsonResponse([
            'success' => true,
            'data' => ['id' => 1],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Idempotency cache failed after handler success');

        $this->cacheResponseOrFail($key, $response);
    }
}
