<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFlowerRequest;
use App\Http\Requests\UpdateFlowerRequest;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\CrudOperations;
use App\Http\Traits\Idempotency;
use App\Models\Flower;
use App\ValueObjects\FlowerFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlowerController extends Controller
{
    use ApiResponse, Idempotency, CrudOperations;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
        ]);

        $filter = FlowerFilter::fromRequest($request);

        $flowers = Flower::query()
            ->with('user:id,name') // Fix N+1: eager load user relationship
            ->orderBy('created_at', 'desc')
            ->paginate($filter->perPage);

        // Apply filters using FlowerFilter value object (avoids DRY violation)
        $filter->apply($flowers->query());

        return $this->success($flowers);
    }

    public function show(int $id): JsonResponse
    {
        $flower = Flower::findOrFail($id);
        $this->authorize('view', $flower);
        return $this->success($flower);
    }

    protected static function getModelClass(): string
    {
        return Flower::class;
    }
}
