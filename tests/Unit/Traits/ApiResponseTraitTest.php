<?php

namespace Tests\Unit\Traits;

use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class ApiResponseTraitTest extends TestCase
{
    use ApiResponse;

    public function test_success_returns_200_with_data(): void
    {
        $response = $this->success(['key' => 'value']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals(['key' => 'value'], $data['data']);
        $this->assertArrayNotHasKey('message', $data);
    }

    public function test_success_with_message(): void
    {
        $response = $this->success(['key' => 'value'], '操作成功');

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('操作成功', $data['message']);
    }

    public function test_success_with_custom_status_code(): void
    {
        $response = $this->success(['key' => 'value'], null, 201);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_success_with_null_data(): void
    {
        $response = $this->success(null);

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertNull($data['data']);
    }

    public function test_error_returns_400_by_default(): void
    {
        $response = $this->error('出错了');

        $this->assertEquals(400, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('出错了', $data['message']);
    }

    public function test_error_with_custom_status_code(): void
    {
        $response = $this->error('禁止访问', 403);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_created_returns_201(): void
    {
        $response = $this->created(['id' => 1]);

        $this->assertEquals(201, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals(['id' => 1], $data['data']);
    }

    public function test_created_with_message(): void
    {
        $response = $this->created(['id' => 1], '创建成功');

        $data = $response->getData(true);
        $this->assertEquals('创建成功', $data['message']);
    }

    public function test_deleted_returns_200_with_default_message(): void
    {
        $response = $this->deleted();

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('删除成功', $data['message']);
        $this->assertArrayHasKey('data', $data);
        $this->assertNull($data['data']);
    }

    public function test_deleted_with_custom_message(): void
    {
        $response = $this->deleted('记录已删除');

        $data = $response->getData(true);
        $this->assertEquals('记录已删除', $data['message']);
    }
}
