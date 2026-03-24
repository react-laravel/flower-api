<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFlowerRequest;
use App\Http\Requests\UpdateFlowerRequest;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\Idempotency;
use App\Models\Flower;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FlowerController extends Controller
{
    use ApiResponse, Idempotency;

    private const DEFAULT_PER_PAGE = 20;
    private const MAX_PER_PAGE = 100;

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', self::DEFAULT_PER_PAGE), self::MAX_PER_PAGE);

        $flowers = Flower::query()
            ->with('user:id,name') // Fix N+1: eager load user relationship
            ->when($request->filled('category') && $request->category !== 'all',
                fn($q) => $q->where('category', $request->category))
            ->when($request->filled('featured'),
                fn($q) => $q->where('featured', $request->featured === 'true'))
            ->when($request->filled('search'),
                fn($q) => $q->where(fn($q) => $q
                    ->where('name', 'like', "%{$request->search}%")
                    ->orWhere('name_en', 'like', "%{$request->search}%")))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->success($flowers);
    }

    public function store(StoreFlowerRequest $request): JsonResponse
    {
        return $this->handleIdempotentRequest($request, function () use ($request) {
            $this->authorize('create', Flower::class);

            return DB::transaction(function () use ($request) {
                $flower = Flower::create($request->validated());
                return $this->created($flower);
            });
        });
    }

    public function show(int $id): JsonResponse
    {
        $flower = Flower::findOrFail($id);

        $this->authorize('view', $flower);

        return $this->success($flower);
    }

    public function update(UpdateFlowerRequest $request, int $id): JsonResponse
    {
        return $this->handleIdempotentRequest($request, function () use ($request, $id) {
            $flower = Flower::findOrFail($id);

            $this->authorize('update', $flower);

            return DB::transaction(function () use ($flower, $request) {
                $flower->update($request->validated());
                return $this->success($flower);
            });
        });
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        return $this->handleIdempotentRequest($request, function () use ($id) {
            $flower = Flower::findOrFail($id);

            $this->authorize('delete', $flower);

            return DB::transaction(function () use ($flower) {
                $flower->delete();
                return $this->deleted();
            });
        });
    }
}
