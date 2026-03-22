<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $categories = Category::orderBy('name')->get();

        return $this->success($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $category = Category::create($request->validated());

        return $this->created($category);
    }

    public function show(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $this->authorize('view', $category);

        return $this->success($category);
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $this->authorize('update', $category);

        $category->update($request->validated());

        return $this->success($category);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $this->authorize('delete', $category);

        $category->delete();

        return $this->deleted();
    }
}
