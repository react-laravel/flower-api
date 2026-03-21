<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Shared CRUD operations for resource controllers.
 * Fixes DRY violations by extracting common find-or-fail + operation patterns.
 * Includes reliability improvements: transaction support and distributed locking.
 */
trait ResourceController
{
    use ApiResponse;
    use ReliableOperations;

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
     * Update a resource with transaction and locking support.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $model = $this->findOrFail($id);
        $lockKey = $this->getResourceLockKey(static::getModelClass(), $id);

        try {
            return $this->lock()->withLock(
                $lockKey,
                function () use ($request, $model, $id) {
                    // Re-fetch to ensure latest data under lock
                    $model = $this->findOrFail($id);

                    return $this->withTransaction(function () use ($request, $model) {
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

                        Log::info("ResourceController: Updated model", [
                            'class' => static::getModelClass(),
                            'id' => $model->id,
                        ]);

                        return $this->success($model);
                    });
                }
            );
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            Log::warning("ResourceController: Lock timeout on update", [
                'class' => static::getModelClass(),
                'id' => $id,
            ]);
            return $this->error('操作太频繁，请稍后重试', 409);
        }
    }

    /**
     * Delete a resource with transaction and locking support.
     */
    public function destroy(int $id): JsonResponse
    {
        $lockKey = $this->getResourceLockKey(static::getModelClass(), $id);

        try {
            return $this->lock()->withLock(
                $lockKey,
                function () use ($id) {
                    $model = $this->findOrFail($id);

                    return $this->withTransaction(function () use ($model, $id) {
                        $model->delete();

                        Log::info("ResourceController: Deleted model", [
                            'class' => static::getModelClass(),
                            'id' => $id,
                        ]);

                        return $this->deleted();
                    });
                }
            );
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            Log::warning("ResourceController: Lock timeout on destroy", [
                'class' => static::getModelClass(),
                'id' => $id,
            ]);
            return $this->error('操作太频繁，请稍后重试', 409);
        }
    }
}
