<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\CrudOperations;
use App\Http\Traits\Idempotency;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use ApiResponse, Idempotency, CrudOperations;

    public function index(): JsonResponse
    {
        $categories = Category::orderBy('name')->get();
        return $this->success($categories);
    }

    public function show(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $this->authorize('view', $category);
        return $this->success($category);
    }

    protected static function getModelClass(): string
    {
        return Category::class;
    }
}
