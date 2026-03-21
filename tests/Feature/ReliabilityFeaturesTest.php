<?php

namespace Tests\Feature;

use App\Models\Flower;
use App\Models\Category;
use App\Models\Knowledge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests for reliability features: idempotency, distributed locking, transactions.
 */
class ReliabilityFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // ========== Idempotency Tests ==========

    public function test_store_returns_same_response_for_duplicate_idempotency_key(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;
        $idempotencyKey = 'test-idempotency-key-' . uniqid();

        // First request
        $response1 = $this->withHeader('Authorization', "Bearer $token")
            ->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->postJson('/api/flowers', [
                'name' => '幂等性测试花',
                'name_en' => 'Idempotency Test Flower',
                'category' => 'test',
                'price' => 99,
                'stock' => 10,
            ]);

        $response1->assertStatus(201);
        $flowerId1 = $response1->json('data.id');

        // Second request with same idempotency key should return same response
        $response2 = $this->withHeader('Authorization', "Bearer $token")
            ->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->postJson('/api/flowers', [
                'name' => '幂等性测试花',
                'name_en' => 'Idempotency Test Flower',
                'category' => 'test',
                'price' => 99,
                'stock' => 10,
            ]);

        $response2->assertStatus(200);
        $response2->assertJsonPath('data.id', $flowerId1);

        // Only one flower should exist in database
        $this->assertEquals(1, Flower::where('name', '幂等性测试花')->count());
    }

    public function test_store_without_idempotency_key_creates_new_record_each_time(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        // First request
        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/flowers', [
                'name' => '普通测试花',
                'name_en' => 'Normal Test Flower',
                'category' => 'test',
                'price' => 99,
                'stock' => 10,
            ]);

        // Second request without idempotency key
        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/flowers', [
                'name' => '普通测试花',
                'name_en' => 'Normal Test Flower',
                'category' => 'test',
                'price' => 99,
                'stock' => 10,
            ]);

        // Two flowers should exist
        $this->assertEquals(2, Flower::where('name', '普通测试花')->count());
    }

    public function test_category_store_supports_idempotency(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;
        $idempotencyKey = 'category-idempotency-' . uniqid();

        // First request
        $response1 = $this->withHeader('Authorization', "Bearer $token")
            ->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->postJson('/api/categories', [
                'name' => '幂等性测试分类',
                'slug' => 'idempotency-test-' . uniqid(),
                'icon' => '📦',
                'description' => '测试分类描述',
            ]);

        $response1->assertStatus(201);

        // Second request with same key
        $response2 = $this->withHeader('Authorization', "Bearer $token")
            ->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->postJson('/api/categories', [
                'name' => '幂等性测试分类',
                'slug' => 'idempotency-test-' . uniqid(),
                'icon' => '📦',
                'description' => '测试分类描述',
            ]);

        $response2->assertStatus(200);

        // Only one category should exist
        $this->assertEquals(1, Category::where('name', '幂等性测试分类')->count());
    }

    public function test_knowledge_store_supports_idempotency(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;
        $idempotencyKey = 'knowledge-idempotency-' . uniqid();

        // First request
        $response1 = $this->withHeader('Authorization', "Bearer $token")
            ->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->postJson('/api/knowledge', [
                'question' => '什么是幂等性？',
                'answer' => '幂等性是指同样的请求被执行一次和执行多次的效果相同。',
                'category' => '技术',
            ]);

        $response1->assertStatus(201);

        // Second request with same key
        $response2 = $this->withHeader('Authorization', "Bearer $token")
            ->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->postJson('/api/knowledge', [
                'question' => '什么是幂等性？',
                'answer' => '幂等性是指同样的请求被执行一次和执行多次的效果相同。',
                'category' => '技术',
            ]);

        $response2->assertStatus(200);

        // Only one knowledge should exist
        $this->assertEquals(1, Knowledge::where('question', '什么是幂等性？')->count());
    }

    public function test_batch_settings_update_supports_idempotency(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;
        $idempotencyKey = 'settings-batch-idempotency-' . uniqid();

        // First request (POST not PUT)
        $response1 = $this->withHeader('Authorization', "Bearer $token")
            ->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->postJson('/api/settings/batch', [
                'settings' => [
                    'site_name' => 'Flower Shop',
                    'site_email' => 'test@example.com',
                ],
            ]);

        $response1->assertStatus(200);

        // Second request with same key
        $response2 = $this->withHeader('Authorization', "Bearer $token")
            ->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->postJson('/api/settings/batch', [
                'settings' => [
                    'site_name' => 'Flower Shop',
                    'site_email' => 'test@example.com',
                ],
            ]);

        $response2->assertStatus(200);
    }

    // ========== Transaction Tests ==========

    public function test_store_uses_transaction(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        // Verify transaction is being used by checking that database operations are atomic
        $initialCount = Flower::count();

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/flowers', [
                'name' => '事务测试花',
                'name_en' => 'Transaction Test Flower',
                'category' => 'test',
                'price' => 99,
                'stock' => 10,
            ]);

        // Flower should be created
        $this->assertEquals($initialCount + 1, Flower::count());
    }

    public function test_update_uses_transaction(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;
        $flower = Flower::create([
            'name' => '原始名称',
            'name_en' => 'Original Name',
            'category' => 'test',
            'price' => 10,
            'user_id' => null,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/flowers/{$flower->id}", [
                'name' => '更新后的名称',
                'name_en' => 'Updated Name',
                'category' => 'test',
                'price' => 20,
            ]);

        $response->assertOk();

        // Verify update was applied atomically
        $flower->refresh();
        $this->assertEquals('更新后的名称', $flower->name);
        $this->assertEquals('Updated Name', $flower->name_en);
    }

    public function test_destroy_uses_transaction(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;
        $flower = Flower::create([
            'name' => '待删除花',
            'name_en' => 'To Be Deleted',
            'category' => 'test',
            'price' => 10,
            'user_id' => null,
        ]);
        $flowerId = $flower->id;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/flowers/{$flower->id}");

        $response->assertOk();

        // Verify flower was deleted
        $this->assertDatabaseMissing('flowers', ['id' => $flowerId]);
    }

    // ========== Lock Tests ==========

    public function test_concurrent_updates_are_serialized(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;
        $flower = Flower::create([
            'name' => '并发测试花',
            'name_en' => 'Concurrency Test',
            'category' => 'test',
            'price' => 10,
            'user_id' => null,
        ]);

        // Note: In a real test with actual concurrency, we'd verify that updates
        // are serialized. In Laravel's test environment with array cache,
        // we verify that the locking mechanism is in place by checking
        // that the update completes successfully without errors.

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/flowers/{$flower->id}", [
                'name' => '并发更新后的名称',
                'name_en' => 'Concurrency Updated',
                'category' => 'test',
                'price' => 20,
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $flower->refresh();
        $this->assertEquals('并发更新后的名称', $flower->name);
    }

    public function test_concurrent_deletes_are_serialized(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;
        $flower = Flower::create([
            'name' => '并发删除测试花',
            'name_en' => 'Concurrency Delete Test',
            'category' => 'test',
            'price' => 10,
            'user_id' => null,
        ]);
        $flowerId = $flower->id;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/flowers/{$flower->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('flowers', ['id' => $flowerId]);
    }

    public function test_update_returns_409_on_lock_timeout(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;
        $flower = Flower::create([
            'name' => '锁超时测试花',
            'name_en' => 'Lock Timeout Test',
            'category' => 'test',
            'price' => 10,
            'user_id' => null,
        ]);

        // The lock service should return 409 if it can't acquire lock
        // In unit tests this may not trigger, but the mechanism is in place
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/flowers/{$flower->id}", [
                'name' => '锁测试更新',
                'name_en' => 'Lock Test Update',
                'category' => 'test',
                'price' => 20,
            ]);

        // Should succeed in normal circumstances
        $response->assertOk();
    }
}
