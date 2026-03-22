<?php

namespace Tests\Unit\ValueObjects;

use App\Models\Flower;
use App\ValueObjects\FlowerFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FlowerFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_from_request_creates_filter_with_all_params(): void
    {
        $request = new Request(['category' => 'rose', 'featured' => 'true', 'search' => 'red', 'per_page' => 50]);

        $filter = FlowerFilter::fromRequest($request);

        $this->assertEquals('rose', $filter->category);
        $this->assertTrue($filter->featured);
        $this->assertEquals('red', $filter->search);
        $this->assertEquals(50, $filter->perPage);
    }

    public function test_from_request_normalizes_all_category_to_null(): void
    {
        $request = new Request(['category' => 'all']);

        $filter = FlowerFilter::fromRequest($request);

        $this->assertNull($filter->category);
    }

    public function test_from_request_caps_per_page_at_100(): void
    {
        $request = new Request(['per_page' => 200]);

        $filter = FlowerFilter::fromRequest($request);

        $this->assertEquals(100, $filter->perPage);
    }

    public function test_from_request_defaults_per_page_to_20(): void
    {
        $request = new Request();

        $filter = FlowerFilter::fromRequest($request);

        $this->assertEquals(20, $filter->perPage);
    }

    public function test_apply_filters_by_category(): void
    {
        Flower::create(['name' => '红玫瑰', 'name_en' => 'Red Rose', 'category' => 'rose', 'price' => 99, 'image' => 'rose.jpg', 'description' => '红玫瑰描述', 'meaning' => '爱情', 'care' => '定期浇水', 'user_id' => null]);
        Flower::create(['name' => '白百合', 'name_en' => 'White Lily', 'category' => 'lily', 'price' => 79, 'image' => 'lily.jpg', 'description' => '白百合描述', 'meaning' => '纯洁', 'care' => '定期浇水', 'user_id' => null]);

        $filter = FlowerFilter::fromRequest(new Request(['category' => 'rose']));
        $query = Flower::query();
        $filtered = $filter->apply($query)->get();

        $this->assertCount(1, $filtered);
        $this->assertEquals('rose', $filtered->first()->category);
    }

    public function test_apply_filters_by_featured(): void
    {
        Flower::create(['name' => '推荐玫瑰', 'name_en' => 'Featured Rose', 'category' => 'rose', 'price' => 99, 'featured' => true, 'image' => 'featured.jpg', 'description' => '推荐描述', 'meaning' => '爱情', 'care' => '定期浇水', 'user_id' => null]);
        Flower::create(['name' => '普通百合', 'name_en' => 'Normal Lily', 'category' => 'lily', 'price' => 79, 'featured' => false, 'image' => 'normal.jpg', 'description' => '普通描述', 'meaning' => '纯洁', 'care' => '定期浇水', 'user_id' => null]);

        $filter = FlowerFilter::fromRequest(new Request(['featured' => 'true']));
        $query = Flower::query();
        $filtered = $filter->apply($query)->get();

        $this->assertCount(1, $filtered);
        $this->assertTrue($filtered->first()->featured);
    }

    public function test_apply_filters_by_search(): void
    {
        Flower::create(['name' => '红玫瑰', 'name_en' => 'Red Rose', 'category' => 'rose', 'price' => 99, 'image' => 'red.jpg', 'description' => '红玫瑰描述', 'meaning' => '爱情', 'care' => '定期浇水', 'user_id' => null]);
        Flower::create(['name' => '白玫瑰', 'name_en' => 'White Rose', 'category' => 'rose', 'price' => 89, 'image' => 'white.jpg', 'description' => '白玫瑰描述', 'meaning' => '纯洁', 'care' => '定期浇水', 'user_id' => null]);

        $filter = FlowerFilter::fromRequest(new Request(['search' => '红']));
        $query = Flower::query();
        $filtered = $filter->apply($query)->get();

        $this->assertCount(1, $filtered);
        $this->assertEquals('红玫瑰', $filtered->first()->name);
    }

    public function test_has_filters_returns_true_when_filters_active(): void
    {
        $filter = FlowerFilter::fromRequest(new Request(['category' => 'rose']));

        $this->assertTrue($filter->hasFilters());
    }

    public function test_has_filters_returns_false_when_no_filters(): void
    {
        $filter = FlowerFilter::fromRequest(new Request());

        $this->assertFalse($filter->hasFilters());
    }
}
