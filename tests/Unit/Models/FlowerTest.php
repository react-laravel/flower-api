<?php

namespace Tests\Unit\Models;

use App\Models\Flower;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlowerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_a_flower(): void
    {
        $flower = Flower::create([
            'name' => '红玫瑰',
            'name_en' => 'Red Rose',
            'category' => '玫瑰',
            'price' => 99.99,
            'original_price' => 129.99,
            'image' => 'images/rose.jpg',
            'description' => '美丽的红玫瑰',
            'meaning' => '爱情',
            'care' => '避免阳光直射',
            'stock' => 100,
            'featured' => true,
            'holiday' => '情人节',
        ]);

        $this->assertDatabaseHas('flowers', [
            'name' => '红玫瑰',
            'name_en' => 'Red Rose',
        ]);

        $this->assertEquals('红玫瑰', $flower->name);
        $this->assertEquals('Red Rose', $flower->name_en);
        $this->assertEquals('玫瑰', $flower->category);
        $this->assertEquals(99.99, $flower->price);
        $this->assertEquals(129.99, $flower->original_price);
        $this->assertEquals('images/rose.jpg', $flower->image);
        $this->assertEquals('爱情', $flower->meaning);
        $this->assertEquals('避免阳光直射', $flower->care);
        $this->assertEquals(100, $flower->stock);
        $this->assertTrue($flower->featured);
    }

    /**
     * @test
     */
    public function it_casts_price_to_decimal(): void
    {
        $flower = Flower::create([
            'name' => '百合',
            'category' => '百合',
            'price' => '88.50',
            'original_price' => '120.00',
        ]);

        $this->assertIsString($flower->price);
        $this->assertEquals('88.50', $flower->price);
    }

    /**
     * @test
     */
    public function it_casts_featured_to_boolean(): void
    {
        $flower = Flower::create([
            'name' => '郁金香',
            'category' => '球根',
            'price' => 66,
            'featured' => 1,
        ]);

        $this->assertTrue($flower->featured);

        $flower->update(['featured' => false]);
        $this->assertFalse($flower->fresh()->featured);
    }

    /**
     * @test
     */
    public function it_has_fillable_attributes(): void
    {
        $fillable = [
            'name', 'name_en', 'category', 'price', 'original_price',
            'image', 'description', 'meaning', 'care', 'stock',
            'featured', 'holiday',
        ];

        $this->assertEquals($fillable, (new Flower)->getFillable());
    }

    /**
     * @test
     */
    public function it_can_filter_by_category(): void
    {
        Flower::create(['name' => '红玫瑰', 'category' => '玫瑰', 'price' => 10]);
        Flower::create(['name' => '白玫瑰', 'category' => '玫瑰', 'price' => 12]);
        Flower::create(['name' => '向日葵', 'category' => '向日葵', 'price' => 15]);

        $roses = Flower::where('category', '玫瑰')->get();

        $this->assertCount(2, $roses);
    }

    /**
     * @test
     */
    public function it_can_filter_featured_flowers(): void
    {
        Flower::create(['name' => '红玫瑰', 'category' => '玫瑰', 'price' => 10, 'featured' => true]);
        Flower::create(['name' => '白玫瑰', 'category' => '玫瑰', 'price' => 12, 'featured' => false]);

        $featured = Flower::where('featured', true)->get();

        $this->assertCount(1, $featured);
        $this->assertEquals('红玫瑰', $featured->first()->name);
    }

    /**
     * @test
     */
    public function it_can_search_by_name(): void
    {
        Flower::create(['name' => '红玫瑰', 'name_en' => 'Red Rose', 'category' => '玫瑰', 'price' => 10]);
        Flower::create(['name' => '白玫瑰', 'name_en' => 'White Rose', 'category' => '玫瑰', 'price' => 12]);
        Flower::create(['name' => '向日葵', 'name_en' => 'Sunflower', 'category' => '向日葵', 'price' => 15]);

        $results = Flower::where('name', 'like', '%玫瑰%')
            ->orWhere('name_en', 'like', '%玫瑰%')
            ->get();

        $this->assertCount(2, $results);
    }

    /**
     * @test
     */
    public function it_can_update_flower(): void
    {
        $flower = Flower::create([
            'name' => '红玫瑰',
            'category' => '玫瑰',
            'price' => 99,
        ]);

        $flower->update(['price' => 149, 'featured' => true]);

        $this->assertEquals(149, $flower->fresh()->price);
        $this->assertTrue($flower->fresh()->featured);
    }

    /**
     * @test
     */
    public function it_can_delete_flower(): void
    {
        $flower = Flower::create([
            'name' => '红玫瑰',
            'category' => '玫瑰',
            'price' => 99,
        ]);

        $id = $flower->id;
        $flower->delete();

        $this->assertNull(Flower::find($id));
    }
}
