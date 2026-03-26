<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Flower;
use App\Models\Knowledge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestData();
    }

    protected function seedTestData(): void
    {
        // Create categories
        $this->categoryRose = Category::create([
            'name' => '玫瑰',
            'slug' => 'rose',
            'icon' => '🌹',
            'description' => '爱情之花',
        ]);
        $this->categoryLily = Category::create([
            'name' => '百合',
            'slug' => 'lily',
            'icon' => '🌸',
            'description' => '纯洁高雅',
        ]);

        // Create flowers
        $this->flowerRedRose = Flower::create([
            'name' => '红玫瑰花束',
            'name_en' => 'Red Rose Bouquet',
            'category' => 'rose',
            'price' => 299,
            'original_price' => 399,
            'image' => 'https://example.com/red-rose.jpg',
            'description' => '11朵精选红玫瑰',
            'meaning' => '热恋、我爱你',
            'care' => '保持水温18-20°C',
            'stock' => 50,
            'featured' => true,
        ]);
        $this->flowerPinkLily = Flower::create([
            'name' => '粉百合束',
            'name_en' => 'Pink Lily Bouquet',
            'category' => 'lily',
            'price' => 358,
            'image' => 'https://example.com/pink-lily.jpg',
            'description' => '9朵粉百合花束',
            'meaning' => '百年好合',
            'care' => '保持水温15-18°C',
            'stock' => 30,
            'featured' => true,
        ]);
        $this->flowerSunflower = Flower::create([
            'name' => '向日葵花束',
            'name_en' => 'Sunflower Bouquet',
            'category' => 'sunflower',
            'price' => 228,
            'image' => 'https://example.com/sunflower.jpg',
            'description' => '5朵向日葵',
            'meaning' => '阳光、积极',
            'care' => '保持充足水分',
            'stock' => 40,
            'featured' => false,
        ]);

        // Create knowledge items
        $this->knowledgeFresh = Knowledge::create([
            'question' => '鲜花如何保鲜？',
            'answer' => '每天换水，保持水质清洁',
            'category' => 'care',
        ]);
        $this->knowledgeRose = Knowledge::create([
            'question' => '玫瑰的花语是什么？',
            'answer' => '红玫瑰代表热恋和永恒的爱',
            'category' => 'meaning',
        ]);
    }

    // ─── Flowers ──────────────────────────────────────────────────────────────

    public function test_flowers_index_returns_all_flowers(): void
    {
        $response = $this->getJson('/api/flowers');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    public function test_flowers_index_filters_by_category(): void
    {
        $response = $this->getJson('/api/flowers?category=rose');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data');
    }

    public function test_flowers_index_filters_by_category_all(): void
    {
        $response = $this->getJson('/api/flowers?category=all');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    public function test_flowers_index_filters_by_featured(): void
    {
        $response = $this->getJson('/api/flowers?featured=true');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');
    }

    public function test_flowers_index_filters_by_search(): void
    {
        $response = $this->getJson('/api/flowers?search=玫瑰');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data');
    }

    public function test_flowers_index_search_in_name_en(): void
    {
        $response = $this->getJson('/api/flowers?search=Rose');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data');
    }

    public function test_flowers_show_returns_flower_data(): void
    {
        $response = $this->getJson("/api/flowers/{$this->flowerRedRose->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => '红玫瑰花束',
                    'category' => 'rose',
                    'price' => '299.00',
                    'featured' => true,
                ],
            ]);
    }

    public function test_flowers_show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson('/api/flowers/99999');

        $response->assertStatus(404);
    }

    // ─── Categories ───────────────────────────────────────────────────────────

    public function test_categories_index_returns_all_categories(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');
    }

    public function test_categories_index_ordered_by_name(): void
    {
        // Create categories in reverse alphabetical order to verify orderBy works
        Category::create([
            'name' => 'ZZZ Category',
            'slug' => 'zzz-category',
            'icon' => '🌿',
            'description' => 'Last alphabetically',
        ]);
        Category::create([
            'name' => 'AAA Category',
            'slug' => 'aaa-category',
            'icon' => '🌸',
            'description' => 'First alphabetically',
        ]);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200);
        $data = $response->json('data');
        // Should be ordered by name ASC
        $names = array_column($data, 'name');
        $sortedNames = $names;
        sort($sortedNames);
        $this->assertEquals($sortedNames, $names, 'Categories should be ordered by name ASC');
    }

    public function test_categories_show_returns_category_data(): void
    {
        $response = $this->getJson("/api/categories/{$this->categoryRose->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => '玫瑰',
                    'slug' => 'rose',
                ],
            ]);
    }

    public function test_categories_show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson('/api/categories/99999');

        $response->assertStatus(404);
    }

    // ─── Knowledge ─────────────────────────────────────────────────────────────

    public function test_knowledge_index_returns_all_knowledge_items(): void
    {
        $response = $this->getJson('/api/knowledge');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');
    }

    public function test_knowledge_show_returns_knowledge_data(): void
    {
        $response = $this->getJson("/api/knowledge/{$this->knowledgeFresh->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'question' => '鲜花如何保鲜？',
                    'category' => 'care',
                ],
            ]);
    }

    public function test_knowledge_show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson('/api/knowledge/99999');

        $response->assertStatus(404);
    }

    // ─── Settings ──────────────────────────────────────────────────────────────

    public function test_settings_index_returns_all_settings(): void
    {
        $response = $this->getJson('/api/settings');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        // No settings seeded by default, returns empty collection
        $this->assertIsArray($response->json('data'));
    }
}
