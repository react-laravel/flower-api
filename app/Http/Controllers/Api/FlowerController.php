<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFlowerRequest;
use App\Http\Requests\UpdateFlowerRequest;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\PaginatedIndex;
use App\Http\Traits\ReliableOperations;
use App\Http\Traits\ResourceController;
use App\Models\Flower;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Flower controller with DRY-optimized CRUD via ResourceController trait.
 * Includes reliability features: idempotency, distributed locking, transactions.
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
        $filter = \App\ValueObjects\FlowerFilter::fromRequest($request);

        return $this->paginatedIndexWithFilter(
            Flower::query()->orderBy('created_at', 'desc'),
            $filter
        );
    }

    public function store(StoreFlowerRequest $request): JsonResponse
    {
        // Check idempotency - return cached response if duplicate
        $idempotencyCheck = $this->checkIdempotency($request);
        if ($idempotencyCheck !== null) {
            return $idempotencyCheck;
        }

        try {
            $response = $this->withTransaction(function () use ($request) {
                $flower = Flower::create($request->validated());

                Log::info("FlowerController: Created flower", ['id' => $flower->id]);

                return $this->created($flower);
            });

            // Mark idempotency key as processed
            $this->markIdempotencyProcessed($request, $response);

            return $response;
        } catch (\Exception $e) {
            Log::error("FlowerController: Failed to create flower", [
                'error' => $e->getMessage(),
            ]);
            return $this->error('创建失败：' . $e->getMessage(), 500);
        }
    }

    // show(), update(), destroy() are provided by ResourceController trait
}
