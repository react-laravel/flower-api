<?php

namespace App\Policies;

use App\Models\SiteSetting;
use App\Models\User;

class SiteSettingPolicy
{
    /**
     * Anyone can view settings (public read-only).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Anyone can view a specific setting (public read-only).
     */
    public function view(User $user, SiteSetting $setting): bool
    {
        return true;
    }

    /**
     * Only admins can update settings.
     */
    public function update(User $user, SiteSetting $setting): bool
    {
        return $user->is_admin === true;
    }
}
