<?php

namespace Tests\Feature;

use App\Models\Flower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlowerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_flowers(): void
    {
        Flower::create(['name' => '玫瑰', 'name_en' => 'Rose', 'category' => 'rose', 'price' => 99, 'user_id' => null]);
        Flower::create(['name' => '百合', 'name_en' => 'Lily', 'category' => 'lily', 'price' => 79, 'user_id' => null]);

        $response = $this->getJson('/api/flowers');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['items', 'total', 'current_page', 'last_page', 'per_page'],
            ])
            ->assertJsonPath('data.total', 2);
    }

    public function test_index_returns_empty_when_no_flowers(): void
    {
        $response = $this->getJson('/api/flowers');

        $response->assertOk()
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.total', 0);
    }

    public function test_index_filters_by_category(): void
    {
        Flower::create(['name' => '红玫瑰', 'name_en' => 'Red Rose', 'category' => 'rose', 'price' => 99, 'user_id' => null]);
        Flower::create(['name' => '白百合', 'name_en' => 'White Lily', 'category' => 'lily', 'price' => 79, 'user_id' => null]);

        $response = $this->getJson('/api/flowers?category=rose');

        $response->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.category', 'rose');
    }

    public function test_index_filters_by_featured(): void
    {
        Flower::create(['name' => '推荐玫瑰', 'name_en' => 'Featured Rose', 'category' => 'rose', 'price' => 99, 'featured' => true, 'user_id' => null]);
        Flower::create(['name' => '普通百合', 'name_en' => 'Normal Lily', 'category' => 'lily', 'price' => 79, 'featured' => false, 'user_id' => null]);

        $response = $this->getJson('/api/flowers?featured=true');

        $response->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.featured', true);
    }

    public function test_index_filters_by_search(): void
    {
        Flower::create(['name' => '红玫瑰', 'name_en' => 'Red Rose', 'category' => 'rose', 'price' => 99, 'user_id' => null]);
        Flower::create(['name' => '白玫瑰', 'name_en' => 'White Rose', 'category' => 'rose', 'price' => 89, 'user_id' => null]);

        $response = $this->getJson('/api/flowers?search=红');

        $response->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.name', '红玫瑰');
    }

    public function test_index_searches_by_english_name(): void
    {
        Flower::create(['name' => '红玫瑰', 'name_en' => 'Red Rose', 'category' => 'rose', 'price' => 99, 'user_id' => null]);

        $response = $this->getJson('/api/flowers?search=Red');

        $response->assertOk()
            ->assertJsonPath('data.total', 1);
    }

    public function test_index_respects_per_page_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Flower::create(['name' => "花{$i}", 'name_en' => "Flower{$i}", 'category' => 'test', 'price' => 10, 'user_id' => null]);
        }

        $response = $this->getJson('/api/flowers?per_page=2');

        $response->assertOk()
            ->assertJsonPath('data.per_page', 2)
            ->assertJsonPath('data.last_page', 3);
    }

    public function test_index_caps_per_page_at_100(): void
    {
        $response = $this->getJson('/api/flowers?per_page=200');
        $response->assertOk()
            ->assertJsonPath('data.per_page', 100);
    }

    public function test_store_creates_flower(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/flowers', [
                'name' => '郁金香',
                'name_en' => 'Tulip',
                'category' => 'tulip',
                'price' => 129,
                'original_price' => 169,
                'description' => '荷兰名花',
                'stock' => 50,
                'featured' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'data' => ['name' => '郁金香']]);
        $this->assertDatabaseHas('flowers', ['name' => '郁金香', 'category' => 'tulip']);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/flowers', [
            'name' => '测试',
            'name_en' => 'Test',
            'category' => 'test',
            'price' => 10,
        ]);
        $response->assertStatus(401);
    }

    public function test_store_requires_name(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/flowers', ['name_en' => 'Test', 'category' => 'test', 'price' => 10]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_show_returns_flower_by_id(): void
    {
        $flower = Flower::create(['name' => '测试', 'name_en' => 'Test', 'category' => 'test', 'price' => 10, 'user_id' => null]);

        $response = $this->getJson("/api/flowers/{$flower->id}");

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['name' => '测试']]);
    }

    public function test_show_returns_404_for_nonexistent_flower(): void
    {
        $response = $this->getJson('/api/flowers/99999');
        $response->assertStatus(404);
    }

    public function test_update_modifies_flower(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;
        $flower = Flower::create(['name' => '旧名称', 'name_en' => 'Old', 'category' => 'old', 'price' => 10, 'user_id' => null]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/flowers/{$flower->id}", [
                'name' => '新名称',
                'name_en' => 'New',
                'category' => 'new',
                'price' => 20,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['name' => '新名称']]);
        $this->assertDatabaseHas('flowers', ['name' => '新名称']);
    }

    public function test_update_requires_authentication(): void
    {
        $flower = Flower::create(['name' => '测试', 'name_en' => 'Test', 'category' => 'test', 'price' => 10, 'user_id' => null]);

        $response = $this->putJson("/api/flowers/{$flower->id}", ['name' => '新']);
        $response->assertStatus(401);
    }

    public function test_update_returns_404_for_nonexistent_flower(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson('/api/flowers/99999', ['name' => '测试']);
        $response->assertStatus(404);
    }

    public function test_destroy_deletes_flower(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;
        $flower = Flower::create(['name' => '测试', 'name_en' => 'Test', 'category' => 'test', 'price' => 10, 'user_id' => null]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/flowers/{$flower->id}");

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => '删除成功']);
        $this->assertDatabaseMissing('flowers', ['id' => $flower->id]);
    }

    public function test_destroy_requires_authentication(): void
    {
        $flower = Flower::create(['name' => '测试', 'name_en' => 'Test', 'category' => 'test', 'price' => 10, 'user_id' => null]);

        $response = $this->deleteJson("/api/flowers/{$flower->id}");
        $response->assertStatus(401);
    }

    public function test_destroy_returns_404_for_nonexistent_flower(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson('/api/flowers/99999');
        $response->assertStatus(404);
    }
}
