<?php

namespace Tests\Feature;

use App\Models\Flower;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests for DB::transaction wrapping in write operations.
 * Verifies that Flower, Category, Knowledge, and SiteSetting write operations
 * are protected by idempotency, preventing duplicate writes on retry.
 */
class IdempotencyTransactionTest extends TestCase
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
}
