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
            Permissions::ISSUE_VIEW_ALL,
            Permissions::ISSUE_VIEW_OWN,
            Permissions::ISSUE_CREATE_ALL,
            Permissions::ISSUE_CREATE_OWN,
            Permissions::ISSUE_EDIT_ALL,
            Permissions::ISSUE_EDIT_OWN,
            Permissions::ISSUE_DELETE_ALL,
            Permissions::ISSUE_RESTORE_ALL,
            Permissions::ISSUE_RESTORE_OWN,
            Permissions::ISSUE_FORCE_DELETE_ALL,
        ] as $case) {
            $permission = Permission::findOrCreate($case->label(), 'web');
            $admin->givePermissionTo($permission);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
