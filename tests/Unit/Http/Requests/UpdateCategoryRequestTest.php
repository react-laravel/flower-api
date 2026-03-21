<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\UpdateCategoryRequest;
use PHPUnit\Framework\TestCase;

class UpdateCategoryRequestTest extends TestCase
{
    public function test_it_authorizes_any_request(): void
    {
        $request = new UpdateCategoryRequest();
        $this->assertTrue($request->authorize());
    }
    public function test_all_fields_are_optional_for_update(): void
    {
        $request = new UpdateCategoryRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('slug', $rules);
        $this->assertArrayHasKey('icon', $rules);
        $this->assertArrayHasKey('description', $rules);
    }
}
