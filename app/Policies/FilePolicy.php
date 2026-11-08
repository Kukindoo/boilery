<?php

namespace App\Policies;

use App\Contracts\OwnsFiles;
use App\Enums\FileTypes;
use App\Enums\Permissions;
use App\Enums\Roles;
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

    public function changeType(User $user, File $file): bool
    {
        return $user->hasAnyRole([Roles::ADMIN->value, Roles::STAKEHOLDER->value])
            && $this->update($user, $file);
    }

    public function uploadType(?User $user, FileTypes $fileType): bool
    {
        return ! in_array($fileType, [FileTypes::QUOTE_SUMMARY, FileTypes::SERVICE_PROTOCOL], true)
            || ($user?->hasAnyRole([Roles::ADMIN->value, Roles::STAKEHOLDER->value]) ?? false);
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
