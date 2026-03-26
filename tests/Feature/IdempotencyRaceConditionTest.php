<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\IdempotencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests for race-condition / lock-first idempotency behavior.
 * Verifies that concurrent requests are correctly rejected when lock is held,
 * and that retries after completion return cached responses.
 */
class IdempotencyRaceConditionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->adminUser = User::factory()->create(['is_admin' => true]);
        $this->adminToken = $this->adminUser->createToken('admin')->plainTextToken;
    }

    /**
     * Helper to make authenticated requests with admin Bearer token.
     */
    protected function adminRequest(): static
    {
        return $this->withHeader('Authorization', "Bearer {$this->adminToken}");
    }

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
}
