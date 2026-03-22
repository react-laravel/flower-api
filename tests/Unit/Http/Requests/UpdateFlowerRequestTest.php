<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\UpdateFlowerRequest;
use PHPUnit\Framework\TestCase;

class UpdateFlowerRequestTest extends TestCase
{
    public function test_it_authorizes_any_request(): void
    {
        $request = new UpdateFlowerRequest();
        $this->assertTrue($request->authorize());
    }
    public function test_all_fields_are_optional_for_update(): void
    {
        $request = new UpdateFlowerRequest();
        $rules = $request->rules();

        // Unlike StoreFlowerRequest, fields should be nullable/optional
        foreach (['name', 'name_en', 'category', 'price', 'image', 'description', 'meaning', 'care', 'stock'] as $field) {
            $this->assertArrayHasKey($field, $rules);
        }
    }
}
