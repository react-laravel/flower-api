<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Shared CRUD operation helpers for resource controllers.
 * Fixes DRY violations across FlowerController, CategoryController, KnowledgeController.
 */
trait CrudOperations
{
    use ApiResponse;

    /**
     * Get the fully-qualified model class name.
     * Subclasses MUST implement: return ModelClass::class;
     */
    abstract protected static function getModelClass(): string;

    /**
     * Get the short name of the model (e.g. 'Flower').
     */
    protected static function getModelShortName(): string
    {
        $class = static::getModelClass();
        return (new \ReflectionClass($class))->getShortName();
    }

    /**
     * Get the form request class for store (e.g. StoreFlowerRequest).
     */
    protected static function getStoreRequestClass(): string
    {
        return "App\\Http\\Requests\\Store" . static::getModelShortName() . "Request";
    }

    /**
     * Get the form request class for update (e.g. UpdateFlowerRequest).
     */
    protected static function getUpdateRequestClass(): string
    {
        return "App\\Http\\Requests\\Update" . static::getModelShortName() . "Request";
    }

    /**
     * Store a new resource (requires Idempotency trait in the controller).
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleIdempotentRequest($request, function () use ($request) {
            $modelClass = static::getModelClass();
            $this->authorize('create', $modelClass);

            $requestClass = static::getStoreRequestClass();
            $validated = class_exists($requestClass)
                ? app($requestClass)->validated()
                : $request->validated();

            return DB::transaction(function () use ($modelClass, $validated) {
                $model = $modelClass::create($validated);
                return $this->created($model);
            });
        });
    }

    /**
     * Update an existing resource (requires Idempotency trait in the controller).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        return $this->handleIdempotentRequest($request, function () use ($request, $id) {
            $modelClass = static::getModelClass();
            $model = $modelClass::findOrFail($id);

            $this->authorize('update', $model);

            $requestClass = static::getUpdateRequestClass();
            $validated = class_exists($requestClass)
                ? app($requestClass)->validated()
                : $request->validated();

            return DB::transaction(function () use ($model, $validated) {
                $model->update($validated);
                return $this->success($model);
            });
        });
    }

    /**
     * Delete a resource (requires Idempotency trait in the controller).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        return $this->handleIdempotentRequest($request, function () use ($id) {
            $modelClass = static::getModelClass();
            $model = $modelClass::findOrFail($id);

            $this->authorize('delete', $model);

            return DB::transaction(function () use ($model) {
                $model->delete();
                return $this->deleted();
            });
        });
    }
}
