<?php

namespace Tests\Unit\Http\Traits;

use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    // TODO: implement tests
    // Note: Trait methods are protected; test via a controller or test-double class.

    /**
     * @covers ApiResponse::success
     */
    public function test_success_returns_200_with_data(): void
    {
        // TODO: implement
    }

    /**
     * @covers ApiResponse::success
     */
    public function test_success_includes_message_when_provided(): void
    {
        // TODO: implement
    }

    /**
     * @covers ApiResponse::success
     */
    public function test_success_always_includes_data_key(): void
    {
        // TODO: implement
    }

    /**
     * @covers ApiResponse::success
     */
    public function test_success_respects_custom_status_code(): void
    {
        // TODO: implement
    }

    /**
     * @covers ApiResponse::error
     */
    public function test_error_returns_400_by_default(): void
    {
        // TODO: implement
    }

    /**
     * @covers ApiResponse::error
     */
    public function test_error_respects_custom_status_code(): void
    {
        // TODO: implement
    }

    /**
     * @covers ApiResponse::error
     */
    public function test_error_contains_success_false(): void
    {
        // TODO: implement
    }

    /**
     * @covers ApiResponse::created
     */
    public function test_created_returns_201_with_data(): void
    {
        // TODO: implement
    }

    /**
     * @covers ApiResponse::created
     */
    public function test_created_includes_message_when_provided(): void
    {
        // TODO: implement
    }

    /**
     * @covers ApiResponse::deleted
     */
    public function test_deleted_returns_200_with_default_message(): void
    {
        // TODO: implement
    }

    /**
     * @covers ApiResponse::deleted
     */
    public function test_deleted_respects_custom_message(): void
    {
        // TODO: implement
    }
}
