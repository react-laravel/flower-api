<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_returns_all_categories(): void
    {
        Category::factory()->count(2)->create();

        $response = $this->getJson('/api/categories');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');
    }

    public function test_index_returns_categories_ordered_by_name(): void
    {
        Category::factory()->create(['name' => '百合']);
        Category::factory()->create(['name' => '玫瑰']);

        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals('百合', $data[0]['name']);
        $this->assertEquals('玫瑰', $data[1]['name']);
    }

    public function test_store_creates_category(): void
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'name' => '玫瑰',
            'slug' => 'roses',
            'icon' => '🌹',
            'description' => '各种玫瑰花束',
        ];

        $response = $this->postJson('/api/categories', $payload);

        $response->assertCreated()
            ->assertJson(['success' => true]);
        $this->assertDatabaseHas('categories', ['slug' => 'roses']);
    }

    public function test_store_requires_name_and_slug(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/categories', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug', 'icon', 'description']);
    }

    public function test_store_requires_unique_slug(): void
    {
        Sanctum::actingAs($this->admin);
        Category::factory()->create(['slug' => 'roses']);

        $response = $this->postJson('/api/categories', [
            'name' => 'Rose Too',
            'slug' => 'roses',
            'icon' => '🌹',
            'description' => 'Desc',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_show_returns_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $category->id]]);
    }

    public function test_show_returns_404_for_missing(): void
    {
        $response = $this->getJson('/api/categories/99999');

        $response->assertNotFound();
    }

    public function test_update_modifies_category(): void
    {
        Sanctum::actingAs($this->admin);
        $category = Category::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/categories/{$category->id}", ['name' => 'New Name']);

        $response->assertOk()
            ->assertJson(['success' => true]);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New Name']);
    }

    public function test_update_allows_same_slug_for_same_category(): void
    {
        Sanctum::actingAs($this->admin);
        $category = Category::factory()->create(['slug' => 'roses']);

        $response = $this->putJson("/api/categories/{$category->id}", ['slug' => 'roses']);

        $response->assertOk();
    }

    public function test_destroy_deletes_category(): void
    {
        Sanctum::actingAs($this->admin);
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => '删除成功']);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
