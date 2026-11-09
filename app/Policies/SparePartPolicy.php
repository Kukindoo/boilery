<?php

namespace App\Policies;

use App\Enums\Permissions;
use App\Models\SparePart;
use App\Models\User;

class SparePartPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::SPARE_PART_VIEW_ALL->label());
    }

    public function view(User $user, SparePart $sparePart): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::SPARE_PART_CREATE_ALL->label());
    }

    public function update(User $user, SparePart $sparePart): bool
    {
        return ! $sparePart->trashed()
            && $user->can(Permissions::SPARE_PART_EDIT_ALL->label());
    }

    public function delete(User $user, SparePart $sparePart): bool
    {
        return ! $sparePart->trashed()
            && $user->can(Permissions::SPARE_PART_DELETE_ALL->label())
            && ! ($sparePart->getAttribute('works_exists') ?? $sparePart->works()->withTrashed()->exists());
    }

    public function restore(User $user, SparePart $sparePart): bool
    {
        return $sparePart->trashed()
            && $user->can(Permissions::SPARE_PART_RESTORE_ALL->label());
    }

    public function forceDelete(User $user, SparePart $sparePart): bool
    {
        return $sparePart->trashed()
            && $user->can(Permissions::SPARE_PART_FORCE_DELETE_ALL->label())
            && ! ($sparePart->getAttribute('works_exists') ?? $sparePart->works()->withTrashed()->exists());
    }
}
