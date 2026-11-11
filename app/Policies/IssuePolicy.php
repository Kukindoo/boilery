<?php

namespace App\Policies;

use App\Enums\Permissions;
use App\Models\Issue;
use App\Models\User;

class IssuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::ISSUE_VIEW_ALL->label());
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::ISSUE_CREATE_ALL->label())
            || $user->can(Permissions::ISSUE_CREATE_OWN->label());
    }

    public function view(User $user, Issue $issue): bool
    {
        return ! $issue->trashed()
            && ($user->can(Permissions::ISSUE_VIEW_ALL->label())
                || ($user->can(Permissions::ISSUE_VIEW_OWN->label()) && $issue->user_id === $user->id));
    }

    public function update(User $user, Issue $issue): bool
    {
        return ! $issue->trashed()
            && ($user->can(Permissions::ISSUE_EDIT_ALL->label());
    }

    public function delete(User $user, Issue $issue): bool
    {
        return ! $issue->trashed()
            && $user->can(Permissions::ISSUE_DELETE_ALL->label());
    }

    public function restore(User $user, Issue $issue): bool
    {
        return $issue->trashed()
            && ($user->can(Permissions::ISSUE_RESTORE_ALL->label());
    }

    public function forceDelete(User $user, Issue $issue): bool
    {
        return $issue->trashed()
            && $user->can(Permissions::ISSUE_FORCE_DELETE_ALL->label())
            && ! $issue->works()->withTrashed()->exists()
            && ! $issue->quotes()->withTrashed()->exists();
    }
}
