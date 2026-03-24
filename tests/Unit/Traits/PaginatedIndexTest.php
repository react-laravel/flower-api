<?php

namespace Tests\Unit\Traits;

use App\Http\Traits\PaginatedIndex;
use App\Models\Flower;
use App\ValueObjects\FlowerFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class PaginatedIndexTest extends TestCase
{
    use RefreshDatabase, PaginatedIndex;

    /**
     * Test applyFilters returns query unchanged by default
     */
    public function test_apply_filters_returns_query_unchanged(): void
    {
        $query = Flower::query();
        $request = Request::create('/flowers', 'GET');

        $result = $this->applyFilters($query, $request);

        $this->assertSame($query, $result);
    }

    /**
     * Test paginatedIndex returns correct structure
     */
    public function test_paginated_index_returns_correct_structure(): void
    {
        $request = Request::create('/flowers', 'GET', ['per_page' => 5]);

        $response = $this->paginatedIndex(Flower::query(), $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('items', $data['data']);
        $this->assertArrayHasKey('total', $data['data']);
        $this->assertArrayHasKey('current_page', $data['data']);
        $this->assertArrayHasKey('last_page', $data['data']);
        $this->assertArrayHasKey('per_page', $data['data']);
    }

    /**
     * Test paginatedIndex respects per_page parameter
     */
    public function test_paginated_index_respects_per_page(): void
    {
        $request = Request::create('/flowers', 'GET', ['per_page' => 10]);

        $response = $this->paginatedIndex(Flower::query(), $request);

        $data = $response->getData(true);
        $this->assertEquals(10, $data['data']['per_page']);
    }

    /**
     * Test paginatedIndex caps per_page at 100
     */
    public function test_paginated_index_caps_per_page_at_100(): void
    {
        $request = Request::create('/flowers', 'GET', ['per_page' => 500]);

        $response = $this->paginatedIndex(Flower::query(), $request);

        $data = $response->getData(true);
        $this->assertEquals(100, $data['data']['per_page']);
    }

    /**
     * Test paginatedIndex uses default per_page of 20
     */
    public function test_paginated_index_uses_default_per_page(): void
    {
        $request = Request::create('/flowers', 'GET');

        $response = $this->paginatedIndex(Flower::query(), $request);

        $data = $response->getData(true);
        $this->assertEquals(20, $data['data']['per_page']);
    }

    /**
     * Test paginatedIndexWithFilter returns correct structure
     */
    public function test_paginated_index_with_filter_returns_correct_structure(): void
    {
        $request = Request::create('/flowers', 'GET', []);
        $filter = FlowerFilter::fromRequest($request);

        $response = $this->paginatedIndexWithFilter(Flower::query(), $filter);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('items', $data['data']);
        $this->assertArrayHasKey('total', $data['data']);
        $this->assertArrayHasKey('current_page', $data['data']);
        $this->assertArrayHasKey('last_page', $data['data']);
        $this->assertArrayHasKey('per_page', $data['data']);
    }

    /**
     * Test paginatedIndexWithFilter uses filter's perPage
     */
    public function test_paginated_index_with_filter_uses_filter_per_page(): void
    {
        $request = Request::create('/flowers', 'GET', ['per_page' => 15]);
        $filter = FlowerFilter::fromRequest($request);

        $response = $this->paginatedIndexWithFilter(Flower::query(), $filter);

        $data = $response->getData(true);
        $this->assertEquals(15, $data['data']['per_page']);
    }
}
