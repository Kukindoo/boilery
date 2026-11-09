<?php

namespace App\Policies;

use App\Enums\Permissions;
use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::PERMISSION_VIEW_ALL->label());
    }

    public function view(User $user, Permission $permission): bool
    {
        return $this->viewAny($user);
    }

    public function manageRoles(User $user, Permission $permission): bool
    {
        return $user->can(Permissions::ROLE_EDIT_ALL->label());
    }
}
