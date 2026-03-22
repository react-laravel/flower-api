<?php

namespace App\Policies;

use App\Models\Knowledge;
use App\Models\User;

class KnowledgePolicy
{
    /**
     * Anyone can view knowledge base (public read-only).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Anyone can view a single knowledge entry (public read-only).
     */
    public function view(User $user, Knowledge $knowledge): bool
    {
        return true;
    }

    /**
     * Only admins can create knowledge entries.
     */
    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Only admins can update knowledge entries.
     */
    public function update(User $user, Knowledge $knowledge): bool
    {
        return $user->is_admin;
    }

    /**
     * Only admins can delete knowledge entries.
     */
    public function delete(User $user, Knowledge $knowledge): bool
    {
        return $user->is_admin;
    }
}
