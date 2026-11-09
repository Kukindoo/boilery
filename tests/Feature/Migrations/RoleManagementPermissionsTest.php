<?php

namespace Tests\Feature\Migrations;

use App\Enums\Permissions;
use App\Enums\Roles;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class RoleManagementPermissionsTest extends FeatureTestCase
{
    public function test_migration_provisions_permissions_idempotently_and_grants_only_admin(): void
    {
        foreach ($this->permissions() as $permission) {
            Permission::findByName($permission->label(), 'web')->delete();
        }
        $migration = require database_path('migrations/2026_11_09_181640_add_role_and_permission_management_permissions.php');

        $migration->up();
        $migration->up();

        foreach ($this->permissions() as $permission) {
            $record = Permission::findByName($permission->label(), 'web');
            $this->assertSame(1, Permission::query()->where('name', $permission->label())->where('guard_name', 'web')->count());
            $this->assertSame([Roles::ADMIN->value], $record->roles()->pluck('roles.id')->all());
        }
    }

    public function test_rollback_removes_only_new_permissions_and_preserves_existing_grants(): void
    {
        $retained = Permission::findByName(Permissions::USER_EDIT_ALL->label());
        $migration = require database_path('migrations/2026_11_09_181640_add_role_and_permission_management_permissions.php');

        $migration->down();

        foreach ($this->permissions() as $permission) {
            $this->assertDatabaseMissing('permissions', ['name' => $permission->label(), 'guard_name' => 'web']);
        }
        $this->assertModelExists($retained);
        $this->assertTrue(Role::findById(Roles::ADMIN->value)->hasPermissionTo($retained));
    }

    private function permissions(): array
    {
        return [Permissions::ROLE_VIEW_ALL, Permissions::ROLE_EDIT_ALL, Permissions::PERMISSION_VIEW_ALL];
    }
}
