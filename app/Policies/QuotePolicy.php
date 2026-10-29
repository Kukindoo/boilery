<?php

namespace App\Policies;

use App\Enums\Permissions;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuotePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::QUOTE_VIEW_ALL->label());
    }

    public function accept(User $user, Quote $quote): bool
    {
        return ! $quote->isClosed()
            && (! $quote->trashed() && $user->can(Permissions::QUOTE_EDIT_ALL->label()));
    }

    public function view(User $user, Quote $quote): bool
    {
        return $this->viewAny($user)
            || ($user->can(Permissions::QUOTE_VIEW_OWN->label())
                && $quote->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::QUOTE_CREATE_ALL->label())
            || $user->can(Permissions::QUOTE_CREATE_OWN->label());
    }

    public function uploadFile(User $user, Quote $quote): bool
    {
        return ($user->can(Permissions::FILE_CREATE_ALL->label())
                || ($user->can(Permissions::FILE_CREATE_OWN->label())
                    && $quote->user_id === $user->id))
            && ($user->can(Permissions::QUOTE_EDIT_ALL->label())
                || ($user->can(Permissions::QUOTE_EDIT_OWN->label())
                    && $quote->user_id === $user->id));
    }

    public function update(User $user, Quote $quote): bool
    {
        return ! $quote->isClosed()
            && ($user->can(Permissions::QUOTE_EDIT_ALL->label())
                || ($user->can(Permissions::QUOTE_EDIT_OWN->label())
                    && $quote->user_id === $user->id));
    }

    public function reassignment(User $user, Quote $quote): bool
    {
        return ! $quote->isClosed()
            && $user->can(Permissions::QUOTE_EDIT_ALL->label());
    }

    public function delete(User $user, Quote $quote): bool
    {
        return ! $quote->trashed()
            && ! $quote->isClosed()
            && $user->can(Permissions::QUOTE_DELETE_ALL->label());
    }

    public function restore(User $user, Quote $quote): bool
    {
        return $quote->trashed()
            && ($user->can(Permissions::QUOTE_RESTORE_ALL->label())
                || ($user->can(Permissions::QUOTE_RESTORE_OWN->label())
                    && $quote->user_id === $user->id));
    }

    public function forceDelete(User $user, Quote $quote): bool
    {
        return $quote->trashed()
            && ! $quote->isClosed()
            && $user->can(Permissions::QUOTE_FORCE_DELETE_ALL->label());
    }
}
