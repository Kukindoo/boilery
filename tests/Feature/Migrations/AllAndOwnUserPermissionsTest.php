<?php

namespace Tests\Feature\Migrations;

use App\Enums\Permissions;
use App\Enums\Roles;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class AllAndOwnUserPermissionsTest extends FeatureTestCase
{
    public function test_all_user_permissions_are_provisioned_and_assigned_by_scope(): void
    {
        $migration = require database_path('migrations/2026_11_05_180000_add_user_permissions.php');
        foreach (Permissions::cases() as $permission) {
            if (str_starts_with($permission->name, 'USER_')) {
                Permission::findByName($permission->label(), 'web')->delete();
            }
        }
        $migration->up();
        $migration->up();

        foreach (Permissions::cases() as $permission) {
            if (! str_starts_with($permission->name, 'USER_')) {
                continue;
            }

            $own = str_ends_with($permission->name, '_OWN');
            $this->assertSame(1, Permission::query()->where('name', $permission->label())->where('guard_name', 'web')->count());
            foreach ([Roles::ADMIN, Roles::STAKEHOLDER, Roles::CUSTOMER] as $role) {
                $expected = $own
                    ? $role === Roles::CUSTOMER
                    : (str_ends_with($permission->name, '_ALL') && $role !== Roles::CUSTOMER);
                $this->assertSame($expected, Role::findById($role->value, 'web')->hasPermissionTo($permission->label()));
            }
        }
    }

    public function test_migration_preserves_unrelated_permissions(): void
    {
        $existing = Permission::findByName(Permissions::WORK_VIEW_ALL->label(), 'web');
        $attributes = $existing->refresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_05_180000_add_user_permissions.php');

        $migration->up();

        $this->assertSame($attributes, $existing->refresh()->getAttributes());
    }
}
