<?php

namespace App\Policies\Traits;

use App\Models\User;

/**
 * Shared admin-only access control for policies.
 * Fixes DRY violation across FlowerPolicy, CategoryPolicy, KnowledgePolicy.
 *
 * Note: viewAny, view, and delete are NOT included in this trait because they
 * have method name conflicts with Illuminate\Foundation\Testing\TestCase methods.
 * Define them directly in each policy class instead.
 */
trait AdminAccessControl
{
    /**
     * Only admins can create.
     */
    public function create(User $user): bool
    {
        return $user->is_admin === true;
    }

    /**
     * Only admins can update.
     */
    public function update(User $user, $model): bool
    {
        return $user->is_admin === true;
    }
}
