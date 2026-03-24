<?php

namespace Tests\Unit\Traits;

use App\Http\Traits\ApiResponse;
use App\Http\Traits\Idempotency;
use App\Services\IdempotencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use ApiResponse, Idempotency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initIdempotency();
    }

    /**
     * Test initIdempotency initializes the service
     */
    public function test_init_idempotency_initializes_service(): void
    {
        $this->assertInstanceOf(IdempotencyService::class, $this->idempotencyService);
    }

    /**
     * Test getIdempotencyKey returns key from header
     */
    public function test_get_idempotency_key_returns_header_value(): void
    {
        $request = Request::create('/test', 'GET', [], [], [], [
            'HTTP_X-Idempotency-Key' => 'test-key-123',
        ]);

        $key = $this->getIdempotencyKey($request);

        $this->assertEquals('test-key-123', $key);
    }

    /**
     * Test getIdempotencyKey returns null when header is missing
     */
    public function test_get_idempotency_key_returns_null_when_missing(): void
    {
        $request = Request::create('/test', 'GET');

        $key = $this->getIdempotencyKey($request);

        $this->assertNull($key);
    }

    /**
     * Test handleIdempotentRequest proceeds without key when no idempotency key
     */
    public function test_handle_idempotent_request_proceeds_without_key(): void
    {
        $request = Request::create('/test', 'POST', ['data' => 'value']);

        $response = $this->handleIdempotentRequest($request, function () {
            return response()->json(['success' => true, 'data' => ['id' => 1]]);
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals(['id' => 1], $data['data']);
    }

    /**
     * Test handleIdempotentRequest returns cached response for processed key
     * (key provided via X-Idempotency-Key header)
     */
    public function test_handle_idempotent_request_returns_cached_response(): void
    {
        $key = 'cached-key-' . uniqid();
        $request = Request::create('/test', 'POST', ['data' => 'value'], [], [], [
            'HTTP_X-Idempotency-Key' => $key,
        ]);

        // Pre-cache a completed (non-pending) response
        $this->idempotencyService->markProcessed($key, [
            'data' => ['cached' => true],
            'message' => 'Cached response',
            'status' => 200,
            'pending' => false,
        ]);

        $response = $this->handleIdempotentRequest($request, function () {
            return response()->json(['success' => true, 'data' => ['new' => true]]);
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertTrue($data['idempotent']);
        $this->assertEquals(['cached' => true], $data['data']);
    }

    /**
     * Test handleIdempotentRequest returns 409 when lock is held by another request.
     * The lock serialization is now ALWAYS attempted (even for new keys) to prevent
     * concurrent new requests from both executing the handler simultaneously.
     */
    public function test_handle_idempotent_request_returns_409_when_lock_is_held(): void
    {
        $key = 'locked-key-' . uniqid();
        $request = Request::create('/test', 'POST', ['data' => 'value'], [], [], [
            'HTTP_X-Idempotency-Key' => $key,
        ]);

        // Acquire lock manually (simulating another request holding the lock)
        $this->idempotencyService->acquireLock($key, 30);

        $response = $this->handleIdempotentRequest($request, function () {
            return response()->json(['success' => true]);
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(409, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('请求正在处理中，请稍后重试', $data['message']);

        // Cleanup
        $this->idempotencyService->releaseLock($key);
    }

    /**
     * Test handleIdempotentRequest executes handler and caches response for a new key.
     * Always acquires lock first (even for new keys) to serialize concurrent requests.
     */
    public function test_handle_idempotent_request_executes_handler_and_caches(): void
    {
        $key = 'new-key-' . uniqid();
        $request = Request::create('/test', 'POST', ['data' => 'value'], [], [], [
            'HTTP_X-Idempotency-Key' => $key,
        ]);

        $response = $this->handleIdempotentRequest($request, function () {
            return response()->json([
                'success' => true,
                'data' => ['id' => 42],
                'message' => 'Created',
            ]);
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        // Verify the response was cached (key is marked processed)
        $this->assertTrue($this->idempotencyService->isProcessed($key));
        $cached = $this->idempotencyService->getResponse($key);
        $this->assertEquals(['id' => 42], $cached['response']['data']);
    }

    /**
     * Test handleIdempotentRequest releases lock in finally block
     */
    public function test_handle_idempotent_request_releases_lock_after_execution(): void
    {
        $key = 'finally-key-' . uniqid();
        $request = Request::create('/test', 'POST', ['data' => 'value'], [], [], [
            'HTTP_X-Idempotency-Key' => $key,
        ]);

        $this->handleIdempotentRequest($request, function () {
            return response()->json(['success' => true]);
        });

        // Lock should be released
        $this->assertFalse($this->idempotencyService->isLocked($key));
    }

    /**
     * Test handleIdempotentRequest releases lock even on handler exception
     */
    public function test_handle_idempotent_request_releases_lock_on_exception(): void
    {
        $key = 'exception-key-' . uniqid();
        $request = Request::create('/test', 'POST', ['data' => 'value'], [], [], [
            'HTTP_X-Idempotency-Key' => $key,
        ]);

        try {
            $this->handleIdempotentRequest($request, function () {
                throw new \Exception('Handler failed');
            });
        } catch (\Exception $e) {
            // Expected
        }

        // Lock should be released even on exception
        $this->assertFalse($this->idempotencyService->isLocked($key));
    }
}
