<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_can_list_categories(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'icon', 'description']
                ]
            ])
            ->assertJson(['success' => true]);
    }

    public function test_can_show_single_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'slug', 'icon', 'description']
            ])
            ->assertJson(['success' => true, 'data' => ['id' => $category->id]]);
    }

    public function test_show_returns_404_for_nonexistent_category(): void
    {
        $response = $this->getJson('/api/categories/99999');

        $response->assertStatus(404);
    }

    public function test_can_create_category_with_valid_data(): void
    {
        $user = \App\Models\User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test-token')->plainTextToken;

        $categoryData = [
            'name' => 'Roses',
            'slug' => 'roses',
            'icon' => 'heart',
            'description' => 'Beautiful rose flowers',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['name' => 'Roses', 'slug' => 'roses']);

        $this->assertDatabaseHas('categories', ['slug' => 'roses']);
    }

    public function test_create_category_requires_authentication(): void
    {
        $categoryData = [
            'name' => 'Roses',
            'slug' => 'roses',
            'icon' => 'heart',
            'description' => 'Beautiful rose flowers',
        ];

        $response = $this->postJson('/api/categories', $categoryData);

        $response->assertStatus(401);
    }

    public function test_can_update_category(): void
    {
        $user = \App\Models\User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test-token')->plainTextToken;
        $category = Category::factory()->create(['name' => 'Old Name']);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->putJson("/api/categories/{$category->id}", [
            'name' => 'New Name',
            'slug' => 'new-name',
            'icon' => 'star',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['name' => 'New Name']);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New Name']);
    }

    public function test_can_delete_category(): void
    {
        $user = \App\Models\User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test-token')->plainTextToken;
        $category = Category::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_list_returns_categories_ordered_by_name(): void
    {
        Category::factory()->create(['name' => 'Zebra']);
        Category::factory()->create(['name' => 'Apple']);
        Category::factory()->create(['name' => 'Moon']);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200);
        $data = $response->json('data');
        $names = array_column($data, 'name');
        $this->assertEquals(['Apple', 'Moon', 'Zebra'], $names);
    }
}
