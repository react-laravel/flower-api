<?php

namespace Tests\Unit;

use App\Models\Flower;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlowerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_flower_can_be_created(): void
    {
        $flower = Flower::create([
            'name' => '红玫瑰',
            'name_en' => 'Red Rose',
            'category' => '玫瑰',
            'price' => 99.00,
            'original_price' => 129.00,
            'image' => '/images/red-rose.jpg',
            'description' => '美丽的红玫瑰',
            'meaning' => '爱情',
            'care' => '每天换水',
            'stock' => 100,
            'featured' => true,
            'holiday' => '情人节',
        ]);

        $this->assertDatabaseHas('flowers', ['name' => '红玫瑰']);
        $this->assertEquals('红玫瑰', $flower->name);
        $this->assertEquals('Red Rose', $flower->name_en);
        $this->assertTrue($flower->featured);
    }

    public function test_flower_price_is_cast_to_decimal(): void
    {
        $flower = Flower::create([
            'name' => '白百合',
            'name_en' => 'White Lily',
            'category' => '百合',
            'price' => 199,
            'original_price' => '299',
            'image' => '/images/white-lily.jpg',
            'description' => '纯洁的百合',
            'meaning' => '纯洁',
            'care' => '避免阳光直射',
            'stock' => 50,
            'featured' => false,
        ]);

        $this->assertIsString($flower->price);
        $this->assertEquals('199.00', $flower->price);
        $this->assertIsString($flower->original_price);
    }

    public function test_flower_featured_is_cast_to_boolean(): void
    {
        $flower = Flower::create([
            'name' => '向日葵',
            'name_en' => 'Sunflower',
            'category' => '向日葵',
            'price' => 79.00,
            'image' => '/images/sunflower.jpg',
            'description' => '阳光的象征',
            'meaning' => '忠诚',
            'care' => '保持水分',
            'stock' => 30,
            'featured' => 1,
        ]);

        $this->assertIsBool($flower->featured);
        $this->assertTrue($flower->featured);
    }

    public function test_flower_fillable_attributes(): void
    {
        $fillable = (new Flower())->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('name_en', $fillable);
        $this->assertContains('category', $fillable);
        $this->assertContains('price', $fillable);
        $this->assertContains('featured', $fillable);
        $this->assertContains('stock', $fillable);
        $this->assertContains('holiday', $fillable);
    }
}
