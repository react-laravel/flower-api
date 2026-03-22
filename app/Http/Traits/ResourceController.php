<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

/**
 * @deprecated Not used by any controller. Remove in a future cleanup.
 *             Common CRUD patterns can be implemented directly in base Controller or dedicated action classes.
 *
 * Shared CRUD operations for resource controllers.
 * Fixes DRY violations by extracting common find-or-fail + operation patterns.
 */
trait ResourceController
{
    use ApiResponse;

    /**
     * Get the model class name. Override in controllers.
     */
    abstract protected static function getModelClass(): string;

    /**
     * Get a fresh instance of the model.
     */
    protected function getModel(): Model
    {
        $class = static::getModelClass();
        return new $class();
    }

    /**
     * Find model by ID or fail.
     */
    protected function findOrFail(int $id): Model
    {
        $class = static::getModelClass();
        return $class::findOrFail($id);
    }

    /**
     * Show a single resource.
     */
    public function show(int $id): JsonResponse
    {
        $model = $this->findOrFail($id);
        return $this->success($model);
    }

    /**
     * Update a resource.
     */
    public function update(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $model = $this->findOrFail($id);

        // Dynamically resolve the Update form request and validate
        $modelShortName = (new \ReflectionClass(static::getModelClass()))->getShortName();
        $formRequestClass = "App\\Http\\Requests\\Update{$modelShortName}Request";

        if (class_exists($formRequestClass)) {
            $formRequest = app($formRequestClass);
            $validated = $formRequest->validated();
        } else {
            $validated = $request->all();
        }

        $model->update($validated);
        return $this->success($model);
    }

    /**
     * Delete a resource.
     */
    public function destroy(int $id): JsonResponse
    {
        $model = $this->findOrFail($id);
        $model->delete();
        return $this->deleted();
    }
}
