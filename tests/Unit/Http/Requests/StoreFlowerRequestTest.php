<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\StoreFlowerRequest;
use PHPUnit\Framework\TestCase;

class StoreFlowerRequestTest extends TestCase
{
    public function test_it_authorizes_any_request(): void
    {
        $request = new StoreFlowerRequest();
        $this->assertTrue($request->authorize());
    }
    public function test_rules_require_all_fields(): void
    {
        $request = new StoreFlowerRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('name_en', $rules);
        $this->assertArrayHasKey('category', $rules);
        $this->assertArrayHasKey('price', $rules);
        $this->assertArrayHasKey('image', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('meaning', $rules);
        $this->assertArrayHasKey('care', $rules);
        $this->assertArrayHasKey('stock', $rules);
    }
    public function test_name_is_required_string(): void
    {
        $request = new StoreFlowerRequest();
        $rules = $request->rules();

        $this->assertStringContainsString('required', $rules['name']);
        $this->assertStringContainsString('string', $rules['name']);
        $this->assertStringContainsString('max:255', $rules['name']);
    }
    public function test_price_is_required_numeric(): void
    {
        $request = new StoreFlowerRequest();
        $rules = $request->rules();

        $this->assertStringContainsString('required', $rules['price']);
        $this->assertStringContainsString('numeric', $rules['price']);
        $this->assertStringContainsString('min:0', $rules['price']);
    }
    public function test_original_price_is_nullable_numeric(): void
    {
        $request = new StoreFlowerRequest();
        $rules = $request->rules();

        $this->assertStringContainsString('nullable', $rules['original_price']);
        $this->assertStringContainsString('numeric', $rules['original_price']);
    }
    public function test_featured_is_boolean(): void
    {
        $request = new StoreFlowerRequest();
        $rules = $request->rules();

        $this->assertStringContainsString('boolean', $rules['featured']);
    }
    public function test_stock_is_required_integer(): void
    {
        $request = new StoreFlowerRequest();
        $rules = $request->rules();

        $this->assertStringContainsString('required', $rules['stock']);
        $this->assertStringContainsString('integer', $rules['stock']);
        $this->assertStringContainsString('min:0', $rules['stock']);
    }
}
