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
            Permissions::SPARE_PART_VIEW_ALL,
            Permissions::SPARE_PART_CREATE_ALL,
            Permissions::SPARE_PART_EDIT_ALL,
            Permissions::SPARE_PART_DELETE_ALL,
            Permissions::SPARE_PART_RESTORE_ALL,
            Permissions::SPARE_PART_FORCE_DELETE_ALL,
        ] as $case) {
            $permission = Permission::firstOrCreate(['id' => $case->value], [
                'name' => $case->label(), 'guard_name' => 'web',
            ]);
            foreach ([Roles::ADMIN, Roles::STAKEHOLDER] as $role) {
                Role::findById($role->value, 'web')->givePermissionTo($permission);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
