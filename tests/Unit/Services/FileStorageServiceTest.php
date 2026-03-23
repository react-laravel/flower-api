<?php

namespace Tests\Unit\Services;

use App\Services\FileStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FileStorageServiceTest extends TestCase
{
    private FileStorageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = new FileStorageService('public');
    }

    #[Test]
    public function test_upload_returns_url_and_path(): void
    {
        $file = UploadedFile::fake()->image(fake()->word() . '.jpg', 100, 100);

        $result = $this->service->upload($file);

        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('path', $result);
        $this->assertStringContainsString('uploads/', $result['path']);
    }

    #[Test]
    public function test_upload_creates_file_in_storage(): void
    {
        $file = UploadedFile::fake()->image(fake()->word() . '.jpg', 100, 100);

        $result = $this->service->upload($file);

        Storage::disk('public')->assertExists($result['path']);
    }

    #[Test]
    public function test_upload_with_invalid_mime_type_throws_exception(): void
    {
        $file = UploadedFile::fake()->create(fake()->word() . '.pdf', 100, 'application/pdf');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('不支持的文件类型');
        $this->service->upload($file);
    }

    #[Test]
    public function test_upload_with_file_too_large_throws_exception(): void
    {
        $file = UploadedFile::fake()->image(fake()->word() . '.jpg', 100, 100)->size(6000);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('文件大小超过限制');
        $this->service->upload($file);
    }

    #[Test]
    public function test_delete_removes_file_from_storage(): void
    {
        $file = UploadedFile::fake()->image(fake()->word() . '.jpg', 100, 100);
        $result = $this->service->upload($file);

        $this->service->delete($result['path']);

        Storage::disk('public')->assertMissing($result['path']);
    }

    #[Test]
    public function test_delete_with_invalid_path_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('无效的文件路径');
        $this->service->delete('invalid/path/file.jpg');
    }

    #[Test]
    public function test_exists_returns_true_for_existing_file(): void
    {
        $file = UploadedFile::fake()->image(fake()->word() . '.jpg', 100, 100);
        $result = $this->service->upload($file);

        $this->assertTrue($this->service->exists($result['path']));
    }

    #[Test]
    public function test_exists_returns_false_for_nonexistent_file(): void
    {
        $this->assertFalse($this->service->exists('nonexistent/path/' . fake()->word() . '.jpg'));
    }

    #[Test]
    public function test_path_returns_full_filesystem_path(): void
    {
        $file = UploadedFile::fake()->image(fake()->word() . '.jpg', 100, 100);
        $result = $this->service->upload($file);

        $path = $this->service->path($result['path']);

        $this->assertIsString($path);
        $this->assertStringContainsString($result['path'], $path);
    }
}
