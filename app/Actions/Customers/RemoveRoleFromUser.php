<?php

namespace App\Actions\Customers;

use App\Enums\Roles;
use App\Models\User;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Throwable;

class RemoveRoleFromUser
{
    use LogsActionActivity;

    /** @throws Throwable */
    public function handle(User $user, Role $role): void
    {
        try {
            DB::transaction(function () use ($user, $role): void {
                $adminRole = Role::query()->lockForUpdate()->findOrFail(Roles::ADMIN->value);
                Gate::authorize('view', $role);
                Gate::authorize('manageRoles', $user);
                $target = User::query()->lockForUpdate()->findOrFail($user->id);
                abort_unless($target->roles()->whereKey($role->id)->exists(), 404);
                if ($role->is($adminRole) && $adminRole->users()->count() <= 1) {
                    throw ValidationException::withMessages(['roleIds' => 'Poslednímu administrátorovi nelze odebrat roli Admin.']);
                }

                $target->removeRole($role);
                $this->logActionCompleted('users', $target, 'Role removed', ['role_id' => $role->id]);
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('users', $user, $exception);
            throw $exception;
        }
    }
}
