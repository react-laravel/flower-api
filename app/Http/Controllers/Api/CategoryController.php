<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\ReliableOperations;
use App\Http\Traits\ResourceController;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Category controller with DRY-optimized CRUD via ResourceController trait.
 * Includes reliability features: idempotency, distributed locking, transactions.
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
        // Check idempotency - return cached response if duplicate
        $idempotencyCheck = $this->checkIdempotency($request);
        if ($idempotencyCheck !== null) {
            return $idempotencyCheck;
        }

        try {
            $response = $this->withTransaction(function () use ($request) {
                $category = Category::create($request->validated());

                Log::info("CategoryController: Created category", ['id' => $category->id]);

                return $this->created($category);
            });

            // Mark idempotency key as processed
            $this->markIdempotencyProcessed($request, $response);

            return $response;
        } catch (\Exception $e) {
            Log::error("CategoryController: Failed to create category", [
                'error' => $e->getMessage(),
            ]);
            return $this->error('创建失败：' . $e->getMessage(), 500);
        }
    }

    // show(), update(), destroy() are provided by ResourceController trait
}
