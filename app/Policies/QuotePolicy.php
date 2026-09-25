<?php

namespace App\Policies;

use App\Enums\Permissions;
use App\Enums\RequestedQuoteStatus;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuotePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permissions::QUOTE_VIEW_ALL);
    }

    public function view(User $user, Quote $quote): bool
    {
        return $this->viewAny($user)
            or ($user->hasPermissionTo(Permissions::QUOTE_VIEW_OWN)
                and $quote->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Quote $quote): bool
    {
        return $user->hasPermissionTo(Permissions::QUOTE_EDIT_ALL) or
            ($user->hasPermissionTo(Permissions::QUOTE_EDIT_OWN)
                and $quote->user_id === $user->id
                and ! $quote->isClosed());
    }

    public function delete(User $user, Quote $quote): bool
    {
        return false;
    }

    public function restore(User $user, Quote $quote): bool
    {
        return false;
    }

    public function forceDelete(User $user, Quote $quote): bool
    {
        return false;
    }
}
