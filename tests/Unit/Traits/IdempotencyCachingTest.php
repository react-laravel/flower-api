<?php

namespace Tests\Unit\Traits;

use App\Http\Traits\IdempotencyCaching;
use App\Services\IdempotencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class IdempotencyCachingTest extends TestCase
{
    use IdempotencyCaching;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->idempotencyService = new IdempotencyService('array');
    }

    public function test_get_cached_response_returns_null_when_not_cached(): void
    {
        $result = $this->getCachedResponse('nonexistent-key');

        $this->assertNull($result);
    }

    public function test_get_cached_response_returns_cached_data(): void
    {
        $key = 'cached-key-' . uniqid();
        $this->idempotencyService->markProcessed($key, [
            'data' => ['id' => 42],
            'message' => 'OK',
            'status' => 200,
            'pending' => false,
        ]);

        $result = $this->getCachedResponse($key);

        $this->assertIsArray($result);
        $this->assertEquals(['id' => 42], $result['response']['data']);
    }

    public function test_mark_processed_stores_response(): void
    {
        $key = 'mark-processed-' . uniqid();

        $this->markProcessed($key, [
            'data' => ['foo' => 'bar'],
            'message' => 'Success',
            'status' => 200,
            'pending' => false,
        ]);

        $this->assertTrue($this->idempotencyService->isProcessed($key));
        $cached = $this->idempotencyService->getResponse($key);
        $this->assertEquals(['foo' => 'bar'], $cached['response']['data']);
    }

    public function test_build_cached_response_returns_json_response(): void
    {
        $cached = [
            'response' => [
                'data' => ['id' => 99],
                'message' => 'Cached',
            ],
        ];

        $response = $this->buildCachedResponse($cached);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertTrue($data['idempotent']);
        $this->assertEquals(['id' => 99], $data['data']);
        $this->assertEquals('Cached', $data['message']);
    }

    public function test_build_cached_response_handles_missing_message(): void
    {
        $cached = [
            'response' => [
                'data' => ['id' => 1],
            ],
        ];

        $response = $this->buildCachedResponse($cached);

        $data = $response->getData(true);
        $this->assertNull($data['message']);
    }

    public function test_cache_response_or_fail_stores_successful_response(): void
    {
        $key = 'cache-success-' . uniqid();
        $response = response()->json([
            'success' => true,
            'data' => ['id' => 123],
            'message' => 'Done',
        ]);

        $this->cacheResponseOrFail($key, $response);

        $this->assertTrue($this->idempotencyService->isProcessed($key));
    }

    public function test_cache_response_or_fail_throws_when_json_decode_fails(): void
    {
        $key = 'bad-json-' . uniqid();
        // Create a response with invalid JSON in content
        $response = new JsonResponse(['data' => 'test']);
        // Override content to be invalid JSON
        $reflector = new \ReflectionClass($response);
        $prop = $reflector->getProperty('content');
        $prop->setAccessible(true);
        $prop->setValue($response, 'not-valid-json{');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Failed to decode response JSON/');

        $this->cacheResponseOrFail($key, $response);
    }

    public function test_cache_response_or_fail_throws_when_mark_processed_fails(): void
    {
        $key = 'mark-fail-' . uniqid();
        $response = response()->json(['success' => true, 'data' => ['id' => 1]]);

        // Simulate markProcessed throwing by using a service that throws
        $faultyService = new class extends IdempotencyService {
            public function __construct()
            {
                parent::__construct('array');
            }
            public function markProcessed(string $key, mixed $response, int $ttl = 86400): void
            {
                throw new \RuntimeException('Cache write failed');
            }
        };

        // Swap in the faulty service via reflection
        $reflection = new \ReflectionClass($this);
        $prop = $reflection->getProperty('idempotencyService');
        $prop->setAccessible(true);
        $prop->setValue($this, $faultyService);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Idempotency cache failed/');

        $this->cacheResponseOrFail($key, $response);
    }

    public function test_cache_response_or_fail_throws_on_cache_failure_prevents_duplicate_execution(): void
    {
        $key = 'duplicate-prevent-' . uniqid();
        $response = response()->json(['success' => true, 'data' => ['id' => 1]]);

        // The service should NOT have marked the key as processed after a failure
        // Simulate a failure in markProcessed
        $faultyService = new class extends IdempotencyService {
            public function __construct()
            {
                parent::__construct('array');
            }
            public function markProcessed(string $key, mixed $response, int $ttl = 86400): void
            {
                throw new \RuntimeException('Cache write failed');
            }
        };

        $reflection = new \ReflectionClass($this);
        $prop = $reflection->getProperty('idempotencyService');
        $prop->setAccessible(true);
        $prop->setValue($this, $faultyService);

        try {
            $this->cacheResponseOrFail($key, $response);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            // Verify the key was NOT marked processed (preventing duplicate execution)
            $this->assertStringContainsString('Idempotency cache failed', $e->getMessage());
        }
    }
}
