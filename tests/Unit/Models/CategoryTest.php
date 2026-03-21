<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_a_category(): void
    {
        $category = Category::create([
            'name' => '玫瑰',
            'slug' => 'rose',
            'icon' => '🌹',
            'description' => '各种玫瑰花',
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => '玫瑰',
            'slug' => 'rose',
        ]);

        $this->assertEquals('玫瑰', $category->name);
        $this->assertEquals('rose', $category->slug);
        $this->assertEquals('🌹', $category->icon);
        $this->assertEquals('各种玫瑰花', $category->description);
    }

    /**
     * @test
     */
    public function it_has_fillable_attributes(): void
    {
        $fillable = ['name', 'slug', 'icon', 'description'];

        $this->assertEquals($fillable, (new Category)->getFillable());
    }

    /**
     * @test
     */
    public function it_can_order_categories_by_name(): void
    {
        Category::create(['name' => '向日葵']);
        Category::create(['name' => '玫瑰']);
        Category::create(['name' => '百合']);

        $categories = Category::orderBy('name')->get();

        $this->assertEquals('百合', $categories->first()->name);
        $this->assertEquals('向日葵', $categories->last()->name);
    }

    /**
     * @test
     */
    public function it_can_update_category(): void
    {
        $category = Category::create([
            'name' => '玫瑰',
            'slug' => 'rose',
        ]);

        $category->update(['name' => '红玫瑰', 'description' => '红色系玫瑰']);

        $this->assertEquals('红玫瑰', $category->fresh()->name);
        $this->assertEquals('红色系玫瑰', $category->fresh()->description);
    }

    /**
     * @test
     */
    public function it_can_delete_category(): void
    {
        $category = Category::create([
            'name' => '玫瑰',
            'slug' => 'rose',
        ]);

        $id = $category->id;
        $category->delete();

        $this->assertNull(Category::find($id));
    }
}
