<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UploadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_upload_accepts_valid_image(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('flower.jpg', 800, 600);

        $response = $this->postJson('/api/upload', ['image' => $file]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data' => ['url', 'path']]);
    }

    public function test_upload_accepts_various_image_types(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        foreach (['jpeg', 'png', 'jpg', 'gif', 'webp'] as $ext) {
            $file = UploadedFile::fake()->create("flower.$ext", 100, 'image/' . $ext);
            $response = $this->postJson('/api/upload', ['image' => $file]);
            $response->assertOk();
        }
    }

    public function test_upload_rejects_non_image_file(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/upload', ['image' => $file]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_rejects_files_larger_than_5mb(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('large-image.jpg', 6000, 'image/jpeg');

        $response = $this->postJson('/api/upload', ['image' => $file]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_requires_image_field(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/upload', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_requires_authentication(): void
    {
        $file = UploadedFile::fake()->image('flower.jpg');

        $response = $this->postJson('/api/upload', ['image' => $file]);

        $response->assertUnauthorized();
    }

    public function test_delete_removes_file(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // First upload a file to populate storage
        $file = UploadedFile::fake()->image('flower.jpg');
        $this->postJson('/api/upload', ['image' => $file]);

        $response = $this->deleteJson('/api/upload', ['path' => 'uploads/test.jpg']);

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => '删除成功']);
    }

    public function test_delete_rejects_invalid_path(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/upload', ['path' => '../../../etc/passwd']);

        $response->assertStatus(400)
            ->assertJson(['success' => false, 'message' => '无效的文件路径']);
    }

    public function test_delete_requires_path_field(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/upload', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['path']);
    }

    public function test_delete_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/upload', ['path' => 'uploads/test.jpg']);

        $response->assertUnauthorized();
    }
}
