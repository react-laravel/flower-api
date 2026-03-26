<?php

namespace Tests\Feature\Idempotency;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Tests for upload idempotency:
 * - Duplicate upload requests with same key return cached response
 * - Upload without idempotency key works normally
 */
class UploadTest extends BaseReliabilityTest
{
    /**
     * Test that duplicate upload requests with same idempotency key return cached response.
     */
    public function test_upload_duplicate_request_returns_cached_response(): void
    {
        Storage::fake('public');

        $idempotencyKey = 'upload-idempotent-' . uniqid();
        $file = UploadedFile::fake()->image('flower.jpg', 800, 600);

        // First request - upload file
        $response1 = $this->adminRequest()->postJson('/api/upload', [
            'image' => $file,
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response1->assertStatus(200);
        $fileData1 = $response1->json('data');

        // Second request with same key - should return cached response
        $file2 = UploadedFile::fake()->image('flower2.jpg', 800, 600);
        $response2 = $this->adminRequest()->postJson('/api/upload', [
            'image' => $file2,
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response2->assertStatus(200);
        $response2->assertJson(['idempotent' => true]);
        $fileData2 = $response2->json('data');

        // Both should return the same file path (cached response)
        $this->assertEquals($fileData1['path'], $fileData2['path']);
    }

    /**
     * Test that upload without idempotency key works normally.
     */
    public function test_upload_without_idempotency_key_works_normally(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('flower.jpg', 800, 600);

        $response = $this->adminRequest()->postJson('/api/upload', [
            'image' => $file,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }
}
