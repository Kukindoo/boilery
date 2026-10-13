<?php

namespace App\Policies;

use App\Enums\Permissions;
use App\Models\User;
use App\Models\Work;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::WORK_VIEW_ALL->label());
    }

    public function view(User $user, Work $work): bool
    {
        return $this->viewAny($user)
            || ($user->can(Permissions::WORK_VIEW_OWN->label())
                && $work->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::WORK_CREATE_ALL->label())
            || $user->can(Permissions::WORK_CREATE_OWN->label());
    }

    public function update(User $user, Work $work): bool
    {
        return $user->can(Permissions::WORK_EDIT_ALL->label())
            || ($user->can(Permissions::WORK_EDIT_OWN->label())
                && $work->user_id === $user->id);
    }

    public function delete(User $user, Work $work): bool
    {
        return ! $work->trashed()
            && $user->can(Permissions::WORK_DELETE_ALL->label());
    }

    public function restore(User $user, Work $work): bool
    {
        return $work->trashed()
            && ($user->can(Permissions::WORK_RESTORE_ALL->label())
                || ($user->can(Permissions::WORK_RESTORE_OWN->label())
                    && $work->user_id === $user->id));
    }

    public function forceDelete(User $user, Work $work): bool
    {
        return $work->trashed()
            && $user->can(Permissions::WORK_FORCE_DELETE_ALL->label());
    }
}
