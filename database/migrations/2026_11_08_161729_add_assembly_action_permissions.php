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
        foreach ([
            Permissions::ASSEMBLY_ACTION_VIEW_ALL,
            Permissions::ASSEMBLY_ACTION_VIEW_INDEX,
            Permissions::ASSEMBLY_ACTION_CREATE_ALL,
            Permissions::ASSEMBLY_ACTION_EDIT_ALL,
            Permissions::ASSEMBLY_ACTION_DELETE_ALL,
            Permissions::ASSEMBLY_ACTION_RESTORE_ALL,
            Permissions::ASSEMBLY_ACTION_FORCE_DELETE_ALL,
        ] as $permission) {
            $record = Permission::firstOrCreate(
                ['name' => $permission->label(), 'guard_name' => 'web'],
                ['id' => $permission->value],
            );
            foreach ([Roles::ADMIN, Roles::STAKEHOLDER] as $role) {
                Role::findById($role->value, 'web')->givePermissionTo($record);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
