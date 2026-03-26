<?php

namespace Tests\Unit;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    // ─── Mass assignment ────────────────────────────────────────────────────

    public function test_can_create_category_with_all_fields(): void
    {
        $category = Category::create([
            'name' => '玫瑰',
            'slug' => 'rose',
            'icon' => '🌹',
            'description' => '爱情之花',
        ]);

        $this->assertDatabaseHas('categories', ['name' => '玫瑰', 'slug' => 'rose']);
        $this->assertEquals('🌹', $category->icon);
    }

    public function test_can_create_category_without_optional_fields(): void
    {
        $category = Category::create([
            'name' => 'Misc',
            'slug' => 'misc',
        ]);

        $this->assertDatabaseHas('categories', ['name' => 'Misc']);
        $this->assertNull($category->icon);
        $this->assertNull($category->description);
    }

    // ─── Fillable protection ─────────────────────────────────────────────────

    public function test_non_fillable_fields_are_ignored(): void
    {
        $category = Category::create([
            'name' => 'Test',
            'slug' => 'test',
            'icon' => '🌸',
            'description' => 'Test',
            'unknown_field' => 'should be ignored',
        ]);

        $this->assertArrayNotHasKey('unknown_field', $category->getAttributes());
    }

    // ─── CRUD ───────────────────────────────────────────────────────────────

    public function test_can_update_category(): void
    {
        $category = Category::create([
            'name' => 'Original',
            'slug' => 'original',
            'icon' => '🌺',
            'description' => 'Original description',
        ]);

        $category->update(['name' => 'Updated', 'slug' => 'updated']);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Updated', 'slug' => 'updated']);
    }

    public function test_can_delete_category(): void
    {
        $category = Category::create([
            'name' => 'To Delete',
            'slug' => 'to-delete',
            'icon' => '🌺',
            'description' => 'Will be deleted',
        ]);

        $id = $category->id;
        $category->delete();

        $this->assertDatabaseMissing('categories', ['id' => $id]);
    }

    // ─── Slug uniqueness ─────────────────────────────────────────────────────

    public function test_slug_is_not_inherently_unique(): void
    {
        // Note: the model does not define a unique rule on slug.
        // The controller/request layer enforces uniqueness via validation.
        // This test documents the model-layer behaviour.
        Category::create(['name' => 'First', 'slug' => 'same-slug', 'icon' => '🌸']);
        $second = Category::create(['name' => 'Second', 'slug' => 'same-slug', 'icon' => '🌺']);

        $this->assertDatabaseHas('categories', ['name' => 'First', 'slug' => 'same-slug']);
        $this->assertDatabaseHas('categories', ['name' => 'Second', 'slug' => 'same-slug']);
    }

    // ─── Ordering ───────────────────────────────────────────────────────────

    public function test_can_order_categories_by_name(): void
    {
        Category::create(['name' => 'ZZZ', 'slug' => 'zzz']);
        Category::create(['name' => 'AAA', 'slug' => 'aaa']);
        Category::create(['name' => 'MMM', 'slug' => 'mmm']);

        $ordered = Category::orderBy('name')->get();

        $this->assertEquals('AAA', $ordered[0]->name);
        $this->assertEquals('MMM', $ordered[1]->name);
        $this->assertEquals('ZZZ', $ordered[2]->name);
    }

    public function test_can_filter_by_slug(): void
    {
        Category::create(['name' => 'Rose', 'slug' => 'rose']);
        Category::create(['name' => 'Lily', 'slug' => 'lily']);

        $result = Category::where('slug', 'rose')->first();

        $this->assertEquals('Rose', $result->name);
    }
}
