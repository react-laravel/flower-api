<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\ResourceController;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 20), 100);

        $categories = Category::query()
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return $this->success([
            'data' => $categories->items(),
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return $this->created($category);
    }

    // show(), update(), destroy() are provided by ResourceController trait
}
