<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKnowledgeRequest;
use App\Http\Requests\UpdateKnowledgeRequest;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\ResourceListTrait;
use App\Models\Knowledge;
use Illuminate\Http\JsonResponse;

class KnowledgeController extends Controller
{
    use ApiResponse, ResourceListTrait;

    public function index(): JsonResponse
    {
        return $this->listAll(Knowledge::class, 'category');
    }

    public function store(StoreKnowledgeRequest $request): JsonResponse
    {
        $this->authorize('create', Knowledge::class);

        $knowledge = Knowledge::create($request->validated());

        return $this->created($knowledge);
    }

    public function show(int $id): JsonResponse
    {
        $knowledge = Knowledge::findOrFail($id);

        $this->authorize('view', $knowledge);

        return $this->success($knowledge);
    }

    public function update(UpdateKnowledgeRequest $request, int $id): JsonResponse
    {
        $knowledge = Knowledge::findOrFail($id);

        $this->authorize('update', $knowledge);

        $knowledge->update($request->validated());

        return $this->success($knowledge);
    }

    public function destroy(int $id): JsonResponse
    {
        $knowledge = Knowledge::findOrFail($id);

        $this->authorize('delete', $knowledge);

        $knowledge->delete();

        return $this->deleted();
    }
}
