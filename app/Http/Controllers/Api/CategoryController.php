<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\Idempotency;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    use ApiResponse, Idempotency;

    public function index(): JsonResponse
    {
        $categories = Category::orderBy('name')->get();

        return $this->success($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        return $this->handleIdempotentRequest($request, function () use ($request) {
            Gate::authorize('create', Category::class);

            return DB::transaction(function () use ($request) {
                $category = Category::create($request->validated());
                return $this->created($category);
            });
        });
    }

    public function show(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        Gate::authorize('view', $category);

        return $this->success($category);
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        return $this->handleIdempotentRequest($request, function () use ($request, $id) {
            $category = Category::findOrFail($id);

            Gate::authorize('update', $category);

            return DB::transaction(function () use ($category, $request) {
                $category->update($request->validated());
                return $this->success($category);
            });
        });
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->handleIdempotentRequest(request(), function () use ($id) {
            $category = Category::findOrFail($id);

            Gate::authorize('delete', $category);

            return DB::transaction(function () use ($category) {
                $category->delete();
                return $this->deleted();
            });
        });
    }
}
