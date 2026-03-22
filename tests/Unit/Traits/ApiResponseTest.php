<?php

namespace Tests\Unit\Traits;

use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    use ApiResponse;

    /**
     * @test
     */
    public function it_returns_success_response(): void
    {
        $response = $this->success(['name' => 'Flower API']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(['name' => 'Flower API'], $data['data']);
    }

    /**
     * @test
     */
    public function it_returns_success_response_with_message(): void
    {
        $response = $this->success(['name' => 'Flower API'], '操作成功');

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(['name' => 'Flower API'], $data['data']);
        $this->assertEquals('操作成功', $data['message']);
    }

    /**
     * @test
     */
    public function it_returns_success_response_with_custom_status_code(): void
    {
        $response = $this->success(['id' => 1], null, 201);

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_returns_error_response(): void
    {
        $response = $this->error('错误信息', 400);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('错误信息', $data['message']);
    }

    /**
     * @test
     */
    public function it_returns_error_response_with_default_status_code(): void
    {
        $response = $this->error('默认错误');

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_returns_created_response(): void
    {
        $response = $this->created(['id' => 1, 'name' => 'New Flower']);

        $this->assertEquals(201, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(['id' => 1, 'name' => 'New Flower'], $data['data']);
    }

    /**
     * @test
     */
    public function it_returns_created_response_with_message(): void
    {
        $response = $this->created(['id' => 1], '创建成功');

        $data = json_decode($response->getContent(), true);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('创建成功', $data['message']);
    }

    /**
     * @test
     */
    public function it_returns_deleted_response(): void
    {
        $response = $this->deleted();

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('删除成功', $data['message']);
    }

    /**
     * @test
     */
    public function it_returns_deleted_response_with_custom_message(): void
    {
        $response = $this->deleted('自定义删除消息');

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('自定义删除消息', $data['message']);
    }

    /**
     * @test
     */
    public function it_can_return_null_data_in_success(): void
    {
        $response = $this->success(null);

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayNotHasKey('data', $data);
    }
}
