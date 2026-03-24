<?php

namespace Tests\Unit;

use App\Http\Traits\ApiResponse;
use Tests\TestCase;

class ApiResponseTraitTest extends TestCase
{
    private object $traitObject;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a concrete class that uses the trait for testing
        $this->traitObject = new class {
            use \App\Http\Traits\ApiResponse;
            public function exposeSuccess(mixed $data = null, ?string $message = null, int $statusCode = 200)
            {
                return $this->success($data, $message, $statusCode);
            }
            public function exposeError(string $message, int $statusCode = 400)
            {
                return $this->error($message, $statusCode);
            }
            public function exposeCreated(mixed $data = null, ?string $message = null)
            {
                return $this->created($data, $message);
            }
            public function exposeDeleted(?string $message = null)
            {
                return $this->deleted($message);
            }
        };
    }

    public function test_success_response_has_success_true(): void
    {
        $response = $this->traitObject->exposeSuccess(['key' => 'value']);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals(['key' => 'value'], $data['data']);
    }

    public function test_success_response_with_message(): void
    {
        $response = $this->traitObject->exposeSuccess(['key' => 'value'], 'Operation successful');
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Operation successful', $data['message']);
    }

    public function test_success_response_with_custom_status_code(): void
    {
        $response = $this->traitObject->exposeSuccess(null, null, 202);
        $this->assertEquals(202, $response->getStatusCode());
    }

    public function test_error_response_has_success_false(): void
    {
        $response = $this->traitObject->exposeError('Something went wrong', 400);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Something went wrong', $data['message']);
    }

    public function test_error_response_with_different_status_code(): void
    {
        $response = $this->traitObject->exposeError('Not found', 404);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_created_response_returns_201(): void
    {
        $response = $this->traitObject->exposeCreated(['id' => 1]);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals(['id' => 1], $data['data']);
    }

    public function test_created_response_with_message(): void
    {
        $response = $this->traitObject->exposeCreated(['id' => 1], 'Created successfully');
        $data = json_decode($response->getContent(), true);

        $this->assertEquals('Created successfully', $data['message']);
    }

    public function test_deleted_response_returns_200_with_default_message(): void
    {
        $response = $this->traitObject->exposeDeleted();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals('删除成功', $data['message']);
    }

    public function test_deleted_response_with_custom_message(): void
    {
        $response = $this->traitObject->exposeDeleted('Custom delete message');
        $data = json_decode($response->getContent(), true);

        $this->assertEquals('Custom delete message', $data['message']);
    }
}
