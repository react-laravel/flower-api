<?php

namespace Tests\Feature;

use App\Models\Flower;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlowerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Run migrations for in-memory SQLite
        $this->artisan('migrate');
    }

    public function test_can_list_flowers(): void
    {
        Flower::factory()->count(3)->create();

        $response = $this->getJson('/api/flowers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'name_en',
                        'category',
                        'price',
                        'image',
                    ]
                ]
            ])
            ->assertJson(['success' => true]);
    }

    public function test_can_filter_flowers_by_category(): void
    {
        Flower::factory()->create(['category' => 'rose']);
        Flower::factory()->create(['category' => 'tulip']);

        $response = $this->getJson('/api/flowers?category=rose');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $flower) {
            $this->assertEquals('rose', $flower['category']);
        }
    }

    public function test_can_search_flowers_by_name(): void
    {
        Flower::factory()->create(['name' => 'Red Rose']);
        Flower::factory()->create(['name' => 'White Lily']);

        $response = $this->getJson('/api/flowers?search=rose');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        
        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    public function test_can_show_single_flower(): void
    {
        $flower = Flower::factory()->create();

        $response = $this->getJson("/api/flowers/{$flower->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'name_en',
                    'category',
                    'price',
                    'image',
                    'description',
                    'meaning',
                    'care',
                ]
            ])
            ->assertJson(['success' => true]);
    }

    public function test_show_returns_404_for_nonexistent_flower(): void
    {
        $response = $this->getJson('/api/flowers/99999');

        $response->assertStatus(404);
    }

    public function test_can_create_flower_with_valid_data(): void
    {
        $user = \App\Models\User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test-token')->plainTextToken;

        $flowerData = [
            'name' => 'Red Rose',
            'name_en' => 'Red Rose',
            'category' => 'rose',
            'price' => 29.99,
            'image' => 'rose.jpg',
            'description' => 'A beautiful red rose',
            'meaning' => 'Love',
            'care' => 'Water regularly',
            'stock' => 100,
            'featured' => true,
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/flowers', $flowerData);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['name' => 'Red Rose']);

        $this->assertDatabaseHas('flowers', ['name' => 'Red Rose']);
    }

    public function test_create_flower_requires_authentication(): void
    {
        $flowerData = [
            'name' => 'Red Rose',
            'name_en' => 'Red Rose',
            'category' => 'rose',
            'price' => 29.99,
            'image' => 'rose.jpg',
            'description' => 'A beautiful red rose',
            'meaning' => 'Love',
            'care' => 'Water regularly',
        ];

        $response = $this->postJson('/api/flowers', $flowerData);

        $response->assertStatus(401);
    }

    public function test_can_update_flower(): void
    {
        $user = \App\Models\User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test-token')->plainTextToken;
        $flower = Flower::factory()->create(['name' => 'Old Name']);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->putJson("/api/flowers/{$flower->id}", [
            'name' => 'New Name',
            'name_en' => 'New Name',
            'category' => 'rose',
            'price' => 39.99,
            'image' => 'new.jpg',
            'description' => 'Updated description',
            'meaning' => 'New meaning',
            'care' => 'Updated care',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['name' => 'New Name']);

        $this->assertDatabaseHas('flowers', ['id' => $flower->id, 'name' => 'New Name']);
    }

    public function test_can_delete_flower(): void
    {
        $user = \App\Models\User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test-token')->plainTextToken;
        $flower = Flower::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->deleteJson("/api/flowers/{$flower->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('flowers', ['id' => $flower->id]);
    }

    public function test_list_returns_empty_array_when_no_flowers(): void
    {
        $response = $this->getJson('/api/flowers');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'data' => []]);
    }
}
