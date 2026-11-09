<?php

namespace App\Policies;

use App\Enums\Permissions;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::ROLE_VIEW_ALL->label());
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can(Permissions::ROLE_VIEW_ALL->label()) && $role->guard_name === 'web';
    }

    public function managePermissions(User $user, Role $role): bool
    {
        return $user->can(Permissions::ROLE_EDIT_ALL->label()) && $role->guard_name === 'web';
    }
}
