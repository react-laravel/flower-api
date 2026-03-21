<?php

namespace Tests\Feature;

use App\Models\Knowledge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_knowledge(): void
    {
        Knowledge::create(['question' => '问题1', 'answer' => '答案1', 'category' => 'care', 'user_id' => null]);
        Knowledge::create(['question' => '问题2', 'answer' => '答案2', 'category' => 'shipping', 'user_id' => null]);

        $response = $this->getJson('/api/knowledge');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['items', 'total', 'current_page', 'last_page', 'per_page'],
            ])
            ->assertJsonPath('data.total', 2);
    }

    public function test_index_returns_empty_when_no_knowledge(): void
    {
        $response = $this->getJson('/api/knowledge');

        $response->assertOk()
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.total', 0);
    }

    public function test_index_orders_by_category(): void
    {
        Knowledge::create(['question' => 'ZZZ', 'answer' => '答案', 'category' => 'zzz', 'user_id' => null]);
        Knowledge::create(['question' => 'AAA', 'answer' => '答案', 'category' => 'aaa', 'user_id' => null]);

        $response = $this->getJson('/api/knowledge');

        $response->assertOk()
            ->assertJsonPath('data.items.0.category', 'aaa')
            ->assertJsonPath('data.items.1.category', 'zzz');
    }

    public function test_index_respects_per_page(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Knowledge::create(['question' => "问题{$i}", 'answer' => "答案{$i}", 'category' => 'test', 'user_id' => null]);
        }

        $response = $this->getJson('/api/knowledge?per_page=2');

        $response->assertOk()
            ->assertJsonPath('data.per_page', 2)
            ->assertJsonPath('data.last_page', 3);
    }

    public function test_index_caps_per_page_at_100(): void
    {
        $response = $this->getJson('/api/knowledge?per_page=200');
        $response->assertOk()
            ->assertJsonPath('data.per_page', 100);
    }

    public function test_store_creates_knowledge(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/knowledge', [
                'question' => '如何订花？',
                'answer' => '请拨打热线',
                'category' => 'ordering',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'data' => ['question' => '如何订花？']]);
        $this->assertDatabaseHas('knowledge', ['question' => '如何订花？']);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/knowledge', [
            'question' => '测试',
            'answer' => '测试',
            'category' => 'test',
        ]);
        $response->assertStatus(401);
    }

    public function test_store_requires_question(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/knowledge', ['answer' => '答案', 'category' => 'test']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question']);
    }

    public function test_store_requires_answer(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/knowledge', ['question' => '问题', 'category' => 'test']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['answer']);
    }

    public function test_show_returns_knowledge_by_id(): void
    {
        $knowledge = Knowledge::create(['question' => '测试问题', 'answer' => '测试答案', 'category' => 'test', 'user_id' => null]);

        $response = $this->getJson("/api/knowledge/{$knowledge->id}");

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['question' => '测试问题']]);
    }

    public function test_show_returns_404_for_nonexistent_knowledge(): void
    {
        $response = $this->getJson('/api/knowledge/99999');
        $response->assertStatus(404);
    }

    public function test_update_modifies_knowledge(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;
        $knowledge = Knowledge::create(['question' => '旧问题', 'answer' => '旧答案', 'category' => 'old', 'user_id' => null]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/knowledge/{$knowledge->id}", [
                'question' => '新问题',
                'answer' => '新答案',
                'category' => 'new',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['question' => '新问题']]);
        $this->assertDatabaseHas('knowledge', ['question' => '新问题']);
    }

    public function test_update_requires_authentication(): void
    {
        $knowledge = Knowledge::create(['question' => '测试', 'answer' => '测试', 'category' => 'test', 'user_id' => null]);

        $response = $this->putJson("/api/knowledge/{$knowledge->id}", ['question' => '新']);
        $response->assertStatus(401);
    }

    public function test_update_returns_404_for_nonexistent_knowledge(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson('/api/knowledge/99999', ['question' => '测试']);
        $response->assertStatus(404);
    }

    public function test_destroy_deletes_knowledge(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;
        $knowledge = Knowledge::create(['question' => '测试', 'answer' => '测试', 'category' => 'test', 'user_id' => null]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/knowledge/{$knowledge->id}");

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => '删除成功']);
        $this->assertDatabaseMissing('knowledge', ['id' => $knowledge->id]);
    }

    public function test_destroy_requires_authentication(): void
    {
        $knowledge = Knowledge::create(['question' => '测试', 'answer' => '测试', 'category' => 'test', 'user_id' => null]);

        $response = $this->deleteJson("/api/knowledge/{$knowledge->id}");
        $response->assertStatus(401);
    }

    public function test_destroy_returns_404_for_nonexistent_knowledge(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson('/api/knowledge/99999');
        $response->assertStatus(404);
    }
}
