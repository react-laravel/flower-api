<?php

namespace App\Policies;

use App\Models\Flower;
use App\Models\User;
use App\Policies\Traits\AdminAccessControl;

class FlowerPolicy
{
    use AdminAccessControl;
}
