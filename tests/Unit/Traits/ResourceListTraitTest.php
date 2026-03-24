<?php

namespace Tests\Unit\Traits;

use App\Http\Traits\ResourceListTrait;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class ResourceListTraitTest extends TestCase
{
    use RefreshDatabase, ResourceListTrait;

    /**
     * Test listAll returns correct structure
     */
    public function test_list_all_returns_correct_structure(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->listAll(Category::class);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    /**
     * Test listAll returns all categories ordered by default column
     */
    public function test_list_all_returns_all_categories(): void
    {
        Category::factory()->count(5)->create();

        $response = $this->listAll(Category::class);

        $data = $response->getData(true);
        $this->assertCount(5, $data['data']);
    }

    /**
     * Test listAll respects custom orderBy column
     */
    public function test_list_all_respects_custom_order_by(): void
    {
        Category::factory()->create(['category' => 'ZZZ']);
        Category::factory()->create(['category' => 'AAA']);

        $response = $this->listAll(Category::class, 'category');

        $data = $response->getData(true);
        $this->assertEquals('AAA', $data['data'][0]['category']);
        $this->assertEquals('ZZZ', $data['data'][1]['category']);
    }

    /**
     * Test listAll works with Flower model
     */
    public function test_list_all_works_with_flower_model(): void
    {
        $response = $this->listAll(\App\Models\Flower::class, 'name');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test listAll returns empty array when no records exist
     */
    public function test_list_all_returns_empty_when_no_records(): void
    {
        $response = $this->listAll(Category::class);

        $data = $response->getData(true);
        $this->assertIsArray($data['data']);
        $this->assertEmpty($data['data']);
    }
}
