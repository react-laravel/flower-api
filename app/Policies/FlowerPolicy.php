<?php

namespace App\Policies;

use App\Models\Flower;
use App\Models\User;

class FlowerPolicy
{
    /**
     * Anyone can view flowers (public read-only).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Anyone can view a single flower (public read-only).
     */
    public function view(User $user, Flower $flower): bool
    {
        return true;
    }

    /**
     * Only admins can create flowers.
     */
    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Only admins can update flowers.
     */
    public function update(User $user, Flower $flower): bool
    {
        return $user->is_admin;
    }

    /**
     * Only admins can delete flowers.
     */
    public function delete(User $user, Flower $flower): bool
    {
        return $user->is_admin;
    }
}
