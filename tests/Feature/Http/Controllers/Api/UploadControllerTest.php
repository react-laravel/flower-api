<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function actingAsAdmin(): array
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('admin')->plainTextToken;
        return ['admin' => $admin, 'token' => $token];
    }

    public function test_upload_accepts_valid_image(): void
    {
        $auth = $this->actingAsAdmin();
        $file = UploadedFile::fake()->image('flower.jpg', 800, 600);

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/upload', ['image' => $file]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => ['url', 'path'],
            ]);
    }

    public function test_upload_rejects_non_image_files(): void
    {
        $auth = $this->actingAsAdmin();
        $file = UploadedFile::fake()->create('document.pdf');

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/upload', ['image' => $file]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_rejects_files_larger_than_5mb(): void
    {
        $auth = $this->actingAsAdmin();
        $file = UploadedFile::fake()->create('large.jpg')->size(6000); // 6MB

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/upload', ['image' => $file]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_requires_image_field(): void
    {
        $auth = $this->actingAsAdmin();

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/upload', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_delete_removes_uploaded_file(): void
    {
        $auth = $this->actingAsAdmin();

        // First upload a file
        $file = UploadedFile::fake()->image('flower.jpg');
        $uploadResponse = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/upload', ['image' => $file]);
        $path = $uploadResponse->json('data.path');

        // Then delete it
        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->deleteJson('/api/upload', ['path' => $path]);

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => '删除成功']);
    }

    public function test_delete_rejects_paths_outside_uploads(): void
    {
        $auth = $this->actingAsAdmin();

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->deleteJson('/api/upload', ['path' => '../etc/passwd']);

        $response->assertStatus(400)
            ->assertJson(['success' => false, 'message' => '无效的文件路径']);
    }

    public function test_upload_requires_authentication(): void
    {
        $file = UploadedFile::fake()->image('flower.jpg');

        $response = $this->postJson('/api/upload', ['image' => $file]);

        $response->assertUnauthorized();
    }
}
