<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKnowledgeRequest;
use App\Http\Requests\UpdateKnowledgeRequest;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\PaginatedIndex;
use App\Http\Traits\ReliableOperations;
use App\Http\Traits\ResourceController;
use App\Models\Knowledge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Knowledge controller with DRY-optimized CRUD via ResourceController trait.
 * Includes reliability features: idempotency, distributed locking, transactions.
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
        // Check idempotency - return cached response if duplicate
        $idempotencyCheck = $this->checkIdempotency($request);
        if ($idempotencyCheck !== null) {
            return $idempotencyCheck;
        }

        try {
            $response = $this->withTransaction(function () use ($request) {
                $knowledge = Knowledge::create($request->validated());

                Log::info("KnowledgeController: Created knowledge", ['id' => $knowledge->id]);

                return $this->created($knowledge);
            });

            // Mark idempotency key as processed
            $this->markIdempotencyProcessed($request, $response);

            return $response;
        } catch (\Exception $e) {
            Log::error("KnowledgeController: Failed to create knowledge", [
                'error' => $e->getMessage(),
            ]);
            return $this->error('创建失败：' . $e->getMessage(), 500);
        }
    }

    // show(), update(), destroy() are provided by ResourceController trait
}
