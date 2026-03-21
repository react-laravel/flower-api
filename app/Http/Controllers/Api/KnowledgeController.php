<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKnowledgeRequest;
use App\Http\Requests\UpdateKnowledgeRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Knowledge;
use Illuminate\Http\JsonResponse;

class KnowledgeController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $knowledge = Knowledge::orderBy('category')->get();

        return $this->success($knowledge);
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
