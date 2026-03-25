<?php

namespace Tests\Unit\Http\Controllers;

use Tests\TestCase;

class ControllerTest extends TestCase
{
    public function test_controller_is_abstract(): void
    {
        $reflection = new \ReflectionClass(\App\Http\Controllers\Controller::class);
        $this->assertTrue($reflection->isAbstract());
    }

    public function test_controller_uses_authorizes_requests_trait(): void
    {
        $traits = class_uses(\App\Http\Controllers\Controller::class);
        $this->assertContains(\Illuminate\Foundation\Auth\Access\AuthorizesRequests::class, $traits);
    }

    public function test_api_controllers_can_extend_base_controller(): void
    {
        // Api controllers (FlowerController, CategoryController, etc.) extend
        // App\Http\Controllers\Controller, which proves the base is extensible
        $this->assertTrue(
            is_subclass_of(\App\Http\Controllers\Api\FlowerController::class, \App\Http\Controllers\Controller::class),
            'FlowerController should extend App\Http\Controllers\Controller'
        );
    }
}
