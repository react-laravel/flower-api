<?php

namespace App\Policies;

use App\Models\Knowledge;
use App\Models\User;
use App\Policies\Traits\AdminAccessControl;

class KnowledgePolicy
{
    use AdminAccessControl;
}
