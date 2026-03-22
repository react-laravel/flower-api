<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_category(): void
    {
        $category = Category::create([
            'name' => '玫瑰',
            'slug' => 'rose',
            'icon' => '🌹',
            'description' => '玫瑰花束',
            'user_id' => null,
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => '玫瑰',
            'slug' => 'rose',
        ]);
        $this->assertEquals('🌹', $category->icon);
    }

    public function test_name_is_fillable(): void
    {
        $category = Category::create(['name' => '百合', 'slug' => 'lily', 'user_id' => null]);
        $this->assertEquals('百合', $category->name);
    }

    public function test_slug_is_fillable(): void
    {
        $category = Category::create(['name' => '康乃馨', 'slug' => 'carnation', 'user_id' => null]);
        $this->assertEquals('carnation', $category->slug);
    }

    public function test_icon_is_fillable(): void
    {
        $category = Category::create(['name' => '郁金香', 'slug' => 'tulip', 'icon' => '🌷', 'user_id' => null]);
        $this->assertEquals('🌷', $category->icon);
    }

    public function test_description_is_fillable(): void
    {
        $category = Category::create(['name' => '向日葵', 'slug' => 'sunflower', 'description' => '阳光之花', 'user_id' => null]);
        $this->assertEquals('阳光之花', $category->description);
    }

    public function test_user_id_is_fillable(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => '测试', 'slug' => 'test', 'user_id' => $user->id]);
        $this->assertEquals($user->id, $category->user_id);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => '测试', 'slug' => 'test', 'user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $category->user);
        $this->assertEquals($user->id, $category->user->id);
    }

    public function test_user_relation_returns_null_when_no_user(): void
    {
        $category = Category::create(['name' => '测试', 'slug' => 'test', 'user_id' => null]);
        $this->assertNull($category->user);
    }
}
