<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\IdempotencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests for Upload idempotency.
 * Verifies that duplicate upload requests with the same idempotency key
 * return cached responses, and that concurrent locked requests return 409.
 */
class IdempotencyUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->adminUser = User::factory()->create(['is_admin' => true]);
        $this->adminToken = $this->adminUser->createToken('admin')->plainTextToken;
    }

    /**
     * Helper to make authenticated requests with admin Bearer token.
     */
    protected function adminRequest(): static
    {
        return $this->withHeader('Authorization', "Bearer {$this->adminToken}");
    }

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

    /**
     * Test that concurrent locked upload request returns 409.
     */
    public function test_upload_concurrent_locked_request_returns_409(): void
    {
        Storage::fake('public');

        $idempotencyKey = 'upload-concurrent-' . uniqid();
        $idempotencyService = new IdempotencyService();
        $idempotencyService->acquireLock($idempotencyKey, 30);

        $file = UploadedFile::fake()->image('flower.jpg', 800, 600);

        $response = $this->adminRequest()->postJson('/api/upload', [
            'image' => $file,
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response->assertStatus(409);

        $idempotencyService->releaseLock($idempotencyKey);
    }
}
