<?php

namespace Tests\Unit;

use App\Http\Requests\StoreFlowerRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreFlowerRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreFlowerRequest();
        return Validator::make($data, $request->rules());
    }

    public function test_valid_data_passes(): void
    {
        $validator = $this->validate([
            'name' => '红玫瑰',
            'name_en' => 'Red Rose',
            'category' => '玫瑰',
            'price' => 99.00,
            'image' => '/images/red-rose.jpg',
            'description' => '美丽的红玫瑰',
            'meaning' => '爱情',
            'care' => '每天换水',
            'stock' => 100,
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_valid_data_with_all_fields_passes(): void
    {
        $validator = $this->validate([
            'name' => '红玫瑰',
            'name_en' => 'Red Rose',
            'category' => '玫瑰',
            'price' => 99.00,
            'original_price' => 129.00,
            'image' => '/images/red-rose.jpg',
            'description' => '美丽的红玫瑰',
            'meaning' => '爱情',
            'care' => '每天换水',
            'stock' => 100,
            'featured' => true,
            'holiday' => '情人节',
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_name_is_required(): void
    {
        $validator = $this->validate([
            'name_en' => 'Red Rose',
            'category' => 'R',
            'price' => 10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_name_en_is_required(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'category' => 'R',
            'price' => 10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name_en', $validator->errors()->toArray());
    }

    public function test_category_is_required(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'price' => 10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    public function test_price_is_required(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
    }

    public function test_stock_is_required(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('stock', $validator->errors()->toArray());
    }

    public function test_name_max_length_is_255(): void
    {
        $validator = $this->validate([
            'name' => str_repeat('a', 256),
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_name_en_max_length_is_255(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => str_repeat('a', 256),
            'category' => 'R',
            'price' => 10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name_en', $validator->errors()->toArray());
    }

    public function test_category_max_length_is_255(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => str_repeat('a', 256),
            'price' => 10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    public function test_price_must_be_numeric(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 'not-number',
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
    }

    public function test_price_must_be_non_negative(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => -10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
    }

    public function test_price_zero_is_valid(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 0,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
        ]);
        $this->assertFalse($validator->fails());
    }

    public function test_price_decimal_is_valid(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 99.99,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
        ]);
        $this->assertFalse($validator->fails());
    }

    public function test_original_price_is_nullable(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 10,
            'original_price' => null,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
        ]);
        $this->assertFalse($validator->fails());
    }

    public function test_original_price_must_be_numeric_when_provided(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 10,
            'original_price' => 'not-number',
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('original_price', $validator->errors()->toArray());
    }

    public function test_original_price_must_be_non_negative(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 10,
            'original_price' => -5,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('original_price', $validator->errors()->toArray());
    }

    public function test_image_is_nullable(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 10,
            'image' => null,
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
        ]);
        $this->assertFalse($validator->fails());
    }

    public function test_description_is_nullable(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 10,
            'image' => 'img',
            'description' => null,
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
        ]);
        $this->assertFalse($validator->fails());
    }

    public function test_meaning_is_nullable(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => null,
            'care' => 'c',
            'stock' => 1,
        ]);
        $this->assertFalse($validator->fails());
    }

    public function test_care_is_nullable(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => null,
            'stock' => 1,
        ]);
        $this->assertFalse($validator->fails());
    }

    public function test_holiday_is_nullable(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
            'holiday' => null,
        ]);
        $this->assertFalse($validator->fails());
    }

    public function test_stock_must_be_integer(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 'not-int',
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('stock', $validator->errors()->toArray());
    }

    public function test_stock_must_be_non_negative(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => -1,
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('stock', $validator->errors()->toArray());
    }

    public function test_stock_zero_is_valid(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 0,
        ]);
        $this->assertFalse($validator->fails());
    }

    public function test_featured_is_boolean_true(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
            'featured' => true,
        ]);
        $this->assertFalse($validator->fails());
    }

    public function test_featured_is_boolean_false(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
            'featured' => false,
        ]);
        $this->assertFalse($validator->fails());
    }

    public function test_featured_must_be_boolean(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
            'featured' => 'yes',
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('featured', $validator->errors()->toArray());
    }

    public function test_featured_null_fails_validation(): void
    {
        // featured field is boolean without nullable, so null fails
        $validator = $this->validate([
            'name' => 'Test',
            'name_en' => 'Test',
            'category' => 'R',
            'price' => 10,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 1,
            'featured' => null,
        ]);
        $this->assertTrue($validator->fails());
    }

    public function test_boundary_max_permissible_values(): void
    {
        $validator = $this->validate([
            'name' => str_repeat('a', 255),
            'name_en' => str_repeat('b', 255),
            'category' => str_repeat('c', 255),
            'price' => 999999999.99,
            'original_price' => 999999999.99,
            'image' => 'img',
            'description' => 'd',
            'meaning' => 'm',
            'care' => 'c',
            'stock' => 2147483647,
        ]);
        $this->assertFalse($validator->fails());
    }

    public function test_all_required_fields_missing_fails(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('name_en', $errors);
        $this->assertArrayHasKey('category', $errors);
        $this->assertArrayHasKey('price', $errors);
        $this->assertArrayHasKey('stock', $errors);
    }
}