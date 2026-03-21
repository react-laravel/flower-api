<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFlowerRequest;
use App\Http\Requests\UpdateFlowerRequest;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\PaginatedIndex;
use App\Models\Flower;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlowerController extends Controller
{
    use ApiResponse, PaginatedIndex;

    public function index(Request $request): JsonResponse
    {
        return $this->paginatedIndex(Flower::query()->orderBy('created_at', 'desc'), $request);
    }

    protected function applyFilters(Builder $query, Request $request): Builder
    {
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->has('featured')) {
            $query->where('featured', $request->featured === 'true');
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('name_en', 'like', "%{$request->search}%");
            });
        }

        return $query;
    }

    public function store(StoreFlowerRequest $request): JsonResponse
    {
        $flower = Flower::create($request->validated());

        return $this->created($flower);
    }

    public function show(int $id): JsonResponse
    {
        $flower = Flower::findOrFail($id);

        return $this->success($flower);
    }

    public function update(UpdateFlowerRequest $request, int $id): JsonResponse
    {
        $flower = Flower::findOrFail($id);
        $flower->update($request->validated());

        return $this->success($flower);
    }

    public function destroy(int $id): JsonResponse
    {
        $flower = Flower::findOrFail($id);
        $flower->delete();

        return $this->deleted();
    }
}
