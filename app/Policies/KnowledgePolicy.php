<?php

namespace App\Policies;

use App\Models\Knowledge;
use App\Models\User;
use App\Policies\Traits\AdminAccessControl;

class KnowledgePolicy
{
    use AdminAccessControl;

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
    public function view(User $user, Knowledge $knowledge): bool
    {
        return true;
    }

    /**
     * Only admins can delete.
     */
    public function delete(User $user, Knowledge $knowledge): bool
    {
        return $user->is_admin === true;
    }
}
