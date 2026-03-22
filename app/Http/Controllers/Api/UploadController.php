<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    use ApiResponse;

    public function upload(Request $request): JsonResponse
    {
        if (!Gate::allows('upload')) {
            return $this->error('需要管理员权限', 403);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $file = $request->file('image');

        // Generate unique filename
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        // Store in public/uploads directory
        $path = $file->storeAs('uploads', $filename, 'public');

        // Return the URL
        $url = Storage::url($path);

        return $this->success([
            'url' => $url,
            'path' => $path,
        ]);
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

        // Must start with uploads/ and contain no path traversal sequences
        if (!str_starts_with($rawPath, 'uploads/')
            || str_contains($rawPath, '..')
            || str_contains($rawPath, '~')) {
            return $this->error('无效的文件路径', 400);
        }

        // Verify file exists before attempting delete to give meaningful feedback
        if (!Storage::disk('public')->exists($rawPath)) {
            return $this->error('文件不存在', 404);
        }

        Storage::disk('public')->delete($rawPath);

        return $this->success(null, '删除成功');
    }
}
