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
        $page = max((int) $request->get('page', 1), 1);

        $knowledge = Knowledge::query()
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->success([
            'data' => $knowledge->items(),
            'pagination' => [
                'current_page' => $knowledge->currentPage(),
                'last_page' => $knowledge->lastPage(),
                'per_page' => $knowledge->perPage(),
                'total' => $knowledge->total(),
            ],
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
