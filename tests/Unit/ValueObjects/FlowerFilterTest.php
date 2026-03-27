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

    public function test_from_request_featured_false_is_parsed_correctly(): void
    {
        $request = new Request(['featured' => 'false']);

        $filter = FlowerFilter::fromRequest($request);

        $this->assertFalse($filter->featured);
    }

    public function test_from_request_featured_missing_is_null(): void
    {
        $request = new Request();

        $filter = FlowerFilter::fromRequest($request);

        $this->assertNull($filter->featured);
    }

    public function test_from_request_per_page_zero_is_passed_through(): void
    {
        // Actual behavior: min(0, 100) = 0 (not defaulting to 20)
        $request = new Request(['per_page' => 0]);

        $filter = FlowerFilter::fromRequest($request);

        $this->assertEquals(0, $filter->perPage);
    }

    public function test_from_request_negative_per_page_becomes_negative(): void
    {
        // Actual behavior: min(-50, 100) = -50 (not capped at 100)
        $request = new Request(['per_page' => -50]);

        $filter = FlowerFilter::fromRequest($request);

        $this->assertEquals(-50, $filter->perPage);
    }

    public function test_from_request_per_page_invalid_string_becomes_zero(): void
    {
        // Actual behavior: (int)'invalid' = 0, min(0, 100) = 0
        $request = new Request(['per_page' => 'invalid']);

        $filter = FlowerFilter::fromRequest($request);

        $this->assertEquals(0, $filter->perPage);
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

    public function test_apply_filters_by_featured_false(): void
    {
        Flower::create(['name' => '推荐玫瑰', 'name_en' => 'Featured Rose', 'category' => 'rose', 'price' => 99, 'featured' => true, 'image' => 'featured.jpg', 'description' => '推荐描述', 'meaning' => '爱情', 'care' => '定期浇水', 'user_id' => null]);
        Flower::create(['name' => '普通百合', 'name_en' => 'Normal Lily', 'category' => 'lily', 'price' => 79, 'featured' => false, 'image' => 'normal.jpg', 'description' => '普通描述', 'meaning' => '纯洁', 'care' => '定期浇水', 'user_id' => null]);

        $filter = FlowerFilter::fromRequest(new Request(['featured' => 'false']));
        $query = Flower::query();
        $filtered = $filter->apply($query)->get();

        $this->assertCount(1, $filtered);
        $this->assertFalse($filtered->first()->featured);
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

    public function test_apply_filters_by_search_on_name_en(): void
    {
        Flower::create(['name' => '红玫瑰', 'name_en' => 'Red Rose', 'category' => 'rose', 'price' => 99, 'image' => 'red.jpg', 'description' => '红玫瑰描述', 'meaning' => '爱情', 'care' => '定期浇水', 'user_id' => null]);
        Flower::create(['name' => '白玫瑰', 'name_en' => 'White Rose', 'category' => 'rose', 'price' => 89, 'image' => 'white.jpg', 'description' => '白玫瑰描述', 'meaning' => '纯洁', 'care' => '定期浇水', 'user_id' => null]);

        $filter = FlowerFilter::fromRequest(new Request(['search' => 'White']));
        $query = Flower::query();
        $filtered = $filter->apply($query)->get();

        $this->assertCount(1, $filtered);
        $this->assertEquals('白玫瑰', $filtered->first()->name);
    }

    public function test_apply_filters_combines_multiple_conditions(): void
    {
        Flower::create(['name' => '红玫瑰', 'name_en' => 'Red Rose', 'category' => 'rose', 'price' => 99, 'featured' => true, 'image' => 'red.jpg', 'description' => '红玫瑰描述', 'meaning' => '爱情', 'care' => '定期浇水', 'user_id' => null]);
        Flower::create(['name' => '白玫瑰', 'name_en' => 'White Rose', 'category' => 'rose', 'price' => 89, 'featured' => false, 'image' => 'white.jpg', 'description' => '白玫瑰描述', 'meaning' => '纯洁', 'care' => '定期浇水', 'user_id' => null]);

        $filter = FlowerFilter::fromRequest(new Request(['category' => 'rose', 'featured' => 'true']));
        $query = Flower::query();
        $filtered = $filter->apply($query)->get();

        $this->assertCount(1, $filtered);
        $this->assertEquals('rose', $filtered->first()->category);
        $this->assertTrue($filtered->first()->featured);
    }

    public function test_apply_filters_with_no_results(): void
    {
        Flower::create(['name' => '红玫瑰', 'name_en' => 'Red Rose', 'category' => 'rose', 'price' => 99, 'image' => 'red.jpg', 'description' => '红玫瑰描述', 'meaning' => '爱情', 'care' => '定期浇水', 'user_id' => null]);

        $filter = FlowerFilter::fromRequest(new Request(['category' => 'nonexistent']));
        $query = Flower::query();
        $filtered = $filter->apply($query)->get();

        $this->assertCount(0, $filtered);
    }

    public function test_apply_filters_returns_all_when_no_filters(): void
    {
        Flower::create(['name' => '红玫瑰', 'name_en' => 'Red Rose', 'category' => 'rose', 'price' => 99, 'image' => 'red.jpg', 'description' => '红玫瑰描述', 'meaning' => '爱情', 'care' => '定期浇水', 'user_id' => null]);
        Flower::create(['name' => '白玫瑰', 'name_en' => 'White Rose', 'category' => 'rose', 'price' => 89, 'image' => 'white.jpg', 'description' => '白玫瑰描述', 'meaning' => '纯洁', 'care' => '定期浇水', 'user_id' => null]);

        $filter = FlowerFilter::fromRequest(new Request());
        $query = Flower::query();
        $filtered = $filter->apply($query)->get();

        $this->assertCount(2, $filtered);
    }

    public function test_has_filters_returns_true_when_category_set(): void
    {
        $filter = FlowerFilter::fromRequest(new Request(['category' => 'rose']));

        $this->assertTrue($filter->hasFilters());
    }

    public function test_has_filters_returns_false_when_no_filters(): void
    {
        $filter = FlowerFilter::fromRequest(new Request());

        $this->assertFalse($filter->hasFilters());
    }

    public function test_has_filters_returns_true_when_featured_set(): void
    {
        $filter = FlowerFilter::fromRequest(new Request(['featured' => 'true']));

        $this->assertTrue($filter->hasFilters());
    }

    public function test_has_filters_returns_true_when_search_set(): void
    {
        $filter = FlowerFilter::fromRequest(new Request(['search' => 'rose']));

        $this->assertTrue($filter->hasFilters());
    }

    public function test_has_filters_returns_false_when_category_is_all(): void
    {
        // 'all' is normalized to null, so hasFilters should return false
        $filter = FlowerFilter::fromRequest(new Request(['category' => 'all']));

        $this->assertFalse($filter->hasFilters());
    }

    public function test_has_filters_returns_false_when_search_is_empty_string(): void
    {
        $filter = FlowerFilter::fromRequest(new Request(['search' => '']));

        $this->assertFalse($filter->hasFilters());
    }

    public function test_has_filters_combines_multiple_active_filters(): void
    {
        $filter = FlowerFilter::fromRequest(new Request([
            'category' => 'rose',
            'featured' => 'true',
            'search' => 'red',
        ]));

        $this->assertTrue($filter->hasFilters());
    }

    public function test_search_with_partial_match(): void
    {
        Flower::create(['name' => '红玫瑰', 'name_en' => 'Red Rose', 'category' => 'rose', 'price' => 99, 'image' => 'red.jpg', 'description' => '红玫瑰描述', 'meaning' => '爱情', 'care' => '定期浇水', 'user_id' => null]);
        Flower::create(['name' => '玫瑰花', 'name_en' => 'Rose Bouquet', 'category' => 'rose', 'price' => 199, 'image' => 'bouquet.jpg', 'description' => '玫瑰花束', 'meaning' => '爱情', 'care' => '定期浇水', 'user_id' => null]);

        $filter = FlowerFilter::fromRequest(new Request(['search' => '玫瑰']));
        $query = Flower::query();
        $filtered = $filter->apply($query)->get();

        $this->assertCount(2, $filtered);
    }
}