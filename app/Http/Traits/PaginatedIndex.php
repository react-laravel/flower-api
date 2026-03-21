<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Shared pagination logic for resource controllers.
 */
trait PaginatedIndex
{
    /**
     * Apply filters from the request to the query.
     * Override in controllers that need specific filters.
     */
    protected function applyFilters(Builder $query, Request $request): Builder
    {
        return $query;
    }

    /**
     * Build a paginated response from a base query.
     *
     * @return JsonResponse data array: [items, total, current_page, last_page, per_page]
     */
    protected function paginatedIndex(Builder $query, Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 20), 100);

        $query = $this->applyFilters($query, $request);

        $results = $query->paginate($perPage);

        return $this->success([
            'items' => $results->items(),
            'total' => $results->total(),
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'per_page' => $results->perPage(),
        ]);
    }
}
