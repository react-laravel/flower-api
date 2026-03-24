<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * File storage service handling file upload/delete business logic.
 * Extracted from UploadController to fix SRP and DIP violations.
 */
class FileStorageService
{
    private const UPLOAD_DIRECTORY = 'uploads';
    private const MAX_FILE_SIZE = 5120; // 5MB
    private const ALLOWED_MIME_TYPES = ['jpeg', 'png', 'jpg', 'gif', 'webp'];

    private string $disk;

    public function __construct(string $disk = 'public')
    {
        $this->disk = $disk;
    }

    /**
     * Upload an image file.
     *
     * @throws \InvalidArgumentException
     */
    public function upload(UploadedFile $file): array
    {
        $this->validateFile($file);

        $filename = $this->generateFilename($file);
        $path = $file->storeAs(self::UPLOAD_DIRECTORY, $filename, $this->disk);
        $url = Storage::disk($this->disk)->url($path);

        return [
            'url' => $url,
            'path' => $path,
        ];
    }

    /**
     * Delete a file by path.
     *
     * @throws \InvalidArgumentException
     */
    public function delete(string $path): void
    {
        $this->validatePath($path);

        Storage::disk($this->disk)->delete($path);
    }

    /**
     * Check if a file exists.
     */
    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    /**
     * Get the full path to a file.
     */
    public function path(string $path): string
    {
        return Storage::disk($this->disk)->path($path);
    }

    /**
     * Validate the uploaded file.
     *
     * @throws \InvalidArgumentException
     */
    private function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_FILE_SIZE * 1024) {
            throw new \InvalidArgumentException('文件大小超过限制（最大5MB）');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_MIME_TYPES)) {
            throw new \InvalidArgumentException('不支持的文件类型');
        }
    }

    /**
     * Validate the file path for security.
     * Checks for proper directory prefix and path traversal attacks.
     *
     * @throws \InvalidArgumentException
     */
    public function validatePath(string $path): void
    {
        if (!str_starts_with($path, self::UPLOAD_DIRECTORY . '/')) {
            throw new \InvalidArgumentException('无效的文件路径');
        }

        if (str_contains($path, '..') || str_contains($path, '~')) {
            throw new \InvalidArgumentException('无效的文件路径');
        }
    }

    /**
     * Generate a unique filename.
     */
    private function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return time() . '_' . Str::random(10) . '.' . $extension;
    }
}
