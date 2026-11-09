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

class UpdateUserRoles
{
    use LogsActionActivity;

    /**
     * @param  list<int>  $roleIds
     *
     * @throws Throwable
     */
    public function handle(User $user, array $roleIds): void
    {
        try {
            DB::transaction(function () use ($user, $roleIds): void {
                $adminRole = Role::query()->lockForUpdate()->findOrFail(Roles::ADMIN->value);
                Gate::authorize('manageRoles', $user);
                $target = User::query()->lockForUpdate()->findOrFail($user->id);
                $roles = Role::query()->where('guard_name', 'web')->whereIn('id', $roleIds)->get();
                if ($roles->count() !== count(array_unique($roleIds))) {
                    throw ValidationException::withMessages(['roleIds' => 'Vyberte platné role.']);
                }
                if ($target->hasRole($adminRole) && ! $roles->contains('id', $adminRole->id) && $adminRole->users()->count() <= 1) {
                    throw ValidationException::withMessages(['roleIds' => 'Poslednímu administrátorovi nelze odebrat roli Admin.']);
                }

                $target->syncRoles($roles);
                $this->logActionCompleted('users', $target, 'Roles updated', ['role_ids' => $roleIds]);
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('users', $user, $exception);
            throw $exception;
        }
    }
}
