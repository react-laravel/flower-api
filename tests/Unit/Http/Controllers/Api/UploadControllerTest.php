<?php

namespace Tests\Unit\Http\Controllers\Api;

use Tests\TestCase;

class UploadControllerTest extends TestCase
{
    // TODO: implement tests

    /**
     * @covers UploadController::upload
     */
    public function test_upload_requires_admin_permission(): void
    {
        // TODO: implement
    }

    /**
     * @covers UploadController::upload
     */
    public function test_upload_accepts_valid_image_file(): void
    {
        // TODO: implement
    }

    /**
     * @covers UploadController::upload
     */
    public function test_upload_rejects_non_image_files(): void
    {
        // TODO: implement
    }

    /**
     * @covers UploadController::upload
     */
    public function test_upload_rejects_files_larger_than_5mb(): void
    {
        // TODO: implement
    }

    /**
     * @covers UploadController::upload
     */
    public function test_upload_rejects_invalid_mime_types(): void
    {
        // TODO: implement
    }

    /**
     * @covers UploadController::upload
     */
    public function test_upload_returns_url_and_path(): void
    {
        // TODO: implement
    }

    /**
     * @covers UploadController::upload
     */
    public function test_upload_is_idempotent(): void
    {
        // TODO: implement
    }

    /**
     * @covers UploadController::delete
     */
    public function test_delete_requires_admin_permission(): void
    {
        // TODO: implement
    }

    /**
     * @covers UploadController::delete
     */
    public function test_delete_removes_existing_file(): void
    {
        // TODO: implement
    }

    /**
     * @covers UploadController::delete
     */
    public function test_delete_requires_path_field(): void
    {
        // TODO: implement
    }

    /**
     * @covers UploadController::delete
     */
    public function test_delete_rejects_path_with_traversal_sequence(): void
    {
        // TODO: implement
    }

    /**
     * @covers UploadController::delete
     */
    public function test_delete_rejects_path_not_starting_with_uploads(): void
    {
        // TODO: implement
    }

    /**
     * @covers UploadController::delete
     */
    public function test_delete_returns_404_for_nonexistent_file(): void
    {
        // TODO: implement
    }
}
