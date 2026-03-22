<?php

namespace App\ValueObjects;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Value Object representing flower filter criteria.
 * Fixes Long Parameter List violation by encapsulating filter parameters.
 */
class FlowerFilter
{
    public readonly ?string $category;
    public readonly ?bool $featured;
    public readonly ?string $search;
    public readonly int $perPage;

    private function __construct(?string $category, ?bool $featured, ?string $search, int $perPage)
    {
        $this->category = $category;
        $this->featured = $featured;
        $this->search = $search;
        $this->perPage = $perPage;
    }

    /**
     * Create FlowerFilter from HTTP request.
     */
    public static function fromRequest(Request $request): self
    {
        $category = $request->input('category');
        $featured = $request->has('featured')
            ? $request->input('featured') === 'true'
            : null;
        $search = $request->input('search');
        $perPage = min((int) $request->get('per_page', 20), 100);

        // Normalize 'all' category to null
        if ($category === 'all') {
            $category = null;
        }

        return new self($category, $featured, $search, $perPage);
    }

    /**
     * Apply filters to the query builder.
     */
    public function apply(Builder $query): Builder
    {
        if ($this->category !== null) {
            $query->where('category', $this->category);
        }

        if ($this->featured !== null) {
            $query->where('featured', $this->featured);
        }

        if ($this->search !== null && $this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('name_en', 'like', "%{$this->search}%");
            });
        }

        return $query;
    }

    /**
     * Check if any filters are active.
     */
    public function hasFilters(): bool
    {
        return $this->category !== null
            || $this->featured !== null
            || ($this->search !== null && $this->search !== '');
    }
}
