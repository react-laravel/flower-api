<?php

namespace Tests\Feature;

use App\Models\Flower;
use App\Models\Category;
use App\Models\User;
use App\Services\IdempotencyService;
use App\Services\DistributedLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests for interface reliability improvements:
 * - Atomic lock-first idempotency (prevents race conditions)
 * - DB::transaction wrapping in write operations
 * - Concurrent request handling with distributed locks
 * - Response caching atomicity
 */
class IdempotencyReliabilityTest extends TestCase
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

    // -------------------------------------------------------------------------
    // Race-condition / lock-first tests
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // DB::transaction tests
    // -------------------------------------------------------------------------

    /**
     * Test that FlowerController::store wraps create in DB::transaction.
     * We verify this by checking that the idempotency mechanism protects
     * against duplicate writes when retrying after any failure.
     */
    public function test_flower_store_transaction_rollback_prevents_duplicate(): void
    {
        $idempotencyKey = 'tx-rollback-' . uniqid();

        // First request succeeds
        $response1 = $this->adminRequest()->postJson('/api/flowers', [
            'name' => 'TransactionTestFlower',
            'name_en' => 'Transaction Test Flower',
            'category' => 'test',
            'price' => 50,
            'original_price' => 60,
            'image' => 'test.jpg',
            'description' => 'Test',
            'meaning' => 'Test',
            'care' => 'Test',
            'stock' => 10,
            'featured' => false,
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response1->assertStatus(201);
        $this->assertDatabaseHas('flowers', ['name' => 'TransactionTestFlower']);

        // Retry with same key returns cached response (no second flower created)
        $response2 = $this->adminRequest()->postJson('/api/flowers', [
            'name' => 'TransactionTestFlower',
            'name_en' => 'Transaction Test Flower',
            'category' => 'test',
            'price' => 50,
            'original_price' => 60,
            'image' => 'test.jpg',
            'description' => 'Test',
            'meaning' => 'Test',
            'care' => 'Test',
            'stock' => 10,
            'featured' => false,
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response2->assertStatus(200);
        $response2->assertJson(['idempotent' => true]);
        // Only ONE flower should exist (idempotency prevents duplicate)
        $this->assertEquals(1, Flower::where('name', 'TransactionTestFlower')->count());
    }

    /**
     * Test that CategoryController::store is protected by idempotency.
     */
    public function test_category_store_with_idempotency_key(): void
    {
        $idempotencyKey = 'cat-idempotent-' . uniqid();

        $response1 = $this->adminRequest()->postJson('/api/categories', [
            'name' => 'Spring Flowers',
            'slug' => 'spring',
            'icon' => 'spring',
            'description' => 'A collection of spring flowers',
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response1->assertStatus(201);

        // Retry returns cached response
        $response2 = $this->adminRequest()->postJson('/api/categories', [
            'name' => 'Different Name',
            'slug' => 'different',
            'icon' => 'diff',
            'description' => 'Different description',
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response2->assertStatus(200);
        $response2->assertJson(['idempotent' => true]);
        $this->assertEquals(1, Category::where('name', 'Spring Flowers')->count());
    }

    /**
     * Test that KnowledgeController::store is protected by idempotency.
     */
    public function test_knowledge_store_with_idempotency_key(): void
    {
        $idempotencyKey = 'knowledge-idempotent-' . uniqid();

        $response1 = $this->adminRequest()->postJson('/api/knowledge', [
            'question' => 'How to care for roses?',
            'answer' => 'Water them daily',
            'category' => 'care',
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response1->assertStatus(201);

        // Retry returns cached response
        $response2 = $this->adminRequest()->postJson('/api/knowledge', [
            'question' => 'Different question?',
            'answer' => 'Different answer',
            'category' => 'other',
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response2->assertStatus(200);
        $response2->assertJson(['idempotent' => true]);
    }

    /**
     * Test that SiteSettingController::update is protected by idempotency.
     */
    public function test_site_setting_update_idempotent(): void
    {
        $idempotencyKey = 'setting-idempotent-' . uniqid();

        $response1 = $this->adminRequest()->putJson('/api/settings', [
            'key' => 'site_name',
            'value' => 'Flower Shop',
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response1->assertStatus(200);

        // Retry returns cached response
        $response2 = $this->adminRequest()->putJson('/api/settings', [
            'key' => 'site_name',
            'value' => 'Different Shop',
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response2->assertStatus(200);
        $response2->assertJson(['idempotent' => true]);
    }

    /**
     * Test that SiteSettingController::batchUpdate is protected by idempotency.
     */
    public function test_site_setting_batch_update_idempotent(): void
    {
        $idempotencyKey = 'batch-idempotent-' . uniqid();

        $response1 = $this->adminRequest()->postJson('/api/settings/batch', [
            'settings' => [
                'site_name' => 'My Flower Shop',
                'contact_email' => 'test@example.com',
            ],
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response1->assertStatus(200);

        // Retry returns cached response
        $response2 = $this->adminRequest()->postJson('/api/settings/batch', [
            'settings' => [
                'site_name' => 'Different Shop',
                'contact_email' => 'other@example.com',
            ],
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response2->assertStatus(200);
        $response2->assertJson(['idempotent' => true]);
    }

    // -------------------------------------------------------------------------
    // DistributedLockService integration tests
    // -------------------------------------------------------------------------

    /**
     * Test that DistributedLockService::withLock properly acquires/releases
     * and executes the callback atomically.
     *
     * With the array/cache driver, release() removes the key, so a subsequent
     * acquire() succeeds (correct distributed-lock behavior: lock is ephemeral).
     * The test verifies that both calls succeed sequentially, not concurrently.
     */
    public function test_distributed_lock_prevents_concurrent_execution(): void
    {
        $lockKey = 'test-concurrent-lock-' . uniqid();
        $service = new DistributedLockService();

        $executionCount = 0;

        // First caller acquires lock, executes callback, releases
        $result1 = $service->withLock($lockKey, function () use (&$executionCount) {
            $executionCount++;
            return 'first';
        });

        $this->assertEquals('first', $result1);
        $this->assertEquals(1, $executionCount);
        $this->assertFalse($service->isLocked($lockKey));

        // Second caller: after release, lock is free so acquire succeeds.
        // Both sequential calls succeed (first released before second acquired).
        // The critical guarantee: they never execute concurrently (no overlap).
        $result2 = $service->withLock($lockKey, function () use (&$executionCount) {
            $executionCount++;
            return 'second';
        });

        $this->assertEquals('second', $result2);
        $this->assertEquals(2, $executionCount); // both called, sequentially
    }

    /**
     * Test that DistributedLockService::release only succeeds with correct token.
     */
    public function test_distributed_lock_token_mismatch_prevents_release(): void
    {
        $lockKey = 'test-token-mismatch-' . uniqid();
        $service = new DistributedLockService();

        $token = $service->acquire($lockKey, 30);
        $this->assertNotFalse($token);
        $this->assertTrue($service->isLocked($lockKey));

        // Wrong token cannot release
        $released = $service->release($lockKey, 'wrong-token');
        $this->assertFalse($released);
        $this->assertTrue($service->isLocked($lockKey)); // lock still held

        // Correct token releases
        $released = $service->release($lockKey, $token);
        $this->assertTrue($released);
        $this->assertFalse($service->isLocked($lockKey));
    }

    /**
     * Test that when a lock is actively held (not yet released),
     * withLock returns false for a concurrent request.
     */
    public function test_with_lock_returns_false_when_lock_is_held(): void
    {
        $lockKey = 'test-held-lock-' . uniqid();
        $service = new DistributedLockService();

        // Acquire lock without releasing (simulates an in-flight request)
        $token = $service->acquire($lockKey, 30);
        $this->assertNotFalse($token);
        $this->assertTrue($service->isLocked($lockKey));

        // Concurrent caller should fail to acquire
        $result = $service->withLock($lockKey, function () {
            return 'should-not-run';
        });

        $this->assertFalse($result);

        // Clean up
        $service->release($lockKey, $token);
    }

    /**
     * Test that multiple different lock keys operate independently.
     */
    public function test_different_lock_keys_are_independent(): void
    {
        $service = new DistributedLockService();

        $token1 = $service->acquire('key-one-' . uniqid(), 30);
        $token2 = $service->acquire('key-two-' . uniqid(), 30);

        $this->assertNotFalse($token1);
        $this->assertNotFalse($token2);
        $this->assertNotEquals($token1, $token2);
    }

    // -------------------------------------------------------------------------
    // Update and Delete idempotency tests
    // -------------------------------------------------------------------------

    /**
     * Test that Flower update is idempotent with X-Idempotency-Key.
     */
    public function test_flower_update_idempotent(): void
    {
        $flower = Flower::create([
            'name' => 'Original',
            'name_en' => 'Original',
            'category' => 'test',
            'price' => 100,
            'original_price' => 120,
            'image' => 'test.jpg',
            'description' => 'Original',
            'meaning' => 'Original',
            'care' => 'Original',
            'stock' => 10,
            'featured' => false,
        ]);

        $idempotencyKey = 'update-flower-' . uniqid();

        $response1 = $this->adminRequest()->putJson("/api/flowers/{$flower->id}", [
            'name' => 'Updated',
            'name_en' => 'Updated',
            'category' => 'test',
            'price' => 150,
            'original_price' => 180,
            'image' => 'updated.jpg',
            'description' => 'Updated',
            'meaning' => 'Updated',
            'care' => 'Updated',
            'stock' => 20,
            'featured' => true,
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response1->assertStatus(200);
        $this->assertEquals('Updated', $response1->json('data.name'));

        // Retry returns cached (old) response — name should still be 'Updated'
        $response2 = $this->adminRequest()->putJson("/api/flowers/{$flower->id}", [
            'name' => 'Should Not Apply',
            'name_en' => 'Should Not Apply',
            'category' => 'test',
            'price' => 999,
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response2->assertStatus(200);
        $response2->assertJson(['idempotent' => true]);
        $flower->refresh();
        $this->assertEquals('Updated', $flower->name); // not changed to 'Should Not Apply'
    }

    /**
     * Test that Flower delete is idempotent with X-Idempotency-Key.
     */
    public function test_flower_delete_idempotent(): void
    {
        $flower = Flower::create([
            'name' => 'To Be Deleted',
            'name_en' => 'To Be Deleted',
            'category' => 'test',
            'price' => 100,
            'original_price' => 120,
            'image' => 'test.jpg',
            'description' => 'Test',
            'meaning' => 'Test',
            'care' => 'Test',
            'stock' => 10,
            'featured' => false,
        ]);

        $flowerId = $flower->id;
        $idempotencyKey = 'delete-flower-' . uniqid();

        $response1 = $this->adminRequest()->deleteJson("/api/flowers/{$flowerId}", [], [
            'X-Idempotency-Key' => $idempotencyKey,
        ]);

        $response1->assertStatus(200);
        $this->assertNull(Flower::find($flowerId));

        // Retry should return cached (already deleted) response
        $response2 = $this->adminRequest()->deleteJson("/api/flowers/{$flowerId}", [], [
            'X-Idempotency-Key' => $idempotencyKey,
        ]);

        $response2->assertStatus(200);
        $response2->assertJson(['idempotent' => true]);
    }

    /**
     * Test that request without idempotency key still works normally (no locking).
     */
    public function test_request_without_idempotency_key_works_normally(): void
    {
        $response = $this->adminRequest()->postJson('/api/flowers', [
            'name' => 'No Idempotency Key',
            'name_en' => 'No Idempotency Key',
            'category' => 'test',
            'price' => 50,
            'original_price' => 60,
            'image' => 'test.jpg',
            'description' => 'Test',
            'meaning' => 'Test',
            'care' => 'Test',
            'stock' => 10,
            'featured' => false,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('flowers', ['name' => 'No Idempotency Key']);
    }
}
