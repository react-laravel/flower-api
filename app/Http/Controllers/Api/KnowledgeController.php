<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\CrudOperations;
use App\Http\Traits\Idempotency;
use App\Http\Traits\ResourceListTrait;
use App\Models\Knowledge;
use Illuminate\Http\JsonResponse;

class KnowledgeController extends Controller
{
    use ApiResponse, Idempotency, CrudOperations, ResourceListTrait;

    public function index(): JsonResponse
    {
        return $this->listAll(Knowledge::class, 'category');
    }

    public function show(int $id): JsonResponse
    {
        $knowledge = Knowledge::findOrFail($id);
        $this->authorize('view', $knowledge);
        return $this->success($knowledge);
    }

    protected static function getModelClass(): string
    {
        return Knowledge::class;
    }
}
