<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'is_admin' => true,
        ]);
    }

    /**
     * @test
     */
    public function it_can_upload_an_image(): void
    {
        $file = UploadedFile::fake()->image('flower.jpg', 800, 600);

        $response = $this->actingAs($this->user)
            ->postJson('/api/upload', [
                'image' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['url', 'path'],
            ]);

        $this->assertTrue($response->json('success'));
        Storage::disk('public')->assertExists($response->json('data.path'));
    }

    /**
     * @test
     */
    public function it_accepts_various_image_types(): void
    {
        foreach (['jpeg', 'png', 'jpg', 'gif', 'webp'] as $extension) {
            $file = UploadedFile::fake()->image("flower.$extension");
            Storage::fake('public');

            $response = $this->actingAs($this->user)
                ->postJson('/api/upload', ['image' => $file]);

            $response->assertStatus(200);
        }
    }

    /**
     * @test
     */
    public function it_rejects_non_image_files(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->postJson('/api/upload', ['image' => $file]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    /**
     * @test
     */
    public function it_validates_required_image_field(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/upload', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    /**
     * @test
     */
    public function it_rejects_files_larger_than_5mb(): void
    {
        $file = UploadedFile::fake()->image('large.jpg')->size(6000);

        $response = $this->actingAs($this->user)
            ->postJson('/api/upload', ['image' => $file]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    /**
     * @test
     */
    public function it_can_delete_a_file(): void
    {
        Storage::disk('public')->put('uploads/test.jpg', 'content');

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/upload', ['path' => 'uploads/test.jpg']);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => '删除成功']);

        Storage::disk('public')->assertMissing('uploads/test.jpg');
    }

    /**
     * @test
     */
    public function it_rejects_deleting_files_outside_uploads_directory(): void
    {
        Storage::disk('public')->put('other/secret.jpg', 'secret content');

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/upload', ['path' => 'other/secret.jpg']);

        $response->assertStatus(400)
            ->assertJson(['success' => false, 'message' => '无效的文件路径']);

        Storage::disk('public')->assertExists('other/secret.jpg');
    }

    /**
     * @test
     */
    public function it_validates_required_path_on_delete(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/upload', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['path']);
    }

    /**
     * @test
     */
    public function it_returns_error_for_nonexistent_file_on_delete(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/upload', ['path' => 'uploads/nonexistent.jpg']);

        $response->assertStatus(200);
    }
}
