<?php

namespace App\Actions\Roles;

use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

class UpdateRolePermissions
{
    use LogsActionActivity;

    /**
     * @param  list<int>  $permissionIds
     *
     * @throws Throwable
     */
    public function handle(Role $role, array $permissionIds): void
    {
        try {
            DB::transaction(function () use ($role, $permissionIds): void {
                Gate::authorize('managePermissions', $role);
                $permissions = Permission::query()->where('guard_name', 'web')->whereIn('id', $permissionIds)->get();
                if ($permissions->count() !== count(array_unique($permissionIds))) {
                    throw ValidationException::withMessages(['permissionIds' => 'Vyberte platná oprávnění.']);
                }
                $role->syncPermissions($permissions);
                $this->logActionCompleted('roles', $role, 'Role permissions updated', ['permission_ids' => $permissionIds]);
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('roles', $role, $exception);
            throw $exception;
        }
    }
}
