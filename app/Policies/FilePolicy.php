<?php

namespace App\Policies;

use App\Enums\Permissions;
use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::FILE_VIEW_ALL->label());
    }

    public function view(User $user, File $file): bool
    {
        return $this->viewAny($user)
            || ($user->can(Permissions::FILE_VIEW_OWN->label())
                && $file->quote?->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::FILE_CREATE_ALL->label())
            || $user->can(Permissions::FILE_CREATE_OWN->label());
    }

    public function update(User $user, File $file): bool
    {
        return $user->can(Permissions::FILE_EDIT_ALL->label())
            || ($user->can(Permissions::FILE_EDIT_OWN->label())
                && $file->quote?->user_id === $user->id);
    }

    public function delete(User $user, File $file): bool
    {
        return $user->can(Permissions::FILE_DELETE_ALL->label())
            || ($user->can(Permissions::FILE_DELETE_OWN->label())
                && $file->quote?->user_id === $user->id);
    }

    public function forceDelete(User $user, File $file): bool
    {
        return $user->can(Permissions::FILE_FORCE_DELETE_ALL->label())
            || ($user->can(Permissions::FILE_FORCE_DELETE_OWN->label())
                && $file->quote?->user_id === $user->id);
    }
}
