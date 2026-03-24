<?php

namespace Tests\Unit\Models;

use App\Models\Flower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FlowerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_create_flower(): void
    {
        // Arrange
        $data = [
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
        ];

        // Act
        $flower = Flower::create($data);

        // Assert
        $this->assertDatabaseHas('flowers', [
            'name' => '红玫瑰',
            'name_en' => 'Red Rose',
            'category' => 'rose',
        ]);
    }

    #[Test]
    public function price_is_cast_to_decimal(): void
    {
        // Arrange & Act
        $flower = Flower::create([
            'name' => '测试',
            'name_en' => 'Test',
            'price' => '88.50',
            'original_price' => '120.00',
            'category' => 'test',
            'user_id' => null,
        ]);

        // Assert
        $this->assertEquals('88.50', $flower->price);
        $this->assertEquals('120.00', $flower->original_price);
    }

    #[Test]
    public function featured_is_cast_to_boolean(): void
    {
        // Arrange & Act
        $flower = Flower::create([
            'name' => '测试',
            'name_en' => 'Test',
            'featured' => true,
            'category' => 'test',
            'price' => 10,
            'user_id' => null,
        ]);
        $flower2 = Flower::create([
            'name' => '测试2',
            'name_en' => 'Test2',
            'featured' => false,
            'category' => 'test',
            'price' => 10,
            'user_id' => null,
        ]);

        // Assert
        $this->assertTrue((bool) $flower->featured);
        $this->assertFalse((bool) $flower2->featured);
    }

    #[Test]
    public function name_is_fillable(): void
    {
        // Arrange & Act
        $flower = Flower::create([
            'name' => '白玫瑰',
            'name_en' => 'White Rose',
            'category' => 'rose',
            'price' => 50,
            'user_id' => null,
        ]);

        // Assert
        $this->assertEquals('白玫瑰', $flower->name);
    }

    #[Test]
    public function name_en_is_fillable(): void
    {
        // Arrange & Act
        $flower = Flower::create([
            'name' => '测试',
            'name_en' => 'Test Flower',
            'category' => 'test',
            'price' => 10,
            'user_id' => null,
        ]);

        // Assert
        $this->assertEquals('Test Flower', $flower->name_en);
    }

    #[Test]
    public function category_is_fillable(): void
    {
        // Arrange & Act
        $flower = Flower::create([
            'name' => '测试',
            'name_en' => 'Test',
            'category' => 'tulip',
            'price' => 10,
            'user_id' => null,
        ]);

        // Assert
        $this->assertEquals('tulip', $flower->category);
    }

    #[Test]
    public function stock_is_fillable(): void
    {
        // Arrange & Act
        $flower = Flower::create([
            'name' => '测试',
            'name_en' => 'Test',
            'stock' => 50,
            'category' => 'test',
            'price' => 10,
            'user_id' => null,
        ]);

        // Assert
        $this->assertEquals(50, $flower->stock);
    }

    #[Test]
    public function meaning_is_fillable(): void
    {
        // Arrange & Act
        $flower = Flower::create([
            'name' => '测试',
            'name_en' => 'Test',
            'meaning' => '友谊',
            'category' => 'test',
            'price' => 10,
            'user_id' => null,
        ]);

        // Assert
        $this->assertEquals('友谊', $flower->meaning);
    }

    #[Test]
    public function care_is_fillable(): void
    {
        // Arrange & Act
        $flower = Flower::create([
            'name' => '测试',
            'name_en' => 'Test',
            'care' => '每日换水',
            'category' => 'test',
            'price' => 10,
            'user_id' => null,
        ]);

        // Assert
        $this->assertEquals('每日换水', $flower->care);
    }

    #[Test]
    public function holiday_is_fillable(): void
    {
        // Arrange & Act
        $flower = Flower::create([
            'name' => '测试',
            'name_en' => 'Test',
            'holiday' => '母亲节',
            'category' => 'test',
            'price' => 10,
            'user_id' => null,
        ]);

        // Assert
        $this->assertEquals('母亲节', $flower->holiday);
    }

    #[Test]
    public function image_is_fillable(): void
    {
        // Arrange
        $imagePath = fake()->imageUrl(400, 400, 'flowers');

        // Act
        $flower = Flower::create([
            'name' => '测试',
            'name_en' => 'Test',
            'image' => $imagePath,
            'category' => 'test',
            'price' => 10,
            'user_id' => null,
        ]);

        // Assert
        $this->assertEquals($imagePath, $flower->image);
    }

    #[Test]
    public function original_price_is_fillable(): void
    {
        // Arrange
        $originalPrice = fake()->randomFloat(2, 20, 300);

        // Act
        $flower = Flower::create([
            'name' => '测试',
            'name_en' => 'Test',
            'original_price' => $originalPrice,
            'price' => 10,
            'category' => 'test',
            'user_id' => null,
        ]);

        // Assert
        $this->assertEquals($originalPrice, (float) $flower->original_price);
    }

    #[Test]
    public function belongs_to_user(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $flower = Flower::create([
            'name' => '测试',
            'name_en' => 'Test',
            'category' => 'test',
            'price' => 10,
            'user_id' => $user->id,
        ]);

        // Assert
        $this->assertInstanceOf(User::class, $flower->user);
        $this->assertEquals($user->id, $flower->user->id);
    }

    #[Test]
    public function user_relation_returns_null_when_no_user(): void
    {
        // Arrange & Act
        $flower = Flower::create([
            'name' => '测试',
            'name_en' => 'Test',
            'category' => 'test',
            'price' => 10,
            'user_id' => null,
        ]);

        // Assert
        $this->assertNull($flower->user);
    }
}
