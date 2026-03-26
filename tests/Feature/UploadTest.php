<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        Storage::fake('public');
    }

    // ─── Upload ───────────────────────────────────────────────────────────────

    public function test_admin_can_upload_valid_image(): void
    {
        $file = UploadedFile::fake()->image('flower.jpg', 800, 600);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/upload', [
                'image' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => ['url', 'path'],
            ]);

        $this->assertStringContainsString('uploads/', $response->json('data.path'));
    }

    public function test_upload_validates_image_is_actually_an_image(): void
    {
        // NOTE: UploadedFile::fake()->create also generates a valid-looking file.
        // For stricter image validation testing, swap for a real small jpeg fixture.
        $file = UploadedFile::fake()->create('flower.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/upload', [
                'image' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_rejects_missing_image(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/upload', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_rejects_files_larger_than_5mb(): void
    {
        $file = UploadedFile::fake()->create('large.jpg', 6 * 1024); // 6 MB

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/upload', [
                'image' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_accepts_png_format(): void
    {
        $file = UploadedFile::fake()->image('flower.png', 400, 300);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/upload', [
                'image' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_upload_accepts_webp_format(): void
    {
        $file = UploadedFile::fake()->image('flower.webp', 400, 300);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/upload', [
                'image' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_upload_generates_unique_filenames(): void
    {
        $file1 = UploadedFile::fake()->image('flower.jpg', 400, 300);
        $file2 = UploadedFile::fake()->image('flower.jpg', 400, 300);

        $response1 = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/upload', ['image' => $file1]);

        $response2 = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/upload', ['image' => $file2]);

        $path1 = $response1->json('data.path');
        $path2 = $response2->json('data.path');

        $this->assertNotEquals($path1, $path2, 'Uploaded files should have unique filenames');
    }

    // ─── Delete Upload ───────────────────────────────────────────────────────

    public function test_admin_can_delete_uploaded_file(): void
    {
        // First upload a file
        $file = UploadedFile::fake()->image('to-delete.jpg', 400, 300);
        $uploadResponse = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/upload', ['image' => $file]);

        $path = $uploadResponse->json('data.path');

        // Then delete it
        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/upload', ['path' => $path]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '删除成功',
            ]);
    }

    public function test_delete_rejects_path_outside_uploads_directory(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/upload', ['path' => 'secrets/password.txt']);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => '无效的文件路径',
            ]);
    }

    public function test_delete_rejects_missing_path(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/upload', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['path']);
    }

    public function test_delete_rejects_non_string_path(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/upload', ['path' => 12345]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['path']);
    }

    public function test_delete_nonexistent_file_returns_success(): void
    {
        // Deleting a file that doesn't exist still returns 200 (idempotent)
        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/upload', ['path' => 'uploads/does-not-exist.jpg']);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // ─── Non-admin users ─────────────────────────────────────────────────────

    public function test_regular_user_cannot_upload(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $file = UploadedFile::fake()->image('flower.jpg', 400, 300);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/upload', ['image' => $file]);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_delete_upload(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/upload', ['path' => 'uploads/test.jpg']);

        $response->assertStatus(403);
    }
}
