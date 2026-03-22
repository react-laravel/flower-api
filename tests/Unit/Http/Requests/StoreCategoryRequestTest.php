<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\StoreCategoryRequest;
use PHPUnit\Framework\TestCase;

class StoreCategoryRequestTest extends TestCase
{
    public function test_it_authorizes_any_request(): void
    {
        $request = new StoreCategoryRequest();
        $this->assertTrue($request->authorize());
    }
    public function test_rules_require_name_and_slug(): void
    {
        $request = new StoreCategoryRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('slug', $rules);
        $this->assertStringContainsString('required', $rules['name']);
        $this->assertStringContainsString('required', $rules['slug']);
    }
    public function test_icon_and_description_are_optional(): void
    {
        $request = new StoreCategoryRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('icon', $rules);
        $this->assertArrayHasKey('description', $rules);
    }
}
