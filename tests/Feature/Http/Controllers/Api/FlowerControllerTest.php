<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Flower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlowerControllerTest extends TestCase
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
    public function test_index_returns_all_flowers(): void
    {
        Flower::factory()->count(3)->create();

        $response = $this->getJson('/api/flowers');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }
    public function test_index_filters_by_category(): void
    {
        Flower::factory()->create(['category' => 'rose']);
        Flower::factory()->create(['category' => 'tulip']);

        $response = $this->getJson('/api/flowers?category=rose');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
    public function test_index_filters_by_featured(): void
    {
        Flower::factory()->create(['featured' => true]);
        Flower::factory()->create(['featured' => false]);

        $response = $this->getJson('/api/flowers?featured=true');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
    public function test_index_searches_by_name(): void
    {
        Flower::factory()->create(['name' => 'Red Rose']);
        Flower::factory()->create(['name' => 'White Lily']);

        $response = $this->getJson('/api/flowers?search=Rose');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
    public function test_show_returns_flower_by_id(): void
    {
        $flower = Flower::factory()->create();

        $response = $this->getJson("/api/flowers/{$flower->id}");

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $flower->id]]);
    }
    public function test_show_returns_404_for_missing_flower(): void
    {
        $response = $this->getJson('/api/flowers/99999');

        $response->assertNotFound();
    }
    public function test_store_creates_flower_as_admin(): void
    {
        $auth = $this->actingAsAdmin();

        $data = [
            'name' => 'Red Rose',
            'name_en' => 'Red Rose',
            'category' => 'rose',
            'price' => 99.99,
            'image' => 'rose.jpg',
            'description' => 'A beautiful red rose',
            'meaning' => 'Love',
            'care' => 'Keep in water',
            'stock' => 10,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/flowers', $data);

        $response->assertCreated()
            ->assertJson(['success' => true, 'data' => ['name' => 'Red Rose']]);

        $this->assertDatabaseHas('flowers', ['name' => 'Red Rose']);
    }
    public function test_store_validates_required_fields(): void
    {
        $auth = $this->actingAsAdmin();

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/flowers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name', 'name_en', 'category', 'price', 'image',
                'description', 'meaning', 'care', 'stock',
            ]);
    }
    public function test_update_modifies_flower_as_admin(): void
    {
        $auth = $this->actingAsAdmin();
        $flower = Flower::factory()->create(['name' => 'Old Name']);

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->putJson("/api/flowers/{$flower->id}", [
                'name' => 'New Name',
                'name_en' => 'New Name',
                'category' => 'rose',
                'price' => 88.00,
                'image' => 'new.jpg',
                'description' => 'Updated desc',
                'meaning' => 'New meaning',
                'care' => 'New care',
                'stock' => 5,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['name' => 'New Name']]);
    }
    public function test_destroy_removes_flower_as_admin(): void
    {
        $auth = $this->actingAsAdmin();
        $flower = Flower::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->deleteJson("/api/flowers/{$flower->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('flowers', ['id' => $flower->id]);
    }
    public function test_store_rejects_non_admin(): void
    {
        $auth = $this->actingAsUser();

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/flowers', [
                'name' => 'Test',
                'name_en' => 'Test',
                'category' => 'test',
                'price' => 10,
                'image' => 'test.jpg',
                'description' => 'Test',
                'meaning' => 'Test',
                'care' => 'Test',
                'stock' => 1,
            ]);

        $response->assertForbidden();
    }
}
