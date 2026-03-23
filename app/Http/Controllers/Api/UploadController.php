<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UploadController extends Controller
{
    use ApiResponse;

    private FileStorageService $fileStorage;

    public function __construct(FileStorageService $fileStorage)
    {
        $this->fileStorage = $fileStorage;
    }

    public function upload(Request $request): JsonResponse
    {
        if (!Gate::allows('upload')) {
            return $this->error('需要管理员权限', 403);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $file = $request->file('image');

        try {
            $result = $this->fileStorage->upload($file);
            return $this->success($result);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function delete(Request $request): JsonResponse
    {
        if (!Gate::allows('upload.delete')) {
            return $this->error('需要管理员权限', 403);
        }

        $request->validate([
            'path' => 'required|string|max:255',
        ]);

        $rawPath = urldecode($request->path);

        if (!str_starts_with($rawPath, 'uploads/')
            || str_contains($rawPath, '..')
            || str_contains($rawPath, '~')) {
            return $this->error('无效的文件路径', 400);
        }

        if (!$this->fileStorage->exists($rawPath)) {
            return $this->error('文件不存在', 404);
        }

        try {
            $this->fileStorage->delete($rawPath);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }

        return $this->success(null, '删除成功');
    }
}
