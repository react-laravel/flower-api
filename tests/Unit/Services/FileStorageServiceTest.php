<?php

namespace Tests\Unit\Services;

use App\Services\FileStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileStorageServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_upload_returns_url_and_path(): void
    {
        $file = UploadedFile::fake()->image('flower.jpg', 800, 600);
        $service = new FileStorageService('public');

        $result = $service->upload($file);

        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('path', $result);
        $this->assertNotEmpty($result['path']);
    }

    public function test_upload_stores_file_in_public_disk(): void
    {
        $file = UploadedFile::fake()->image('rose.jpg', 800, 600);
        $service = new FileStorageService('public');

        $result = $service->upload($file);

        Storage::disk('public')->assertExists($result['path']);
    }

    public function test_upload_rejects_file_exceeding_max_size(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('文件大小超过限制');

        $file = UploadedFile::fake()->create('large.jpg', 6000); // > 5MB
        $service = new FileStorageService('public');

        $service->upload($file);
    }

    public function test_upload_rejects_disallowed_extension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('不支持的文件类型');

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $service = new FileStorageService('public');

        $service->upload($file);
    }

    public function test_upload_accepts_png_format(): void
    {
        $file = UploadedFile::fake()->image('flower.png', 800, 600);
        $service = new FileStorageService('public');

        $result = $service->upload($file);

        $this->assertNotEmpty($result['path']);
    }

    public function test_upload_accepts_gif_format(): void
    {
        $file = UploadedFile::fake()->image('flower.gif', 800, 600);
        $service = new FileStorageService('public');

        $result = $service->upload($file);

        $this->assertNotEmpty($result['path']);
    }

    public function test_upload_accepts_webp_format(): void
    {
        $file = UploadedFile::fake()->image('flower.webp', 800, 600);
        $service = new FileStorageService('public');

        $result = $service->upload($file);

        $this->assertNotEmpty($result['path']);
    }

    public function test_delete_removes_existing_file(): void
    {
        Storage::disk('public')->put('uploads/test.jpg', 'content');
        $service = new FileStorageService('public');

        $service->delete('uploads/test.jpg');

        Storage::disk('public')->assertMissing('uploads/test.jpg');
    }

    public function test_delete_rejects_path_traversal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('无效的文件路径');

        $service = new FileStorageService('public');
        $service->delete('../etc/passwd');
    }

    public function test_delete_rejects_absolute_path(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('无效的文件路径');

        $service = new FileStorageService('public');
        $service->delete('/etc/passwd');
    }

    public function test_exists_returns_true_for_existing_file(): void
    {
        Storage::disk('public')->put('uploads/test.jpg', 'content');
        $service = new FileStorageService('public');

        $this->assertTrue($service->exists('uploads/test.jpg'));
    }

    public function test_exists_returns_false_for_missing_file(): void
    {
        $service = new FileStorageService('public');

        $this->assertFalse($service->exists('uploads/nonexistent.jpg'));
    }

    public function test_generate_filename_is_unique(): void
    {
        $file1 = UploadedFile::fake()->image('flower1.jpg', 800, 600);
        $file2 = UploadedFile::fake()->image('flower2.jpg', 800, 600);
        $service = new FileStorageService('public');

        $result1 = $service->upload($file1);
        $result2 = $service->upload($file2);

        $this->assertNotEquals($result1['path'], $result2['path']);
    }
}
