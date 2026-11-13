<?php

namespace App\Policies;

use App\Enums\Permissions;
use App\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::ACTIVITY_LOG_VIEW_ALL->label());
    }
}
