<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFlowerRequest;
use App\Http\Requests\UpdateFlowerRequest;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\PaginatedIndex;
use App\Http\Traits\ResourceController;
use App\Models\Flower;
use App\ValueObjects\FlowerFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Flower controller with DRY-optimized CRUD via ResourceController trait.
 */
class FlowerController extends Controller
{
    use ApiResponse, PaginatedIndex, ResourceController;

    protected static function getModelClass(): string
    {
        return Flower::class;
    }

    public function index(Request $request): JsonResponse
    {
        $filter = FlowerFilter::fromRequest($request);

        return $this->paginatedIndexWithFilter(
            Flower::query()->orderBy('created_at', 'desc'),
            $filter
        );
    }

    public function store(StoreFlowerRequest $request): JsonResponse
    {
        $flower = Flower::create($request->validated());

        return $this->created($flower);
    }

    // show(), update(), destroy() are provided by ResourceController trait
}
