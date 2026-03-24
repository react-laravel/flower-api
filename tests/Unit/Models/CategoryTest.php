<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_create_category(): void
    {
        // Arrange
        $data = [
            'name' => '玫瑰',
            'slug' => 'rose',
            'icon' => '🌹',
            'description' => '玫瑰花束',
            'user_id' => null,
        ];

        // Act
        $category = Category::create($data);

        // Assert
        $this->assertDatabaseHas('categories', [
            'name' => '玫瑰',
            'slug' => 'rose',
        ]);
        $this->assertEquals('🌹', $category->icon);
    }

    #[Test]
    public function name_is_fillable(): void
    {
        // Arrange & Act
        $category = Category::create(['name' => '百合', 'slug' => 'lily', 'user_id' => null]);

        // Assert
        $this->assertEquals('百合', $category->name);
    }

    #[Test]
    public function slug_is_fillable(): void
    {
        // Arrange & Act
        $category = Category::create(['name' => '康乃馨', 'slug' => 'carnation', 'user_id' => null]);

        // Assert
        $this->assertEquals('carnation', $category->slug);
    }

    #[Test]
    public function icon_is_fillable(): void
    {
        // Arrange & Act
        $category = Category::create(['name' => '郁金香', 'slug' => 'tulip', 'icon' => '🌷', 'user_id' => null]);

        // Assert
        $this->assertEquals('🌷', $category->icon);
    }

    #[Test]
    public function description_is_fillable(): void
    {
        // Arrange & Act
        $category = Category::create([
            'name' => '向日葵',
            'slug' => 'sunflower',
            'description' => '阳光之花',
            'user_id' => null,
        ]);

        // Assert
        $this->assertEquals('阳光之花', $category->description);
    }

    #[Test]
    public function user_id_is_fillable(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $category = Category::create(['name' => '测试', 'slug' => 'test', 'user_id' => $user->id]);

        // Assert
        $this->assertEquals($user->id, $category->user_id);
    }

    #[Test]
    public function belongs_to_user(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $category = Category::create(['name' => '测试', 'slug' => 'test', 'user_id' => $user->id]);

        // Assert
        $this->assertInstanceOf(User::class, $category->user);
        $this->assertEquals($user->id, $category->user->id);
    }

    #[Test]
    public function user_relation_returns_null_when_no_user(): void
    {
        // Arrange & Act
        $category = Category::create(['name' => '测试', 'slug' => 'test', 'user_id' => null]);

        // Assert
        $this->assertNull($category->user);
    }
}
