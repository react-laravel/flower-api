<?php

namespace Tests\Feature\Http\Traits;

use Tests\TestCase;
use Illuminate\Http\JsonResponse;

/**
 * Test double for ApiResponse trait - exposes protected methods as public.
 */
class TestableApiResponseController
{
    use \App\Http\Traits\ApiResponse;

    public function publicSuccess(mixed $data = null, ?string $message = null, int $statusCode = 200): JsonResponse
    {
        return $this->success($data, $message, $statusCode);
    }

    public function publicError(string $message, int $statusCode = 400): JsonResponse
    {
        return $this->error($message, $statusCode);
    }

    public function publicCreated(mixed $data = null, ?string $message = null): JsonResponse
    {
        return $this->created($data, $message);
    }

    public function publicDeleted(?string $message = '删除成功'): JsonResponse
    {
        return $this->deleted($message);
    }
}

class ApiResponseTest extends TestCase
{
    private TestableApiResponseController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TestableApiResponseController();
    }

    public function test_success_returns_200_with_data(): void
    {
        $data = ['key' => 'value'];
        $response = $this->controller->publicSuccess($data);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals($data, $content['data']);
        $this->assertArrayNotHasKey('message', $content);
    }

    public function test_success_with_message_includes_message(): void
    {
        $data = ['key' => 'value'];
        $message = 'Operation completed';
        $response = $this->controller->publicSuccess($data, $message);

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals($data, $content['data']);
        $this->assertEquals($message, $content['message']);
    }

    public function test_success_with_custom_status_code(): void
    {
        $response = $this->controller->publicSuccess(null, null, 201);

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_error_returns_json_with_success_false(): void
    {
        $message = 'Something went wrong';
        $response = $this->controller->publicError($message);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals($message, $content['message']);
    }

    public function test_error_with_custom_status_code(): void
    {
        $response = $this->controller->publicError('Not found', 404);

        $this->assertEquals(404, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
    }

    public function test_created_returns_201_status(): void
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $response = $this->controller->publicCreated($data);

        $this->assertEquals(201, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals($data, $content['data']);
    }

    public function test_created_with_message(): void
    {
        $response = $this->controller->publicCreated(['id' => 1], 'Resource created');

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals('Resource created', $content['message']);
    }

    public function test_deleted_returns_200_with_default_message(): void
    {
        $response = $this->controller->publicDeleted();

        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals('删除成功', $content['message']);
        $this->assertArrayNotHasKey('data', $content);
    }

    public function test_deleted_with_custom_message(): void
    {
        $response = $this->controller->publicDeleted('Record removed');

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Record removed', $content['message']);
    }
}
