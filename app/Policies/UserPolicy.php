<?php

namespace App\Policies;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Models\User;

class UserPolicy
{
    public function manageRoles(User $user, User $customer): bool
    {
        return $user->hasRole(Roles::ADMIN->value);
    }

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::USER_VIEW_ALL->label());
    }

    public function view(User $user, User $customer): bool
    {
        return $user->can(Permissions::USER_VIEW_ALL->label())
            || ($user->can(Permissions::USER_VIEW_OWN->label()) && $user->id === $customer->id);
    }

    public function update(User $user, User $customer): bool
    {
        return $user->can(Permissions::USER_EDIT_ALL->label())
            || ($user->can(Permissions::USER_EDIT_OWN->label()) && $user->id === $customer->id);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::USER_CREATE_ALL->label())
            || $user->can(Permissions::USER_CREATE_OWN->label());
    }

    public function delete(User $user, User $customer): bool
    {
        return $user->can(Permissions::USER_DELETE_ALL->label())
            || ($user->can(Permissions::USER_DELETE_OWN->label()) && $user->id === $customer->id);
    }

    public function restore(User $user, User $customer): bool
    {
        return $user->can(Permissions::USER_RESTORE_ALL->label())
            || ($user->can(Permissions::USER_RESTORE_OWN->label()) && $user->id === $customer->id);
    }

    public function forceDelete(User $user, User $customer): bool
    {
        return $user->can(Permissions::USER_FORCE_DELETE_ALL->label())
            || ($user->can(Permissions::USER_FORCE_DELETE_OWN->label()) && $user->id === $customer->id);
    }
}
