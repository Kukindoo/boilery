<?php

use App\Enums\Permissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            Permissions::WORK_VIEW_ALL,
            Permissions::WORK_VIEW_OWN,
            Permissions::WORK_VIEW_INDEX,
            Permissions::WORK_PRINT_ALL,
            Permissions::WORK_EDIT_ALL,
            Permissions::WORK_EDIT_OWN,
            Permissions::WORK_DELETE_ALL,
            Permissions::WORK_DELETE_OWN,
            Permissions::WORK_CREATE_ALL,
            Permissions::WORK_CREATE_OWN,
            Permissions::WORK_RESTORE_ALL,
            Permissions::WORK_RESTORE_OWN,
            Permissions::WORK_FORCE_DELETE_ALL,
            Permissions::WORK_FORCE_DELETE_OWN,
        ] as $permission) {
            Permission::firstOrCreate([
                'id' => $permission->value,
            ], [
                'name' => $permission->label(),
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
