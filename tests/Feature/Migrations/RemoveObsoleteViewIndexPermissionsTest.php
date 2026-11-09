<?php

namespace Tests\Feature\Migrations;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class RemoveObsoleteViewIndexPermissionsTest extends FeatureTestCase
{
    public function test_removes_obsolete_permissions_and_assignments_by_name_and_preserves_view_all(): void
    {
        $role = Role::findById(Roles::ADMIN->value);
        $user = User::factory()->create();
        $names = ['View index quotes', 'View index works', 'View index assembly actions', 'View index users', 'View index spare parts', 'Print all works', 'Print all quotes'];
        $ids = [];
        foreach ($names as $offset => $name) {
            $permission = Permission::forceCreate(['id' => 10001 + $offset, 'name' => $name, 'guard_name' => 'web']);
            $ids[] = $permission->id;
            $role->givePermissionTo($permission);
            $user->givePermissionTo($permission);
        }
        $retained = Permission::findByName(Permissions::SPARE_PART_VIEW_ALL->label());
        $originalId = $retained->id;
        $migration = require database_path('migrations/2026_11_09_164244_remove_obsolete_view_index_permissions.php');

        $migration->up();
        $migration->up();

        $this->assertSame(0, Permission::query()->whereIn('name', $names)->count());
        $this->assertSame(0, DB::table('role_has_permissions')->whereIn('permission_id', $ids)->count());
        $this->assertSame(0, DB::table('model_has_permissions')->whereIn('permission_id', $ids)->count());
        $this->assertSame($originalId, $retained->refresh()->id);
        $this->assertTrue($role->fresh()->hasPermissionTo($retained));
        $this->assertFalse($user->fresh()->can('View index users'));
        $this->assertTrue($role->fresh()->hasPermissionTo(Permissions::USER_VIEW_ALL->label()));
    }
}
