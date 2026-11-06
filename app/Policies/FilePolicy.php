<?php

namespace App\Policies;

use App\Contracts\OwnsFiles;
use App\Enums\Permissions;
use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

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
                && $file->isOwnedBy($user));
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
                && $file->isExclusivelyOwnedBy($user));
    }

    public function delete(User $user, File $file): bool
    {
        return $user->can(Permissions::FILE_DELETE_ALL->label())
            || ($user->can(Permissions::FILE_DELETE_OWN->label())
                && $file->isExclusivelyOwnedBy($user));
    }

    public function forceDelete(User $user, File $file): bool
    {
        return $user->can(Permissions::FILE_FORCE_DELETE_ALL->label())
            || ($user->can(Permissions::FILE_FORCE_DELETE_OWN->label())
                && $file->isExclusivelyOwnedBy($user));
    }

    public function detach(User $user, File $file, Model $parent): bool
    {
        return $user->can(Permissions::FILE_DELETE_ALL->label())
            || ($user->can(Permissions::FILE_DELETE_OWN->label())
                && $parent instanceof OwnsFiles && $parent->isFileOwner($user));
    }
}
