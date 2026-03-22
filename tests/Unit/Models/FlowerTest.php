<?php

namespace Tests\Unit\Models;

use App\Models\Flower;
use PHPUnit\Framework\TestCase;

class FlowerTest extends TestCase
{
    public function test_it_can_be_instantiated(): void
    {
        $flower = new Flower();
        $this->assertInstanceOf(Flower::class, $flower);
    }
    public function test_fillable_attributes_are_defined(): void
    {
        $flower = new Flower();
        $fillable = $flower->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('name_en', $fillable);
        $this->assertContains('category', $fillable);
        $this->assertContains('price', $fillable);
        $this->assertContains('original_price', $fillable);
        $this->assertContains('image', $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('meaning', $fillable);
        $this->assertContains('care', $fillable);
        $this->assertContains('stock', $fillable);
        $this->assertContains('featured', $fillable);
        $this->assertContains('holiday', $fillable);
    }
    public function test_price_is_cast_to_decimal(): void
    {
        $flower = new Flower();
        $casts = $flower->getCasts();

        $this->assertArrayHasKey('price', $casts);
        $this->assertEquals('decimal:2', $casts['price']);
    }
    public function test_original_price_is_cast_to_decimal(): void
    {
        $flower = new Flower();
        $casts = $flower->getCasts();

        $this->assertArrayHasKey('original_price', $casts);
        $this->assertEquals('decimal:2', $casts['original_price']);
    }
    public function test_featured_is_cast_to_boolean(): void
    {
        $flower = new Flower();
        $casts = $flower->getCasts();

        $this->assertArrayHasKey('featured', $casts);
        $this->assertEquals('boolean', $casts['featured']);
    }
}
