<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Policies\Traits\AdminAccessControl;

class CategoryPolicy
{
    use AdminAccessControl;
}
