<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_all_categories_ordered_by_name(): void
    {
        Category::create(['name' => '玫瑰', 'slug' => 'rose', 'user_id' => null]);
        Category::create(['name' => '百合', 'slug' => 'lily', 'user_id' => null]);

        $response = $this->getJson('/api/categories');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');
    }

    public function test_index_returns_empty_array_when_no_categories(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => []]);
    }

    public function test_store_creates_category(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/categories', [
                'name' => '郁金香',
                'slug' => 'tulip',
                'icon' => '🌷',
                'description' => '荷兰国花',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'data' => ['name' => '郁金香', 'slug' => 'tulip']]);
        $this->assertDatabaseHas('categories', ['name' => '郁金香', 'slug' => 'tulip']);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => '测试',
            'slug' => 'test',
        ]);
        $response->assertStatus(401);
    }

    public function test_store_requires_name(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/categories', ['slug' => 'test']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_show_returns_category_by_id(): void
    {
        $category = Category::create(['name' => '测试', 'slug' => 'test', 'user_id' => null]);

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['name' => '测试']]);
    }

    public function test_show_returns_404_for_nonexistent_category(): void
    {
        $response = $this->getJson('/api/categories/99999');
        $response->assertStatus(404);
    }

    public function test_update_modifies_category(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;
        $category = Category::create(['name' => '旧名称', 'slug' => 'old', 'user_id' => null]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/categories/{$category->id}", [
                'name' => '新名称',
                'slug' => 'new',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['name' => '新名称']]);
        $this->assertDatabaseHas('categories', ['name' => '新名称']);
    }

    public function test_update_requires_authentication(): void
    {
        $category = Category::create(['name' => '测试', 'slug' => 'test', 'user_id' => null]);

        $response = $this->putJson("/api/categories/{$category->id}", ['name' => '新名称']);
        $response->assertStatus(401);
    }

    public function test_update_returns_404_for_nonexistent_category(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson('/api/categories/99999', ['name' => '测试']);
        $response->assertStatus(404);
    }

    public function test_destroy_deletes_category(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;
        $category = Category::create(['name' => '测试', 'slug' => 'test', 'user_id' => null]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => '删除成功']);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_destroy_requires_authentication(): void
    {
        $category = Category::create(['name' => '测试', 'slug' => 'test', 'user_id' => null]);

        $response = $this->deleteJson("/api/categories/{$category->id}");
        $response->assertStatus(401);
    }

    public function test_destroy_returns_404_for_nonexistent_category(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson('/api/categories/99999');
        $response->assertStatus(404);
    }
}
