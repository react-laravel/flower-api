<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKnowledgeRequest;
use App\Http\Requests\UpdateKnowledgeRequest;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\PaginatedIndex;
use App\Http\Traits\ResourceController;
use App\Models\Knowledge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Knowledge controller with DRY-optimized CRUD via ResourceController trait.
 */
class KnowledgeController extends Controller
{
    use ApiResponse, PaginatedIndex, ResourceController;

    protected static function getModelClass(): string
    {
        return Knowledge::class;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->paginatedIndex(
            Knowledge::query()->orderBy('category')->orderBy('id'),
            $request
        );
    }

    public function store(StoreKnowledgeRequest $request): JsonResponse
    {
        $knowledge = Knowledge::create($request->validated());

        return $this->created($knowledge);
    }

    // show(), update(), destroy() are provided by ResourceController trait
}
