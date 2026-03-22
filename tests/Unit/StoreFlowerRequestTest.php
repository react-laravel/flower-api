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

    public function test_name_is_required(): void
    {
        $validator = $this->validate(['name_en' => 'Red Rose', 'category' => 'R', 'price' => 10, 'image' => 'img', 'description' => 'd', 'meaning' => 'm', 'care' => 'c', 'stock' => 1]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_price_must_be_numeric(): void
    {
        $validator = $this->validate(['name' => 'Test', 'name_en' => 'Test', 'category' => 'R', 'price' => 'not-number', 'image' => 'img', 'description' => 'd', 'meaning' => 'm', 'care' => 'c', 'stock' => 1]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
    }

    public function test_price_must_be_non_negative(): void
    {
        $validator = $this->validate(['name' => 'Test', 'name_en' => 'Test', 'category' => 'R', 'price' => -10, 'image' => 'img', 'description' => 'd', 'meaning' => 'm', 'care' => 'c', 'stock' => 1]);
        $this->assertTrue($validator->fails());
    }

    public function test_stock_must_be_integer(): void
    {
        $validator = $this->validate(['name' => 'Test', 'name_en' => 'Test', 'category' => 'R', 'price' => 10, 'image' => 'img', 'description' => 'd', 'meaning' => 'm', 'care' => 'c', 'stock' => 'not-int']);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('stock', $validator->errors()->toArray());
    }
}
