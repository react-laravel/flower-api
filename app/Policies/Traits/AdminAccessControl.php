<?php

namespace App\Policies\Traits;

use App\Models\User;

/**
 * Shared admin-only access control for policies.
 * Fixes DRY violation across FlowerPolicy, CategoryPolicy, KnowledgePolicy.
 */
trait AdminAccessControl
{
    /**
     * Anyone can view (public read-only).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Anyone can view a single resource (public read-only).
     */
    public function view(User $user, $model): bool
    {
        return true;
    }

    /**
     * Only admins can create.
     */
    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Only admins can update.
     */
    public function update(User $user, $model): bool
    {
        return $user->is_admin;
    }

    /**
     * Only admins can delete.
     */
    public function delete(User $user, $model): bool
    {
        return $user->is_admin;
    }
}
