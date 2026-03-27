<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\UpdateFlowerRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateFlowerRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new UpdateFlowerRequest();
        return Validator::make($data, $request->rules());
    }

    public function test_it_authorizes_any_request(): void
    {
        $request = new UpdateFlowerRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_all_fields_exist_in_rules(): void
    {
        $request = new UpdateFlowerRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('name_en', $rules);
        $this->assertArrayHasKey('category', $rules);
        $this->assertArrayHasKey('price', $rules);
        $this->assertArrayHasKey('original_price', $rules);
        $this->assertArrayHasKey('image', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('meaning', $rules);
        $this->assertArrayHasKey('care', $rules);
        $this->assertArrayHasKey('stock', $rules);
        $this->assertArrayHasKey('featured', $rules);
        $this->assertArrayHasKey('holiday', $rules);
    }

    public function test_empty_data_passes_validation(): void
    {
        // Unlike StoreFlowerRequest, all fields are optional for update
        $validator = $this->validate([]);

        $this->assertFalse($validator->fails());
    }

    public function test_name_is_optional_string(): void
    {
        $validator = $this->validate(['name' => 'Updated Flower']);

        $this->assertFalse($validator->fails());
    }

    public function test_name_max_length_is_255(): void
    {
        $validator = $this->validate(['name' => str_repeat('a', 256)]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_name_en_is_optional_string(): void
    {
        $validator = $this->validate(['name_en' => 'Updated Flower EN']);

        $this->assertFalse($validator->fails());
    }

    public function test_name_en_max_length_is_255(): void
    {
        $validator = $this->validate(['name_en' => str_repeat('a', 256)]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name_en', $validator->errors()->toArray());
    }

    public function test_category_is_optional_string(): void
    {
        $validator = $this->validate(['category' => 'tulip']);

        $this->assertFalse($validator->fails());
    }

    public function test_category_max_length_is_255(): void
    {
        $validator = $this->validate(['category' => str_repeat('a', 256)]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    public function test_price_is_optional_numeric(): void
    {
        $validator = $this->validate(['price' => 49.99]);

        $this->assertFalse($validator->fails());
    }

    public function test_price_must_be_numeric(): void
    {
        $validator = $this->validate(['price' => 'not-a-number']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
    }

    public function test_price_must_be_non_negative(): void
    {
        $validator = $this->validate(['price' => -10]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
    }

    public function test_original_price_is_nullable_numeric(): void
    {
        $validator = $this->validate(['original_price' => null]);

        $this->assertFalse($validator->fails());
    }

    public function test_original_price_must_be_numeric_when_provided(): void
    {
        $validator = $this->validate(['original_price' => 'not-number']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('original_price', $validator->errors()->toArray());
    }

    public function test_original_price_must_be_non_negative(): void
    {
        $validator = $this->validate(['original_price' => -5]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('original_price', $validator->errors()->toArray());
    }

    public function test_image_is_optional_string(): void
    {
        $validator = $this->validate(['image' => '/images/flower.jpg']);

        $this->assertFalse($validator->fails());
    }

    public function test_description_is_optional_string(): void
    {
        $validator = $this->validate(['description' => 'A beautiful flower']);

        $this->assertFalse($validator->fails());
    }

    public function test_meaning_is_optional_string(): void
    {
        $validator = $this->validate(['meaning' => 'Love']);

        $this->assertFalse($validator->fails());
    }

    public function test_care_is_optional_string(): void
    {
        $validator = $this->validate(['care' => 'Water daily']);

        $this->assertFalse($validator->fails());
    }

    public function test_stock_is_optional_integer(): void
    {
        $validator = $this->validate(['stock' => 50]);

        $this->assertFalse($validator->fails());
    }

    public function test_stock_must_be_integer(): void
    {
        $validator = $this->validate(['stock' => 10.5]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('stock', $validator->errors()->toArray());
    }

    public function test_stock_must_be_non_negative(): void
    {
        $validator = $this->validate(['stock' => -1]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('stock', $validator->errors()->toArray());
    }

    public function test_featured_is_optional_boolean_true(): void
    {
        $validator = $this->validate(['featured' => true]);

        $this->assertFalse($validator->fails());
    }

    public function test_featured_is_optional_boolean_false(): void
    {
        $validator = $this->validate(['featured' => false]);

        $this->assertFalse($validator->fails());
    }

    public function test_featured_must_be_boolean(): void
    {
        $validator = $this->validate(['featured' => 'yes']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('featured', $validator->errors()->toArray());
    }

    public function test_holiday_is_optional_string(): void
    {
        $validator = $this->validate(['holiday' => 'Valentine']);

        $this->assertFalse($validator->fails());
    }

    public function test_holiday_can_be_null(): void
    {
        $validator = $this->validate(['holiday' => null]);

        $this->assertFalse($validator->fails());
    }

    public function test_partial_update_passes_with_single_field(): void
    {
        $validator = $this->validate(['name' => 'New Name']);

        $this->assertFalse($validator->fails());
    }

    public function test_partial_update_passes_with_multiple_fields(): void
    {
        $validator = $this->validate([
            'name' => 'New Name',
            'price' => 79.99,
            'featured' => true,
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_boundary_price_zero_is_valid(): void
    {
        $validator = $this->validate(['price' => 0]);

        $this->assertFalse($validator->fails());
    }

    public function test_boundary_stock_zero_is_valid(): void
    {
        $validator = $this->validate(['stock' => 0]);

        $this->assertFalse($validator->fails());
    }

    public function test_boundary_max_permissible_values(): void
    {
        $validator = $this->validate([
            'name' => str_repeat('a', 255),
            'name_en' => str_repeat('b', 255),
            'category' => str_repeat('c', 255),
            'price' => 999999999.99,
            'stock' => 2147483647,
        ]);

        $this->assertFalse($validator->fails());
    }
}