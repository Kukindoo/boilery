<?php

namespace App\Policies;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Models\AssemblyAction;
use App\Models\User;

class AssemblyActionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::ASSEMBLY_ACTION_VIEW_ALL->label());
    }

    public function view(User $user, AssemblyAction $assemblyAction): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::ASSEMBLY_ACTION_CREATE_ALL->label());
    }

    public function update(User $user, AssemblyAction $assemblyAction): bool
    {
        return ! $assemblyAction->trashed()
            && $user->can(Permissions::ASSEMBLY_ACTION_EDIT_ALL->label());
    }

    public function delete(User $user, AssemblyAction $assemblyAction): bool
    {
        return ! $assemblyAction->trashed()
            && $user->can(Permissions::ASSEMBLY_ACTION_DELETE_ALL->label())
            && ! ($assemblyAction->getAttribute('works_exists') ?? $assemblyAction->works()->withTrashed()->exists());
    }

    public function restore(User $user, AssemblyAction $assemblyAction): bool
    {
        return $assemblyAction->trashed()
            && $user->can(Permissions::ASSEMBLY_ACTION_RESTORE_ALL->label());
    }

    public function forceDelete(User $user, AssemblyAction $assemblyAction): bool
    {
        return $assemblyAction->trashed()
            && $user->can(Permissions::ASSEMBLY_ACTION_FORCE_DELETE_ALL->label())
            && ! ($assemblyAction->getAttribute('works_exists') ?? $assemblyAction->works()->withTrashed()->exists());
    }
}
