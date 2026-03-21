<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    public function test_it_can_be_instantiated(): void
    {
        $category = new Category();
        $this->assertInstanceOf(Category::class, $category);
    }
    public function test_fillable_attributes_are_defined(): void
    {
        $category = new Category();
        $fillable = $category->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('slug', $fillable);
        $this->assertContains('icon', $fillable);
        $this->assertContains('description', $fillable);
    }
}
