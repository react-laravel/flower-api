<?php

namespace Tests\Unit;

use App\Models\Flower;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlowerTest extends TestCase
{
    use RefreshDatabase;

    // ─── Mass assignment ────────────────────────────────────────────────────

    public function test_can_create_flower_with_all_fields(): void
    {
        $flower = Flower::create([
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
            'holiday' => 'valentine',
        ]);

        $this->assertDatabaseHas('flowers', ['name' => '红玫瑰花束', 'category' => 'rose']);
        $this->assertEquals('299.00', $flower->price);
        $this->assertEquals('399.00', $flower->original_price);
        $this->assertTrue($flower->featured);
    }

    public function test_can_create_flower_without_optional_fields(): void
    {
        $flower = Flower::create([
            'name' => 'Basic Bouquet',
            'name_en' => 'Basic Bouquet EN',
            'category' => 'mixed',
            'price' => 100,
            'image' => 'https://example.com/basic.jpg',
            'description' => 'A basic bouquet',
            'meaning' => 'Love',
            'care' => 'Water daily',
            'stock' => 10,
        ]);

        $this->assertDatabaseHas('flowers', ['name' => 'Basic Bouquet']);
        $this->assertFalse($flower->featured);
        $this->assertNull($flower->holiday);
    }

    // ─── Type casting ────────────────────────────────────────────────────────

    public function test_price_is_cast_to_decimal(): void
    {
        $flower = Flower::create([
            'name' => 'Test Flower',
            'name_en' => 'Test EN',
            'category' => 'rose',
            'price' => 123,
            'image' => 'https://example.com/test.jpg',
            'description' => 'Test',
            'meaning' => 'Test',
            'care' => 'Test',
            'stock' => 5,
        ]);

        $this->assertIsString($flower->price);
        $this->assertEquals('123.00', $flower->price);
    }

    public function test_featured_is_cast_to_boolean(): void
    {
        $featured = Flower::create([
            'name' => 'Featured Flower',
            'name_en' => 'Featured EN',
            'category' => 'rose',
            'price' => 100,
            'image' => 'https://example.com/featured.jpg',
            'description' => 'Test',
            'meaning' => 'Test',
            'care' => 'Test',
            'stock' => 5,
            'featured' => 1,
        ]);

        $this->assertTrue($featured->featured);

        $nonFeatured = Flower::create([
            'name' => 'Non-Featured Flower',
            'name_en' => 'Non-Featured EN',
            'category' => 'rose',
            'price' => 100,
            'image' => 'https://example.com/nonfeatured.jpg',
            'description' => 'Test',
            'meaning' => 'Test',
            'care' => 'Test',
            'stock' => 5,
            'featured' => 0,
        ]);

        $this->assertFalse($nonFeatured->featured);
    }

    // ─── Fillable protection ─────────────────────────────────────────────────

    public function test_non_fillable_fields_are_ignored(): void
    {
        $flower = Flower::create([
            'name' => 'Test Flower',
            'name_en' => 'Test EN',
            'category' => 'rose',
            'price' => 100,
            'image' => 'https://example.com/test.jpg',
            'description' => 'Test',
            'meaning' => 'Test',
            'care' => 'Test',
            'stock' => 5,
            'unknown_field' => 'should be ignored',
        ]);

        $this->assertArrayNotHasKey('unknown_field', $flower->getAttributes());
    }

    // ─── CRUD ───────────────────────────────────────────────────────────────

    public function test_can_update_flower(): void
    {
        $flower = Flower::create([
            'name' => 'Original',
            'name_en' => 'Original EN',
            'category' => 'rose',
            'price' => 100,
            'image' => 'https://example.com/original.jpg',
            'description' => 'Original',
            'meaning' => 'Original',
            'care' => 'Original',
            'stock' => 10,
        ]);

        $flower->update(['name' => 'Updated', 'price' => 200]);

        $this->assertDatabaseHas('flowers', ['id' => $flower->id, 'name' => 'Updated', 'price' => '200.00']);
    }

    public function test_can_delete_flower(): void
    {
        $flower = Flower::create([
            'name' => 'To Delete',
            'name_en' => 'To Delete EN',
            'category' => 'rose',
            'price' => 100,
            'image' => 'https://example.com/delete.jpg',
            'description' => 'To Delete',
            'meaning' => 'To Delete',
            'care' => 'To Delete',
            'stock' => 10,
        ]);

        $id = $flower->id;
        $flower->delete();

        $this->assertDatabaseMissing('flowers', ['id' => $id]);
    }

    // ─── Scopes / Querying ───────────────────────────────────────────────────

    public function test_can_filter_by_category(): void
    {
        Flower::create(['name' => 'Rose', 'name_en' => 'Rose EN', 'category' => 'rose', 'price' => 100, 'image' => 'https://x.com/r.jpg', 'description' => 'R', 'meaning' => 'R', 'care' => 'R', 'stock' => 5]);
        Flower::create(['name' => 'Lily', 'name_en' => 'Lily EN', 'category' => 'lily', 'price' => 100, 'image' => 'https://x.com/l.jpg', 'description' => 'L', 'meaning' => 'L', 'care' => 'L', 'stock' => 5]);

        $roses = Flower::where('category', 'rose')->get();

        $this->assertCount(1, $roses);
        $this->assertEquals('Rose', $roses->first()->name);
    }

    public function test_can_filter_featured_flowers(): void
    {
        Flower::create(['name' => 'Featured', 'name_en' => 'F EN', 'category' => 'rose', 'price' => 100, 'image' => 'https://x.com/f.jpg', 'description' => 'F', 'meaning' => 'F', 'care' => 'F', 'stock' => 5, 'featured' => true]);
        Flower::create(['name' => 'Not Featured', 'name_en' => 'NF EN', 'category' => 'rose', 'price' => 100, 'image' => 'https://x.com/nf.jpg', 'description' => 'NF', 'meaning' => 'NF', 'care' => 'NF', 'stock' => 5, 'featured' => false]);

        $featured = Flower::where('featured', true)->get();

        $this->assertCount(1, $featured);
        $this->assertEquals('Featured', $featured->first()->name);
    }

    public function test_can_search_by_name(): void
    {
        Flower::create(['name' => '红玫瑰', 'name_en' => 'Red Rose', 'category' => 'rose', 'price' => 100, 'image' => 'https://x.com/r.jpg', 'description' => 'R', 'meaning' => 'R', 'care' => 'R', 'stock' => 5]);
        Flower::create(['name' => '白玫瑰', 'name_en' => 'White Rose', 'category' => 'rose', 'price' => 100, 'image' => 'https://x.com/w.jpg', 'description' => 'W', 'meaning' => 'W', 'care' => 'W', 'stock' => 5]);

        $results = Flower::where('name', 'like', '%玫瑰%')->get();

        $this->assertCount(2, $results);
    }

    public function test_can_order_by_created_at_desc(): void
    {
        $old = Flower::create(['name' => 'Old Flower', 'name_en' => 'Old EN', 'category' => 'rose', 'price' => 100, 'image' => 'https://x.com/o.jpg', 'description' => 'O', 'meaning' => 'O', 'care' => 'O', 'stock' => 5]);
        $new = Flower::create(['name' => 'New Flower', 'name_en' => 'New EN', 'category' => 'rose', 'price' => 100, 'image' => 'https://x.com/n.jpg', 'description' => 'N', 'meaning' => 'N', 'care' => 'N', 'stock' => 5]);

        $ordered = Flower::orderBy('created_at', 'desc')->get();

        $this->assertEquals($new->id, $ordered->first()->id);
        $this->assertEquals($old->id, $ordered->last()->id);
    }
}
