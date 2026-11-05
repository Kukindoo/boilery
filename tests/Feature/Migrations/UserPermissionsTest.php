<?php

namespace Tests\Feature\Migrations;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class UserPermissionsTest extends FeatureTestCase
{
    public function test_provisions_missing_permissions_and_grants_them_only_to_staff_roles(): void
    {
        $migration = require database_path('migrations/2026_11_05_180000_add_user_permissions.php');
        foreach ([Permissions::USER_VIEW_ALL, Permissions::USER_EDIT_ALL] as $permission) {
            Permission::findByName($permission->label(), 'web')->delete();
        }
        $migration->up();
        $migration->up();

        foreach ([Permissions::USER_VIEW_ALL, Permissions::USER_EDIT_ALL] as $permission) {
            $this->assertSame(1, Permission::query()
                ->where('name', $permission->label())->where('guard_name', 'web')->count());
            $this->assertTrue(Role::findById(Roles::ADMIN->value, 'web')->hasPermissionTo($permission->label()));
            $this->assertTrue(Role::findById(Roles::STAKEHOLDER->value, 'web')->hasPermissionTo($permission->label()));
            $this->assertFalse(Role::findById(Roles::CUSTOMER->value, 'web')->hasPermissionTo($permission->label()));
        }
    }

    public function test_migration_restores_grants_on_existing_permissions(): void
    {
        foreach ([Permissions::USER_VIEW_ALL, Permissions::USER_EDIT_ALL] as $permission) {
            foreach ([Roles::ADMIN, Roles::STAKEHOLDER] as $role) {
                Role::findById($role->value, 'web')->revokePermissionTo($permission->label());
            }
        }

        $migration = require database_path('migrations/2026_11_05_180000_add_user_permissions.php');
        $migration->up();

        foreach ([Roles::ADMIN, Roles::STAKEHOLDER] as $role) {
            $staff = User::factory()->create()->assignRole($role->value);
            $this->assertTrue($staff->can(Permissions::USER_VIEW_ALL->label()));
            $this->assertTrue($staff->can(Permissions::USER_EDIT_ALL->label()));
        }
    }

    public function test_migration_reuses_existing_permission_records(): void
    {
        $existing = [];
        foreach (Permissions::cases() as $permission) {
            if (str_starts_with($permission->name, 'USER_')) {
                $existing[$permission->label()] = Permission::findByName($permission->label(), 'web')->id;
            }
        }
        $migration = require database_path('migrations/2026_11_05_180000_add_user_permissions.php');

        $migration->up();

        foreach ($existing as $label => $id) {
            $this->assertSame($id, Permission::findByName($label, 'web')->id);
        }
    }
}
