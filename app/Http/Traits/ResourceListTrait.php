<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

/**
 * Trait for controllers that need simple list-all operations.
 * Eliminates duplicated `Model::orderBy($orderBy)->get()` + `$this->success()` pattern.
 */
trait ResourceListTrait
{
    /**
     * List all records of a model, ordered by the given column.
     *
     * @param string $modelClass Fully-qualified model class name
     * @param string $orderBy Column to order by (default: 'category')
     */
    protected function listAll(string $modelClass, string $orderBy = 'category'): JsonResponse
    {
        /** @var Model $instance */
        $instance = new $modelClass();

        $items = $modelClass::orderBy($orderBy)->get();

        return $this->success($items);
    }
}
