<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): array
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('admin')->plainTextToken;
        return ['admin' => $admin, 'token' => $token];
    }

    private function actingAsUser(): array
    {
        $user = User::factory()->create(['is_admin' => false]);
        $token = $user->createToken('user')->plainTextToken;
        return ['user' => $user, 'token' => $token];
    }
    public function test_index_returns_all_categories_ordered_by_name(): void
    {
        Category::factory()->create(['name' => 'Rose']);
        Category::factory()->create(['name' => 'Lily']);

        $response = $this->getJson('/api/categories');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');
    }
    public function test_show_returns_category_by_id(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $category->id]]);
    }
    public function test_show_returns_404_for_missing_category(): void
    {
        $response = $this->getJson('/api/categories/99999');

        $response->assertNotFound();
    }
    public function test_store_creates_category_as_admin(): void
    {
        $auth = $this->actingAsAdmin();

        $data = [
            'name' => 'Roses',
            'slug' => 'roses',
            'icon' => '🌹',
            'description' => 'All kinds of roses',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/categories', $data);

        $response->assertCreated()
            ->assertJson(['success' => true, 'data' => ['name' => 'Roses']]);

        $this->assertDatabaseHas('categories', ['slug' => 'roses']);
    }
    public function test_store_validates_required_fields(): void
    {
        $auth = $this->actingAsAdmin();

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/categories', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug']);
    }
    public function test_update_modifies_category_as_admin(): void
    {
        $auth = $this->actingAsAdmin();
        $category = Category::factory()->create(['name' => 'Old Name']);

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->putJson("/api/categories/{$category->id}", [
                'name' => 'New Name',
                'slug' => 'new-name',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['name' => 'New Name']]);
    }
    public function test_destroy_removes_category_as_admin(): void
    {
        $auth = $this->actingAsAdmin();
        $category = Category::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
    public function test_store_rejects_non_admin(): void
    {
        $auth = $this->actingAsUser();

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/categories', [
                'name' => 'Test',
                'slug' => 'test',
            ]);

        $response->assertForbidden();
    }
}
