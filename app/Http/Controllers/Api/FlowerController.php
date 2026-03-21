<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFlowerRequest;
use App\Http\Requests\UpdateFlowerRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Flower;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlowerController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 20), 100);
        $page = max((int) $request->get('page', 1), 1);

        $query = Flower::query();

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->has('featured')) {
            $query->where('featured', $request->featured === 'true');
        }

        // Search with length limit to prevent ReDoS
        $search = mb_substr($request->get('search', ''), 0, 100);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%");
            });
        }

        $flowers = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        return $this->success([
            'data' => $flowers->items(),
            'pagination' => [
                'current_page' => $flowers->currentPage(),
                'last_page' => $flowers->lastPage(),
                'per_page' => $flowers->perPage(),
                'total' => $flowers->total(),
            ],
        ]);
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
