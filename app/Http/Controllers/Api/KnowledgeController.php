<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKnowledgeRequest;
use App\Http\Requests\UpdateKnowledgeRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Knowledge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 20), 100);
        $knowledge = Knowledge::orderBy('category')
            ->orderBy('id')
            ->paginate($perPage);

        return $this->success([
            'items' => $knowledge->items(),
            'total' => $knowledge->total(),
            'current_page' => $knowledge->currentPage(),
            'last_page' => $knowledge->lastPage(),
            'per_page' => $knowledge->perPage(),
        ]);
    }

    public function store(StoreKnowledgeRequest $request): JsonResponse
    {
        $knowledge = Knowledge::create($request->validated());

        return $this->created($knowledge);
    }

    public function show(int $id): JsonResponse
    {
        $knowledge = Knowledge::findOrFail($id);

        return $this->success($knowledge);
    }

    public function update(UpdateKnowledgeRequest $request, int $id): JsonResponse
    {
        $knowledge = Knowledge::findOrFail($id);
        $knowledge->update($request->validated());

        return $this->success($knowledge);
    }

    public function destroy(int $id): JsonResponse
    {
        $knowledge = Knowledge::findOrFail($id);
        $knowledge->delete();

        return $this->deleted();
    }
}
