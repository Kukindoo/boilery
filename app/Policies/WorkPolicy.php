<?php

namespace App\Policies;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Enums\WorkStatus;
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

    public function generatePdf(User $user, Work $work): bool
    {
        return $this->viewAny($user)
            && $user->can(Permissions::GENERATE_PDF->label())
            && ! $work->hasServiceProtocol();
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::WORK_CREATE_ALL->label())
            || $user->can(Permissions::WORK_CREATE_OWN->label());
    }

    public function uploadFile(User $user, Work $work): bool
    {
        return ! $work->trashed()
            && $this->update($user, $work)
            && ($user->can(Permissions::FILE_CREATE_ALL->label())
                || ($user->can(Permissions::FILE_CREATE_OWN->label()) && $work->user_id === $user->id));
    }

    public function update(User $user, Work $work): bool
    {
        return $user->can(Permissions::WORK_EDIT_ALL->label())
            || ($user->can(Permissions::WORK_EDIT_OWN->label())
                && $work->user_id === $user->id);
    }

    public function manageSpareParts(User $user, Work $work): bool
    {
        return ! $work->trashed()
            && $this->view($user, $work)
            && $this->update($user, $work);
    }

    public function start(User $user, Work $work): bool
    {
        return $user->can(Permissions::WORK_EDIT_ALL->label())
            && ($work->status === WorkStatus::NEW
            || $work->status === WorkStatus::FINISHED
            || $work->status === WorkStatus::CANCELLED);
    }

    public function end(User $user, Work $work): bool
    {
        return $user->can(Permissions::WORK_EDIT_ALL->label())
            && $work->status === WorkStatus::PROCESSING;
    }

    public function cancel(User $user, Work $work): bool
    {
        return $user->can(Permissions::WORK_EDIT_ALL->label())
            && $work->status === WorkStatus::PROCESSING;
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
