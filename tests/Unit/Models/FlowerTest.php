<?php

namespace Tests\Unit\Models;

use App\Models\Flower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlowerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_flower(): void
    {
        $flower = Flower::create([
            'name' => '红玫瑰',
            'name_en' => 'Red Rose',
            'category' => 'rose',
            'price' => 99.00,
            'original_price' => 129.00,
            'image' => '/images/rose.jpg',
            'description' => '经典红玫瑰',
            'meaning' => '爱情',
            'care' => '避免阳光直射',
            'stock' => 100,
            'featured' => true,
            'holiday' => '情人节',
            'user_id' => null,
        ]);

        $this->assertDatabaseHas('flowers', [
            'name' => '红玫瑰',
            'name_en' => 'Red Rose',
            'category' => 'rose',
        ]);
    }

    public function test_price_is_cast_to_decimal(): void
    {
        $flower = Flower::create(['name' => '测试', 'name_en' => 'Test', 'price' => '88.50', 'original_price' => '120.00', 'category' => 'test', 'user_id' => null]);
        $this->assertEquals('88.50', $flower->price);
        $this->assertEquals('120.00', $flower->original_price);
    }

    public function test_featured_is_cast_to_boolean(): void
    {
        $flower = Flower::create(['name' => '测试', 'name_en' => 'Test', 'featured' => true, 'category' => 'test', 'price' => 10, 'user_id' => null]);
        $this->assertTrue((bool) $flower->featured);

        $flower2 = Flower::create(['name' => '测试2', 'name_en' => 'Test2', 'featured' => false, 'category' => 'test', 'price' => 10, 'user_id' => null]);
        $this->assertFalse((bool) $flower2->featured);
    }

    public function test_name_is_fillable(): void
    {
        $flower = Flower::create(['name' => '白玫瑰', 'name_en' => 'White Rose', 'category' => 'rose', 'price' => 50, 'user_id' => null]);
        $this->assertEquals('白玫瑰', $flower->name);
    }

    public function test_name_en_is_fillable(): void
    {
        $flower = Flower::create(['name' => '测试', 'name_en' => 'Test Flower', 'category' => 'test', 'price' => 10, 'user_id' => null]);
        $this->assertEquals('Test Flower', $flower->name_en);
    }

    public function test_category_is_fillable(): void
    {
        $flower = Flower::create(['name' => '测试', 'name_en' => 'Test', 'category' => 'tulip', 'price' => 10, 'user_id' => null]);
        $this->assertEquals('tulip', $flower->category);
    }

    public function test_stock_is_fillable(): void
    {
        $flower = Flower::create(['name' => '测试', 'name_en' => 'Test', 'stock' => 50, 'category' => 'test', 'price' => 10, 'user_id' => null]);
        $this->assertEquals(50, $flower->stock);
    }

    public function test_meaning_is_fillable(): void
    {
        $flower = Flower::create(['name' => '测试', 'name_en' => 'Test', 'meaning' => '友谊', 'category' => 'test', 'price' => 10, 'user_id' => null]);
        $this->assertEquals('友谊', $flower->meaning);
    }

    public function test_care_is_fillable(): void
    {
        $flower = Flower::create(['name' => '测试', 'name_en' => 'Test', 'care' => '每日换水', 'category' => 'test', 'price' => 10, 'user_id' => null]);
        $this->assertEquals('每日换水', $flower->care);
    }

    public function test_holiday_is_fillable(): void
    {
        $flower = Flower::create(['name' => '测试', 'name_en' => 'Test', 'holiday' => '母亲节', 'category' => 'test', 'price' => 10, 'user_id' => null]);
        $this->assertEquals('母亲节', $flower->holiday);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $flower = Flower::create(['name' => '测试', 'name_en' => 'Test', 'category' => 'test', 'price' => 10, 'user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $flower->user);
        $this->assertEquals($user->id, $flower->user->id);
    }

    public function test_user_relation_returns_null_when_no_user(): void
    {
        $flower = Flower::create(['name' => '测试', 'name_en' => 'Test', 'category' => 'test', 'price' => 10, 'user_id' => null]);
        $this->assertNull($flower->user);
    }
}
