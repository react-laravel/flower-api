<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Flower;
use App\Models\Knowledge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->regularUser = User::factory()->create(['is_admin' => false]);
    }

    // ─── Flower CRUD ────────────────────────────────────────────────────────

    public function test_admin_can_create_flower(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/flowers', [
                'name' => '红玫瑰',
                'name_en' => 'Red Rose',
                'category' => 'rose',
                'price' => 199,
                'original_price' => 299,
                'image' => 'http://example.com/rose.jpg',
                'description' => 'A beautiful red rose',
                'meaning' => 'Love',
                'care' => 'Keep in water',
                'stock' => 100,
                'featured' => true,
                'holiday' => 'valentine',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => '红玫瑰',
                    'category' => 'rose',
                    'featured' => true,
                ],
            ]);

        $this->assertDatabaseHas('flowers', ['name' => '红玫瑰', 'category' => 'rose']);
    }

    public function test_admin_can_update_flower(): void
    {
        $flower = Flower::create([
            'name' => 'Original Flower',
            'name_en' => 'Original Flower EN',
            'category' => 'rose',
            'price' => 100,
            'image' => 'http://example.com/flower.jpg',
            'description' => 'Original description',
            'meaning' => 'Original meaning',
            'care' => 'Original care',
            'stock' => 10,
            'featured' => false,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/flowers/{$flower->id}", [
                'name' => 'Updated Flower',
                'name_en' => 'Updated Flower EN',
                'category' => 'lily',
                'price' => 200,
                'image' => 'http://example.com/updated.jpg',
                'description' => 'Updated description',
                'meaning' => 'Updated meaning',
                'care' => 'Updated care',
                'stock' => 20,
                'featured' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated Flower',
                    'category' => 'lily',
                    'price' => '200.00',
                    'featured' => true,
                ],
            ]);

        $this->assertDatabaseHas('flowers', ['name' => 'Updated Flower', 'category' => 'lily']);
    }

    public function test_admin_can_delete_flower(): void
    {
        $flower = Flower::create([
            'name' => 'To Be Deleted',
            'name_en' => 'To Be Deleted EN',
            'category' => 'rose',
            'price' => 100,
            'image' => 'http://example.com/flower.jpg',
            'description' => 'Will be deleted',
            'meaning' => 'Delete me',
            'care' => 'Delete',
            'stock' => 10,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/flowers/{$flower->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => '删除成功']);

        $this->assertDatabaseMissing('flowers', ['id' => $flower->id]);
    }

    public function test_admin_update_nonexistent_flower_returns_404(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/flowers/99999', [
                'name' => 'Updated Flower',
                'name_en' => 'Updated Flower EN',
                'category' => 'rose',
                'price' => 200,
                'image' => 'http://example.com/updated.jpg',
                'description' => 'Updated description',
                'meaning' => 'Updated meaning',
                'care' => 'Updated care',
                'stock' => 20,
            ]);

        $response->assertStatus(404);
    }

    public function test_admin_delete_nonexistent_flower_returns_404(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/flowers/99999');

        $response->assertStatus(404);
    }

    public function test_create_flower_with_missing_fields_returns_validation_error(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/flowers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name', 'name_en', 'category', 'price', 'image',
                'description', 'meaning', 'care', 'stock',
            ]);
    }

    public function test_create_flower_with_invalid_price_returns_validation_error(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/flowers', [
                'name' => 'Test Flower',
                'name_en' => 'Test Flower EN',
                'category' => 'rose',
                'price' => -10,
                'image' => 'http://example.com/flower.jpg',
                'description' => 'A test flower',
                'meaning' => 'Love',
                'care' => 'Water daily',
                'stock' => 10,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    // ─── Category CRUD ───────────────────────────────────────────────────────

    public function test_admin_can_create_category(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/categories', [
                'name' => '郁金香',
                'slug' => 'tulip',
                'icon' => '🌷',
                'description' => '高贵典雅的花朵',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => '郁金香',
                    'slug' => 'tulip',
                ],
            ]);

        $this->assertDatabaseHas('categories', ['slug' => 'tulip']);
    }

    public function test_admin_can_update_category(): void
    {
        $category = Category::create([
            'name' => 'Original Category',
            'slug' => 'original-category',
            'icon' => '🌺',
            'description' => 'Original description',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/categories/{$category->id}", [
                'name' => 'Updated Category',
                'slug' => 'updated-category',
                'icon' => '🌻',
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated Category',
                    'slug' => 'updated-category',
                ],
            ]);
    }

    public function test_admin_can_delete_category(): void
    {
        $category = Category::create([
            'name' => 'To Be Deleted',
            'slug' => 'to-be-deleted',
            'icon' => '🌺',
            'description' => 'Will be deleted',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_create_category_with_duplicate_slug_returns_validation_error(): void
    {
        Category::create([
            'name' => 'Existing Category',
            'slug' => 'existing-slug',
            'icon' => '🌺',
            'description' => 'Already exists',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/categories', [
                'name' => 'New Category',
                'slug' => 'existing-slug',
                'icon' => '🌻',
                'description' => 'Trying to use existing slug',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_admin_update_nonexistent_category_returns_404(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/categories/99999', [
                'name' => 'Updated Category',
                'slug' => 'updated-category',
                'icon' => '🌻',
                'description' => 'Updated description',
            ]);

        $response->assertStatus(404);
    }

    public function test_admin_delete_nonexistent_category_returns_404(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/categories/99999');

        $response->assertStatus(404);
    }

    public function test_create_category_with_missing_fields_returns_validation_error(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/categories', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug', 'icon', 'description']);
    }

    // ─── Knowledge CRUD ──────────────────────────────────────────────────────

    public function test_admin_can_create_knowledge(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/knowledge', [
                'question' => '如何照顾多肉植物？',
                'answer' => '少浇水，多晒太阳',
                'category' => 'care',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'question' => '如何照顾多肉植物？',
                    'category' => 'care',
                ],
            ]);

        $this->assertDatabaseHas('knowledge', ['question' => '如何照顾多肉植物？']);
    }

    public function test_admin_can_update_knowledge(): void
    {
        $knowledge = Knowledge::create([
            'question' => 'Original Question?',
            'answer' => 'Original Answer',
            'category' => 'care',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/knowledge/{$knowledge->id}", [
                'question' => 'Updated Question?',
                'answer' => 'Updated Answer',
                'category' => 'meaning',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'question' => 'Updated Question?',
                    'category' => 'meaning',
                ],
            ]);
    }

    public function test_admin_can_delete_knowledge(): void
    {
        $knowledge = Knowledge::create([
            'question' => 'To Be Deleted?',
            'answer' => 'Will be deleted',
            'category' => 'care',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/knowledge/{$knowledge->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('knowledge', ['id' => $knowledge->id]);
    }

    public function test_admin_update_nonexistent_knowledge_returns_404(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/knowledge/99999', [
                'question' => 'Updated Question?',
                'answer' => 'Updated Answer',
                'category' => 'care',
            ]);

        $response->assertStatus(404);
    }

    public function test_admin_delete_nonexistent_knowledge_returns_404(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/knowledge/99999');

        $response->assertStatus(404);
    }

    public function test_create_knowledge_with_missing_fields_returns_validation_error(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/knowledge', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question', 'answer', 'category']);
    }

    // ─── Regular user should NOT be able to do admin operations ─────────────

    public function test_regular_user_cannot_update_flower(): void
    {
        $flower = Flower::create([
            'name' => 'Test Flower',
            'name_en' => 'Test Flower EN',
            'category' => 'rose',
            'price' => 100,
            'image' => 'http://example.com/flower.jpg',
            'description' => 'Test description',
            'meaning' => 'Love',
            'care' => 'Water',
            'stock' => 10,
        ]);

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->putJson("/api/flowers/{$flower->id}", [
                'name' => 'Hacked Flower',
                'name_en' => 'Hacked EN',
                'category' => 'rose',
                'price' => 1,
                'image' => 'http://example.com/hacked.jpg',
                'description' => 'Hacked',
                'meaning' => 'Hacked',
                'care' => 'Hacked',
                'stock' => 1,
            ]);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_delete_flower(): void
    {
        $flower = Flower::create([
            'name' => 'Protected Flower',
            'name_en' => 'Protected Flower EN',
            'category' => 'rose',
            'price' => 100,
            'image' => 'http://example.com/flower.jpg',
            'description' => 'Protected',
            'meaning' => 'Protected',
            'care' => 'Protected',
            'stock' => 10,
        ]);

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->deleteJson("/api/flowers/{$flower->id}");

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_update_category(): void
    {
        $category = Category::create([
            'name' => 'Protected Category',
            'slug' => 'protected-category',
            'icon' => '🌺',
            'description' => 'Protected',
        ]);

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->putJson("/api/categories/{$category->id}", [
                'name' => 'Hacked Category',
                'slug' => 'hacked-category',
                'icon' => '🌻',
                'description' => 'Hacked',
            ]);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_delete_category(): void
    {
        $category = Category::create([
            'name' => 'Protected Category',
            'slug' => 'protected-category',
            'icon' => '🌺',
            'description' => 'Protected',
        ]);

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_update_knowledge(): void
    {
        $knowledge = Knowledge::create([
            'question' => 'Protected Question?',
            'answer' => 'Protected Answer',
            'category' => 'care',
        ]);

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->putJson("/api/knowledge/{$knowledge->id}", [
                'question' => 'Hacked Question?',
                'answer' => 'Hacked Answer',
                'category' => 'care',
            ]);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_delete_knowledge(): void
    {
        $knowledge = Knowledge::create([
            'question' => 'Protected Question?',
            'answer' => 'Protected Answer',
            'category' => 'care',
        ]);

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->deleteJson("/api/knowledge/{$knowledge->id}");

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_batch_update_settings(): void
    {
        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->postJson('/api/settings/batch', [
                'settings' => ['hero_title' => 'Hacked Title'],
            ]);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_upload(): void
    {
        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->postJson('/api/upload', []);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_delete_upload(): void
    {
        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->deleteJson('/api/upload', ['path' => 'uploads/test.jpg']);

        $response->assertStatus(403);
    }
}
