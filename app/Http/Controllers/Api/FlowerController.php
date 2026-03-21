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
        $query = Flower::query();

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

        $flowers = $query->orderBy('created_at', 'desc')->get();

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
