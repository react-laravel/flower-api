<?php

namespace Tests\Unit\Http\Traits;

use Tests\TestCase;

class ResourceControllerTest extends TestCase
{
    // TODO: implement tests
    // Note: Trait methods are protected (getModel/findOrFail) and public (show/update/destroy).
    // Test via a concrete controller that uses this trait.

    /**
     * @covers ResourceController::getModel
     */
    public function test_get_model_returns_fresh_instance(): void
    {
        // TODO: implement
    }

    /**
     * @covers ResourceController::findOrFail
     */
    public function test_find_or_fail_returns_model_by_id(): void
    {
        // TODO: implement
    }

    /**
     * @covers ResourceController::findOrFail
     */
    public function test_find_or_fail_throws_for_nonexistent_id(): void
    {
        // TODO: implement
    }

    /**
     * @covers ResourceController::show
     */
    public function test_show_returns_model_as_success_response(): void
    {
        // TODO: implement
    }

    /**
     * @covers ResourceController::show
     */
    public function test_show_returns_404_when_model_not_found(): void
    {
        // TODO: implement
    }

    /**
     * @covers ResourceController::update
     */
    public function test_update_modifies_model_with_request_data(): void
    {
        // TODO: implement
    }

    /**
     * @covers ResourceController::update
     */
    public function test_update_returns_updated_model(): void
    {
        // TODO: implement
    }

    /**
     * @covers ResourceController::update
     */
    public function test_update_returns_404_when_model_not_found(): void
    {
        // TODO: implement
    }

    /**
     * @covers ResourceController::destroy
     */
    public function test_destroy_deletes_model(): void
    {
        // TODO: implement
    }

    /**
     * @covers ResourceController::destroy
     */
    public function test_destroy_returns_deleted_response(): void
    {
        // TODO: implement
    }

    /**
     * @covers ResourceController::destroy
     */
    public function test_destroy_returns_404_when_model_not_found(): void
    {
        // TODO: implement
    }
}
