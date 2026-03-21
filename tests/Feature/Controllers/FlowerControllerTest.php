<?php

namespace Tests\Feature\Controllers;

use App\Models\Flower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlowerControllerTest extends TestCase
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
    public function it_can_list_all_flowers(): void
    {
        Flower::create(['name' => '红玫瑰', 'category' => '玫瑰', 'price' => 99]);
        Flower::create(['name' => '向日葵', 'category' => '向日葵', 'price' => 59]);

        $response = $this->getJson('/api/flowers');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * @test
     */
    public function it_can_filter_flowers_by_category(): void
    {
        Flower::create(['name' => '红玫瑰', 'category' => '玫瑰', 'price' => 99]);
        Flower::create(['name' => '白玫瑰', 'category' => '玫瑰', 'price' => 89]);
        Flower::create(['name' => '向日葵', 'category' => '向日葵', 'price' => 59]);

        $response = $this->getJson('/api/flowers?category=玫瑰');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * @test
     */
    public function it_can_filter_flowers_by_featured(): void
    {
        Flower::create(['name' => '红玫瑰', 'category' => '玫瑰', 'price' => 99, 'featured' => true]);
        Flower::create(['name' => '白玫瑰', 'category' => '玫瑰', 'price' => 89, 'featured' => false]);

        $response = $this->getJson('/api/flowers?featured=true');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('红玫瑰', $response->json('data.0.name'));
    }

    /**
     * @test
     */
    public function it_can_search_flowers_by_name(): void
    {
        Flower::create(['name' => '红玫瑰', 'name_en' => 'Red Rose', 'category' => '玫瑰', 'price' => 99]);
        Flower::create(['name' => '白玫瑰', 'name_en' => 'White Rose', 'category' => '玫瑰', 'price' => 89]);

        $response = $this->getJson('/api/flowers?search=玫瑰');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * @test
     */
    public function it_can_create_a_flower(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/flowers', [
                'name' => '郁金香',
                'name_en' => 'Tulip',
                'category' => '球根',
                'price' => 79.99,
                'original_price' => 99.99,
                'image' => 'images/tulip.jpg',
                'description' => '美丽的郁金香',
                'meaning' => '爱的告白',
                'care' => '保持通风',
                'stock' => 50,
                'featured' => true,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('flowers', ['name' => '郁金香']);
    }

    /**
     * @test
     */
    public function it_validates_required_fields_on_create_flower(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/flowers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'category', 'price']);
    }

    /**
     * @test
     */
    public function it_can_show_a_flower(): void
    {
        $flower = Flower::create([
            'name' => '红玫瑰',
            'category' => '玫瑰',
            'price' => 99,
        ]);

        $response = $this->getJson("/api/flowers/{$flower->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['name' => '红玫瑰'],
            ]);
    }

    /**
     * @test
     */
    public function it_returns_404_for_nonexistent_flower(): void
    {
        $response = $this->getJson('/api/flowers/99999');

        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function it_can_update_a_flower(): void
    {
        $flower = Flower::create([
            'name' => '红玫瑰',
            'category' => '玫瑰',
            'price' => 99,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/flowers/{$flower->id}", [
                'name' => '深红玫瑰',
                'category' => '玫瑰',
                'price' => 129,
            ]);

        $response->assertStatus(200);
        $this->assertEquals('深红玫瑰', $flower->fresh()->name);
        $this->assertEquals(129, $flower->fresh()->price);
    }

    /**
     * @test
     */
    public function it_can_delete_a_flower(): void
    {
        $flower = Flower::create([
            'name' => '红玫瑰',
            'category' => '玫瑰',
            'price' => 99,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/flowers/{$flower->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => '删除成功']);

        $this->assertNull(Flower::find($flower->id));
    }

    /**
     * @test
     */
    public function it_orders_flowers_by_created_at_desc(): void
    {
        $oldFlower = Flower::create(['name' => '老花', 'category' => '玫瑰', 'price' => 10]);
        sleep(1);
        $newFlower = Flower::create(['name' => '新花', 'category' => '玫瑰', 'price' => 10]);

        $response = $this->getJson('/api/flowers');

        $response->assertStatus(200);
        $this->assertEquals('新花', $response->json('data.0.name'));
    }
}
