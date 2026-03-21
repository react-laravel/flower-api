<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Anyone can view categories (public read-only).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Anyone can view a single category (public read-only).
     */
    public function view(User $user, Category $category): bool
    {
        return true;
    }

    /**
     * Only admins can create categories.
     */
    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Only admins can update categories.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->is_admin;
    }

    /**
     * Only admins can delete categories.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->is_admin;
    }
}
