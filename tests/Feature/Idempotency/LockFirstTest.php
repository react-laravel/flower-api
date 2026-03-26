<?php

namespace Tests\Feature\Idempotency;

use App\Services\IdempotencyService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Tests for lock-first idempotency behavior:
 * - Concurrent request returns 409 when lock is held
 * - Retry after lock release but isProcessed=true returns cached response
 */
class LockFirstTest extends BaseReliabilityTest
{
    /**
     * Test that when lock is held by an active request, concurrent request returns 409
     * even if isProcessed is still false (request still running handler).
     */
    public function test_concurrent_request_while_lock_held_returns_409(): void
    {
        $idempotencyKey = 'concurrent-lock-held-' . uniqid();

        // Simulate an active request holding the lock
        $idempotencyService = new IdempotencyService();
        $this->assertTrue($idempotencyService->acquireLock($idempotencyKey, 30));

        // Concurrent request should get 409 (lock is held, isProcessed is false)
        $response = $this->adminRequest()->postJson('/api/flowers', [
            'name' => 'Orchid',
            'name_en' => 'Orchid',
            'category' => 'exotic',
            'price' => 150,
            'original_price' => 180,
            'image' => 'orchid.jpg',
            'description' => 'An exotic flower',
            'meaning' => 'Luxury',
            'care' => 'Special care needed',
            'stock' => 10,
            'featured' => true,
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response->assertStatus(409);
        $idempotencyService->releaseLock($idempotencyKey);
    }

    /**
     * Test that when lock is released but isProcessed is true (first request completed),
     * a retry returns the cached response (200 with idempotent: true).
     */
    public function test_retry_after_lock_released_but_processed_returns_cached_response(): void
    {
        $idempotencyKey = 'retry-after-completion-' . uniqid();

        // Simulate first request completed: lock released, response cached
        $idempotencyService = new IdempotencyService();
        $idempotencyService->markProcessed($idempotencyKey, [
            'data' => ['id' => 999, 'name' => 'Pre-cached Rose'],
            'message' => null,
            'status' => 201,
        ]);

        // Retry should return cached response (lock is not held, but isProcessed is true)
        $response = $this->adminRequest()->postJson('/api/flowers', [
            'name' => 'Rose',
            'name_en' => 'Rose',
            'category' => 'romantic',
            'price' => 100,
            'original_price' => 120,
            'image' => 'rose.jpg',
            'description' => 'A beautiful red rose',
            'meaning' => 'Love',
            'care' => 'Water daily',
            'stock' => 50,
            'featured' => true,
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response->assertStatus(200);
        $response->assertJson(['idempotent' => true]);
        $this->assertEquals('Pre-cached Rose', $response->json('data.name'));
    }

    /**
     * Test that concurrent locked upload request returns 409.
     */
    public function test_upload_concurrent_locked_request_returns_409(): void
    {
        Storage::fake('public');

        $idempotencyKey = 'upload-concurrent-' . uniqid();
        $idempotencyService = new IdempotencyService();
        $idempotencyService->acquireLock($idempotencyKey, 30);

        $file = UploadedFile::fake()->image('flower.jpg', 800, 600);

        $response = $this->adminRequest()->postJson('/api/upload', [
            'image' => $file,
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response->assertStatus(409);

        $idempotencyService->releaseLock($idempotencyKey);
    }

    /**
     * Test that concurrent locked register request returns 409.
     */
    public function test_register_concurrent_locked_request_returns_409(): void
    {
        $idempotencyKey = 'register-concurrent-' . uniqid();
        $idempotencyService = new IdempotencyService();
        $idempotencyService->acquireLock($idempotencyKey, 30);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Concurrent User',
            'email' => 'concurrent@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response->assertStatus(409);

        $idempotencyService->releaseLock($idempotencyKey);
    }
}
