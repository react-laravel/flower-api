<?php

namespace Tests\Unit\Http\Middleware;

use Tests\TestCase;

class EnsureUserIsAdminTest extends TestCase
{
    // TODO: implement tests

    /**
     * @covers EnsureUserIsAdmin::handle
     */
    public function test_handle_allows_admin_user(): void
    {
        // TODO: implement
    }

    /**
     * @covers EnsureUserIsAdmin::handle
     */
    public function test_handle_blocks_non_admin_user(): void
    {
        // TODO: implement
    }

    /**
     * @covers EnsureUserIsAdmin::handle
     */
    public function test_handle_blocks_unauthenticated_request(): void
    {
        // TODO: implement
    }

    /**
     * @covers EnsureUserIsAdmin::handle
     */
    public function test_handle_returns_403_forbidden(): void
    {
        // TODO: implement
    }

    /**
     * @covers EnsureUserIsAdmin::handle
     */
    public function test_handle_returns_json_error_message(): void
    {
        // TODO: implement
    }
}
