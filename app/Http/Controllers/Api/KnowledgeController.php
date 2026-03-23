<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKnowledgeRequest;
use App\Http\Requests\UpdateKnowledgeRequest;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\Idempotency;
use App\Http\Traits\ResourceListTrait;
use App\Models\Knowledge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KnowledgeController extends Controller
{
    use ApiResponse, ResourceListTrait, Idempotency;

    public function index(): JsonResponse
    {
        return $this->listAll(Knowledge::class, 'category');
    }

    public function store(StoreKnowledgeRequest $request): JsonResponse
    {
        return $this->handleIdempotentRequest($request, function () use ($request) {
            $this->authorize('create', Knowledge::class);

            return DB::transaction(function () use ($request) {
                $knowledge = Knowledge::create($request->validated());
                return $this->created($knowledge);
            });
        });
    }

    public function show(int $id): JsonResponse
    {
        $knowledge = Knowledge::findOrFail($id);

        $this->authorize('view', $knowledge);

        return $this->success($knowledge);
    }

    public function update(UpdateKnowledgeRequest $request, int $id): JsonResponse
    {
        return $this->handleIdempotentRequest($request, function () use ($request, $id) {
            $knowledge = Knowledge::findOrFail($id);

            $this->authorize('update', $knowledge);

            return DB::transaction(function () use ($knowledge, $request) {
                $knowledge->update($request->validated());
                return $this->success($knowledge);
            });
        });
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        return $this->handleIdempotentRequest($request, function () use ($id) {
            $knowledge = Knowledge::findOrFail($id);

            $this->authorize('delete', $knowledge);

            return DB::transaction(function () use ($knowledge) {
                $knowledge->delete();
                return $this->deleted();
            });
        });
    }
}
