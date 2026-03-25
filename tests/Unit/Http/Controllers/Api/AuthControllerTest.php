<?php

namespace Tests\Unit\Http\Controllers\Api;

use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    // TODO: implement tests

    /**
     * @covers AuthController::login
     */
    public function test_login_with_valid_credentials_returns_user_and_token(): void
    {
        // TODO: implement
    }

    /**
     * @covers AuthController::login
     */
    public function test_login_with_invalid_credentials_returns_error(): void
    {
        // TODO: implement
    }

    /**
     * @covers AuthController::login
     */
    public function test_login_requires_email_and_password(): void
    {
        // TODO: implement
    }

    /**
     * @covers AuthController::register
     */
    public function test_register_creates_user_and_returns_token(): void
    {
        // TODO: implement
    }

    /**
     * @covers AuthController::register
     */
    public function test_register_requires_name_email_password(): void
    {
        // TODO: implement
    }

    /**
     * @covers AuthController::register
     */
    public function test_register_requires_password_confirmation(): void
    {
        // TODO: implement
    }

    /**
     * @covers AuthController::register
     */
    public function test_register_prevents_duplicate_email(): void
    {
        // TODO: implement
    }

    /**
     * @covers AuthController::register
     */
    public function test_register_is_idempotent(): void
    {
        // TODO: implement
    }

    /**
     * @covers AuthController::user
     */
    public function test_user_returns_authenticated_user(): void
    {
        // TODO: implement
    }

    /**
     * @covers AuthController::logout
     */
    public function test_logout_revokes_token(): void
    {
        // TODO: implement
    }

    /**
     * @covers AuthController::isAdmin
     */
    public function test_is_admin_returns_admin_status(): void
    {
        // TODO: implement
    }
}
