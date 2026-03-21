<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\ResourceController;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

/**
 * Category controller with DRY-optimized CRUD via ResourceController trait.
 */
class CategoryController extends Controller
{
    use ApiResponse, ResourceController;

    protected static function getModelClass(): string
    {
        return Category::class;
    }

    public function index(): JsonResponse
    {
        $categories = Category::orderBy('name')->get();

        return $this->success($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return $this->created($category);
    }

    // show(), update(), destroy() are provided by ResourceController trait
}
