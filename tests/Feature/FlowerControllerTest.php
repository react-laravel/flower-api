<?php

namespace Tests\Feature;

use App\Models\Flower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FlowerControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
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
        Flower::factory()->create(['category' => '玫瑰']);
        Flower::factory()->create(['category' => '百合']);

        $response = $this->getJson('/api/flowers?category=玫瑰');

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
        Flower::factory()->create(['name' => '红玫瑰']);
        Flower::factory()->create(['name' => '白玫瑰']);

        $response = $this->getJson('/api/flowers?search=红');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_store_creates_flower(): void
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'name' => '郁金香',
            'name_en' => 'Tulip',
            'category' => '球根',
            'price' => 89.00,
            'image' => '/images/tulip.jpg',
            'description' => '优雅的郁金香',
            'meaning' => '爱的表白',
            'care' => '避免高温',
            'stock' => 50,
        ];

        $response = $this->postJson('/api/flowers', $payload);

        $response->assertCreated()
            ->assertJson(['success' => true]);
        $this->assertDatabaseHas('flowers', ['name' => '郁金香']);
    }

    public function test_store_requires_authentication(): void
    {
        $payload = ['name' => 'Test'];

        $response = $this->postJson('/api/flowers', $payload);

        $response->assertUnauthorized();
    }

    public function test_store_validates_required_fields(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/flowers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'name_en', 'category', 'price', 'image', 'description', 'meaning', 'care', 'stock']);
    }

    public function test_show_returns_flower(): void
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

    public function test_update_modifies_flower(): void
    {
        Sanctum::actingAs($this->admin);
        $flower = Flower::factory()->create();

        $response = $this->putJson("/api/flowers/{$flower->id}", ['name' => '更新后的名称']);

        $response->assertOk()
            ->assertJson(['success' => true]);
        $this->assertDatabaseHas('flowers', ['id' => $flower->id, 'name' => '更新后的名称']);
    }

    public function test_update_validates_price_is_numeric(): void
    {
        Sanctum::actingAs($this->admin);
        $flower = Flower::factory()->create();

        $response = $this->putJson("/api/flowers/{$flower->id}", ['price' => 'not-a-number']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_destroy_deletes_flower(): void
    {
        Sanctum::actingAs($this->admin);
        $flower = Flower::factory()->create();

        $response = $this->deleteJson("/api/flowers/{$flower->id}");

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => '删除成功']);
        $this->assertDatabaseMissing('flowers', ['id' => $flower->id]);
    }
}
