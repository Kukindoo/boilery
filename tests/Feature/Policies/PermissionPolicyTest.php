<?php

namespace Tests\Feature\Policies;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class PermissionPolicyTest extends FeatureTestCase
{
    #[TestWith([null, false])]
    #[TestWith([Permissions::PERMISSION_VIEW_ALL, true])]
    #[TestWith([Permissions::ROLE_EDIT_ALL, false])]
    public function test_permission_view_uses_permission_instead_of_role_identity(?Permissions $permission, bool $allowed): void
    {
        $user = User::factory()->create();
        if ($permission !== null) {
            $role = Role::create(['name' => 'Delegated role', 'guard_name' => 'web']);
            $role->givePermissionTo($permission->label());
            $user->assignRole($role);
        }
        $target = Permission::findByName(Permissions::WORK_VIEW_ALL->label());
        $gate = Gate::forUser($user);

        $this->assertSame($allowed, $gate->allows('viewAny', Permission::class));
        $this->assertSame($allowed, $gate->allows('view', $target));
    }

    public function test_view_requires_permission_but_does_not_restrict_target_guard(): void
    {
        $role = Role::findById(Roles::ADMIN->value);
        $role->revokePermissionTo(Permissions::PERMISSION_VIEW_ALL->label());
        $admin = User::factory()->admin()->create();
        $this->assertFalse(Gate::forUser($admin)->allows('viewAny', Permission::class));
        $role->givePermissionTo(Permissions::PERMISSION_VIEW_ALL->label());
        $api = Permission::create(['name' => 'API permission', 'guard_name' => 'api']);
        $this->assertTrue(Gate::forUser($admin->fresh())->allows('view', $api));
    }
}
