<?php

namespace App\Actions\Permissions;

use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

class UpdatePermissionRoles
{
    use LogsActionActivity;

    /**
     * @param  list<int>  $roleIds
     *
     * @throws Throwable
     */
    public function handle(Permission $permission, array $roleIds): void
    {
        try {
            DB::transaction(function () use ($permission, $roleIds): void {
                Gate::authorize('manageRoles', $permission);
                $roles = Role::query()->where('guard_name', 'web')->whereIn('id', $roleIds)->get();
                if ($roles->count() !== count(array_unique($roleIds))) {
                    throw ValidationException::withMessages(['roleIds' => 'Vyberte platné role.']);
                }
                $permission->syncRoles($roles);
                $this->logActionCompleted('permissions', $permission, 'Permission roles updated', ['role_ids' => $roleIds]);
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('permissions', $permission, $exception);
            throw $exception;
        }
    }
}
