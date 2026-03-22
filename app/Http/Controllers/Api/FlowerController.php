<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFlowerRequest;
use App\Http\Requests\UpdateFlowerRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Flower;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlowerController extends Controller
{
    use ApiResponse;

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
        $this->authorize('create', Flower::class);

        $flower = Flower::create($request->validated());

        return $this->created($flower);
    }

    public function show(int $id): JsonResponse
    {
        $flower = Flower::findOrFail($id);

        $this->authorize('view', $flower);

        return $this->success($flower);
    }

    public function update(UpdateFlowerRequest $request, int $id): JsonResponse
    {
        $flower = Flower::findOrFail($id);

        $this->authorize('update', $flower);

        $flower->update($request->validated());

        return $this->success($flower);
    }

    public function destroy(int $id): JsonResponse
    {
        $flower = Flower::findOrFail($id);

        $this->authorize('delete', $flower);

        $flower->delete();

        return $this->deleted();
    }
}
