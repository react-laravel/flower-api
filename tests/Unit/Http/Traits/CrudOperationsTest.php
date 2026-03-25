<?php

namespace Tests\Unit\Http\Traits;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\CrudOperations;
use App\Http\Traits\Idempotency;
use App\Models\Flower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Minimal concrete controller that uses CrudOperations for testing.
 */
class TestCrudOperationsController extends Controller
{
    use ApiResponse;
    use Idempotency;
    use CrudOperations;

    protected static function getModelClass(): string
    {
        return Flower::class;
    }

    // Expose protected static methods for testing
    public static function publicGetModelShortName(): string
    {
        return static::getModelShortName();
    }

    public static function publicGetStoreRequestClass(): string
    {
        return static::getStoreRequestClass();
    }

    public static function publicGetUpdateRequestClass(): string
    {
        return static::getUpdateRequestClass();
    }
}

class CrudOperationsTest extends TestCase
{
    use RefreshDatabase;

    private TestCrudOperationsController $controller;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new TestCrudOperationsController();

        // Create an admin user for authorization tests
        $this->adminUser = User::factory()->admin()->create();

        // Set the admin user as the "current user" for Gate authorization
        Auth::setUser($this->adminUser);
    }

    // ─── Static helper method tests ────────────────────────────────────────

    public function test_get_model_short_name_returns_flower(): void
    {
        $this->assertEquals('Flower', TestCrudOperationsController::publicGetModelShortName());
    }

    public function test_get_store_request_class_returns_store_flower_request(): void
    {
        $class = TestCrudOperationsController::publicGetStoreRequestClass();
        $this->assertStringContainsString('StoreFlowerRequest', $class);
        $this->assertEquals('App\\Http\\Requests\\StoreFlowerRequest', $class);
    }

    public function test_get_update_request_class_returns_update_flower_request(): void
    {
        $class = TestCrudOperationsController::publicGetUpdateRequestClass();
        $this->assertStringContainsString('UpdateFlowerRequest', $class);
        $this->assertEquals('App\\Http\\Requests\\UpdateFlowerRequest', $class);
    }

    // ─── store() tests ─────────────────────────────────────────────────────

    public function test_store_creates_flower_and_returns_201(): void
    {
        // Use withRequest(function) to simulate proper HTTP request context
        // that the FormRequest expects when resolved from the container
        $this->app['request']->replace([
            'name' => 'Red Rose',
            'name_en' => 'Red Rose',
            'category' => 'rose',
            'price' => 99.00,
            'original_price' => 129.00,
            'description' => 'A beautiful red rose',
            'meaning' => 'Love',
            'care' => 'Keep in water',
            'stock' => 50,
            'featured' => true,
            'user_id' => $this->adminUser->id,
        ]);

        $request = $this->app['request'];
        $request->setMethod('POST');

        $response = $this->controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals('Red Rose', $content['data']['name']);
        $this->assertDatabaseHas('flowers', ['name' => 'Red Rose']);
    }

    public function test_store_requires_authorization(): void
    {
        $nonAdmin = User::factory()->create(['is_admin' => false]);
        Auth::setUser($nonAdmin);

        $this->app['request']->replace([
            'name' => 'Red Rose',
            'category' => 'rose',
            'price' => 99.00,
            'stock' => 10,
        ]);

        $request = $this->app['request'];
        $request->setMethod('POST');

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->controller->store($request);
    }

    // ─── update() tests ────────────────────────────────────────────────────

    public function test_update_modifies_flower_and_returns_200(): void
    {
        $flower = Flower::factory()->create([
            'name' => 'Old Name',
            'user_id' => $this->adminUser->id,
        ]);

        $this->app['request']->replace([
            'name' => 'Updated Name',
            'category' => 'rose',
            'price' => 149.00,
            'stock' => 10,
        ]);

        $request = $this->app['request'];
        $request->setMethod('PUT');

        $response = $this->controller->update($request, $flower->id);

        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals('Updated Name', $content['data']['name']);

        $this->assertDatabaseHas('flowers', ['id' => $flower->id, 'name' => 'Updated Name']);
    }

    public function test_update_returns_404_for_missing_flower(): void
    {
        $this->app['request']->replace([
            'name' => 'Updated',
            'category' => 'rose',
            'price' => 100,
            'stock' => 10,
        ]);

        $request = $this->app['request'];
        $request->setMethod('PUT');

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->controller->update($request, 99999);
    }

    // ─── destroy() tests ───────────────────────────────────────────────────

    public function test_destroy_deletes_flower_and_returns_200(): void
    {
        $flower = Flower::factory()->create([
            'name' => 'To Be Deleted',
            'user_id' => $this->adminUser->id,
        ]);
        $flowerId = $flower->id;

        $this->app['request']->replace([]);
        $request = $this->app['request'];
        $request->setMethod('DELETE');

        $response = $this->controller->destroy($request, $flowerId);

        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertDatabaseMissing('flowers', ['id' => $flowerId]);
    }

    public function test_destroy_returns_404_for_missing_flower(): void
    {
        $this->app['request']->replace([]);
        $request = $this->app['request'];
        $request->setMethod('DELETE');

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->controller->destroy($request, 99999);
    }
}
