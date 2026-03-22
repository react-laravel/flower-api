<?php

namespace Tests\Unit;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_be_created(): void
    {
        $category = Category::create([
            'name' => '玫瑰',
            'slug' => 'roses',
            'icon' => '🌹',
            'description' => '各类玫瑰花束',
        ]);

        $this->assertDatabaseHas('categories', ['slug' => 'roses']);
        $this->assertEquals('玫瑰', $category->name);
        $this->assertEquals('roses', $category->slug);
        $this->assertEquals('🌹', $category->icon);
    }

    public function test_category_fillable_attributes(): void
    {
        $fillable = (new Category())->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('slug', $fillable);
        $this->assertContains('icon', $fillable);
        $this->assertContains('description', $fillable);
    }
}
