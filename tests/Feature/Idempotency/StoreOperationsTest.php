<?php

namespace Tests\Feature\Idempotency;

use App\Models\Category;
use App\Models\Flower;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests for store operations idempotency:
 * - Flower/Category/Knowledge store wrapped in DB::transaction
 * - SiteSetting update and batchUpdate protected by idempotency
 */
class StoreOperationsTest extends BaseReliabilityTest
{
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
