<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFlowerRequest;
use App\Http\Requests\UpdateFlowerRequest;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\CrudOperations;
use App\Http\Traits\Idempotency;
use App\Models\Flower;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlowerController extends Controller
{
    use ApiResponse, Idempotency, CrudOperations;

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 20), 100);

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
