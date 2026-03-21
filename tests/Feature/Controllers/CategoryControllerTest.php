<?php

namespace Tests\Feature\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'is_admin' => true,
        ]);
    }

    /**
     * @test
     */
    public function it_can_list_all_categories(): void
    {
        Category::create(['name' => '玫瑰', 'slug' => 'rose']);
        Category::create(['name' => '向日葵', 'slug' => 'sunflower']);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * @test
     */
    public function it_orders_categories_by_name(): void
    {
        Category::create(['name' => '向日葵', 'slug' => 'sunflower']);
        Category::create(['name' => '玫瑰', 'slug' => 'rose']);
        Category::create(['name' => '百合', 'slug' => 'lily']);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200);
        $this->assertEquals('百合', $response->json('data.0.name'));
        $this->assertEquals('玫瑰', $response->json('data.1.name'));
        $this->assertEquals('向日葵', $response->json('data.2.name'));
    }

    /**
     * @test
     */
    public function it_can_create_a_category(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/categories', [
                'name' => '玫瑰',
                'slug' => 'rose',
                'icon' => '🌹',
                'description' => '各种玫瑰花',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('categories', ['name' => '玫瑰', 'slug' => 'rose']);
    }

    /**
     * @test
     */
    public function it_validates_required_fields_on_create(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/categories', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug']);
    }

    /**
     * @test
     */
    public function it_can_show_a_category(): void
    {
        $category = Category::create([
            'name' => '玫瑰',
            'slug' => 'rose',
        ]);

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['name' => '玫瑰'],
            ]);
    }

    /**
     * @test
     */
    public function it_returns_404_for_nonexistent_category(): void
    {
        $response = $this->getJson('/api/categories/99999');

        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function it_can_update_a_category(): void
    {
        $category = Category::create([
            'name' => '玫瑰',
            'slug' => 'rose',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/categories/{$category->id}", [
                'name' => '红玫瑰',
                'slug' => 'red-rose',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('红玫瑰', $category->fresh()->name);
        $this->assertEquals('red-rose', $category->fresh()->slug);
    }

    /**
     * @test
     */
    public function it_can_delete_a_category(): void
    {
        $category = Category::create([
            'name' => '玫瑰',
            'slug' => 'rose',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => '删除成功']);

        $this->assertNull(Category::find($category->id));
    }
}
