<?php

use App\Enums\Permissions;
use App\Enums\Roles;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions() as $permission) {
            $record = Permission::findOrCreate($permission->label(), 'web');

            $roles = match (true) {
                str_ends_with($permission->name, '_ALL') => [Roles::ADMIN, Roles::STAKEHOLDER],
                str_ends_with($permission->name, '_OWN') => [Roles::CUSTOMER],
                default => [],
            };

            foreach ($roles as $role) {
                Role::findById($role->value, 'web')->givePermissionTo($record);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @return list<Permissions> */
    private function permissions(): array
    {
        return [
            Permissions::USER_VIEW_ALL,
            Permissions::USER_VIEW_OWN,
            Permissions::USER_VIEW_INDEX,
            Permissions::USER_EDIT_ALL,
            Permissions::USER_EDIT_OWN,
            Permissions::USER_DELETE_ALL,
            Permissions::USER_DELETE_OWN,
            Permissions::USER_CREATE_ALL,
            Permissions::USER_CREATE_OWN,
            Permissions::USER_RESTORE_ALL,
            Permissions::USER_RESTORE_OWN,
            Permissions::USER_FORCE_DELETE_ALL,
            Permissions::USER_FORCE_DELETE_OWN,
        ];
    }
};
