<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\ContractsFilesystem\Filesystem;

class UploadPolicy
{
    /**
     * Only admins can upload files.
     */
    public function create(User $user): bool
    {
        return $user->is_admin === true;
    }

    /**
     * Only admins can delete uploads.
     */
    public function delete(User $user): bool
    {
        return $user->is_admin === true;
    }
}
