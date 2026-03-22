<?php

namespace Tests\Unit\Traits;

use App\Http\Traits\ResourceController;
use App\Models\Flower;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class ResourceControllerTest extends TestCase
{
    use ResourceController;

    protected static function getModelClass(): string
    {
        return Flower::class;
    }

    /**
     * Test getModel returns new model instance
     */
    public function test_get_model_returns_new_instance(): void
    {
        $model = $this->getModel();

        $this->assertInstanceOf(Flower::class, $model);
    }

    /**
     * Test findOrFail throws exception for non-existent model
     */
    public function test_find_or_fail_throws_for_non_existent(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->findOrFail(999999);
    }

    /**
     * Test show returns correct structure for existing model
     */
    public function test_show_returns_correct_structure(): void
    {
        // This test requires a flower to exist in the database
        $flower = Flower::factory()->create();

        $response = $this->show($flower->id);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    /**
     * Test show returns 404 for non-existent model
     */
    public function test_show_returns_404_for_non_existent(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->show(999999);
    }

    /**
     * Test update modifies model and returns success
     */
    public function test_update_modifies_model_and_returns_success(): void
    {
        $flower = Flower::factory()->create(['name' => 'Original Name']);
        $request = Request::create('/flowers/' . $flower->id, 'PUT', ['name' => 'Updated Name']);

        $response = $this->update($request, $flower->id);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);

        $flower->refresh();
        $this->assertEquals('Updated Name', $flower->name);
    }

    /**
     * Test update returns 404 for non-existent model
     */
    public function test_update_returns_404_for_non_existent(): void
    {
        $request = Request::create('/flowers/999999', 'PUT', ['name' => 'Test']);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->update($request, 999999);
    }

    /**
     * Test destroy deletes model and returns success
     */
    public function test_destroy_deletes_model_and_returns_success(): void
    {
        $flower = Flower::factory()->create();
        $id = $flower->id;

        $response = $this->destroy($id);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('删除成功', $data['message']);

        $this->assertNull(Flower::find($id));
    }

    /**
     * Test destroy returns 404 for non-existent model
     */
    public function test_destroy_returns_404_for_non_existent(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->destroy(999999);
    }
}
