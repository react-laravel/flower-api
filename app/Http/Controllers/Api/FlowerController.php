<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFlowerRequest;
use App\Http\Requests\UpdateFlowerRequest;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\Idempotency;
use App\Models\Flower;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class FlowerController extends Controller
{
    use ApiResponse, Idempotency;

    public function index(Request $request): JsonResponse
    {
        $flowers = Flower::query()
            ->when($request->filled('category') && $request->category !== 'all',
                fn($q) => $q->where('category', $request->category))
            ->when($request->filled('featured'),
                fn($q) => $q->where('featured', $request->featured === 'true'))
            ->when($request->filled('search'),
                fn($q) => $q->where(fn($q) => $q
                    ->where('name', 'like', "%{$request->search}%")
                    ->orWhere('name_en', 'like', "%{$request->search}%")))
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($flowers);
    }

    public function store(StoreFlowerRequest $request): JsonResponse
    {
        return $this->handleIdempotentRequest($request, function () use ($request) {
            Gate::authorize('create', Flower::class);

            return DB::transaction(function () use ($request) {
                $flower = Flower::create($request->validated());
                return $this->created($flower);
            });
        });
    }

    public function show(int $id): JsonResponse
    {
        $flower = Flower::findOrFail($id);

        Gate::authorize('view', $flower);

        return $this->success($flower);
    }

    public function update(UpdateFlowerRequest $request, int $id): JsonResponse
    {
        return $this->handleIdempotentRequest($request, function () use ($request, $id) {
            $flower = Flower::findOrFail($id);

            Gate::authorize('update', $flower);

            return DB::transaction(function () use ($flower, $request) {
                $flower->update($request->validated());
                return $this->success($flower);
            });
        });
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->handleIdempotentRequest(request(), function () use ($id) {
            $flower = Flower::findOrFail($id);

            Gate::authorize('delete', $flower);

            return DB::transaction(function () use ($flower) {
                $flower->delete();
                return $this->deleted();
            });
        });
    }
}
