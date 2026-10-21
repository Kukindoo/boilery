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
        $admin = Role::findById(Roles::ADMIN->value, 'web');

        foreach ([
            Permissions::WORK_VIEW_ALL,
            Permissions::WORK_VIEW_OWN,
            Permissions::WORK_VIEW_INDEX,
            Permissions::WORK_EDIT_ALL,
            Permissions::WORK_EDIT_OWN,
            Permissions::WORK_DELETE_ALL,
            Permissions::WORK_CREATE_ALL,
            Permissions::WORK_CREATE_OWN,
            Permissions::WORK_RESTORE_ALL,
            Permissions::WORK_RESTORE_OWN,
            Permissions::WORK_FORCE_DELETE_ALL,
        ] as $permission) {
            $record = Permission::findOrCreate($permission->label(), 'web');
            $admin->givePermissionTo($record);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
